<?php

namespace App\Http\Controllers;

use App\Models\ExaminationTimetable;
use App\Models\ExamSetup;
use App\Models\Faculty;
use App\Models\Venue;
use App\Models\Course;
use App\Models\Program;
use App\Models\FacultyGroup;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExaminationTimetableController extends Controller
{
    public function index(Request $request)
    {
        $setups = ExamSetup::all();
        $allPrograms = Program::all();
        $venues = Venue::all();
        $selectedType = $request->query('exam_type');
        $setup = null;
        $days = [];
        $timeSlots = [];
        $programs = [];
        $timetables = [];

        // Generate academic years for the setup form (2010/2011 to 2025/2026)
        $academicYears = [];
        for ($year = 2010; $year <= 2025; $year++) {
            $academicYears[] = "$year/" . ($year + 1);
        }

        // Available semesters
        $semesters = ['1', '2', 'Final'];

        // If a type is selected, find the setup that includes that type
        if ($selectedType && $setups->isNotEmpty()) {
            $setup = $setups->firstWhere(function ($s) use ($selectedType) {
                return in_array($selectedType, $s->type);
            });
        } elseif ($setups->isNotEmpty()) {
            $setup = $setups->first();
            $selectedType = $setup->type[0] ?? null;
        }

        if ($setup) {
            $startDate = Carbon::parse($setup->start_date);
            $endDate = Carbon::parse($setup->end_date);
            $includeWeekends = $setup->include_weekends;
            $days = $this->getValidDates($setup);
            $timeSlots = $setup->time_slots;
            $programs = Program::whereIn('id', $setup->programs)->get();
            $timetables = ExaminationTimetable::with(['faculty', 'venue', 'lecturers'])
                ->whereIn('exam_date', $days)
                ->whereIn('faculty_id', Faculty::whereIn('program_id', $setup->programs)->pluck('id'))
                ->get();
        }

        // Get all available exam types from setups
        $examTypes = $setups->flatMap(function ($s) {
            return $s->type;
        })->unique()->values();

        return view('timetables.index', compact(
            'setups',
            'setup',
            'days',
            'timeSlots',
            'programs',
            'timetables',
            'allPrograms',
            'venues',
            'selectedType',
            'examTypes',
            'academicYears',
            'semesters'
        ));
    }

    public function storeSetup(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|array|min:1',
            'type.*' => 'in:Degree,Non Degree,Masters',
            'academic_year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
            'semester' => 'required|in:1,2,Final',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'include_weekends' => 'nullable|boolean',
            'time_slots' => 'required|array|min:1',
            'time_slots.*.name' => 'required|string|max:255',
            'time_slots.*.start_time' => 'required|date_format:H:i',
            'time_slots.*.end_time' => 'required|date_format:H:i|after:time_slots.*.start_time',
            'programs' => 'required|array|min:1',
            'programs.*' => 'exists:programs,id',
        ]);

        $validated['include_weekends'] = $request->has('include_weekends');
        ExamSetup::create($validated);

        return redirect()->route('timetables.index')->with('success', 'Setup created successfully.');
    }

    public function updateSetup(Request $request, ExamSetup $setup)
    {
        $validated = $request->validate([
            'type' => 'required|array|min:1',
            'type.*' => 'in:Degree,Non Degree,Masters',
            'academic_year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
            'semester' => 'required|in:1,2,Final',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'include_weekends' => 'nullable|boolean',
            'time_slots' => 'required|array|min:1',
            'time_slots.*.name' => 'required|string|max:255',
            'time_slots.*.start_time' => 'required|date_format:H:i',
            'time_slots.*.end_time' => 'required|date_format:H:i|after:time_slots.*.start_time',
            'programs' => 'required|array|min:1',
            'programs.*' => 'exists:programs,id',
        ]);

        $validated['include_weekends'] = $request->has('include_weekends');
        $setup->update($validated);
        return redirect()->route('timetables.index')->with('success', 'Setup updated successfully.');
    }

         public function store(Request $request)
    {
        $setup = ExamSetup::first();
        if (!$setup) {
            Log::error('No setup found');
            return $request->ajax()
                ? response()->json(['errors' => ['setup' => 'No setup found']], 422)
                : back()->with('error', 'No setup found')->withInput();
        }

        $timeSlot = json_decode($request->input('time_slot'), true);
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'course_code' => 'required|exists:courses,course_code',
            'exam_date' => 'required|date|in:' . implode(',', $this->getValidDates($setup)),
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'venue_id' => 'required|exists:venues,id',
            'group_selection' => 'required|array|min:1',
            'group_selection.*' => 'string',
            'lecturer_ids' => 'required|array|min:1',
            'lecturer_ids.*' => 'exists:users,id',
        ]);

        // Handle "All Groups" selection
        if (in_array('all', $validated['group_selection'])) {
            $groups = FacultyGroup::where('faculty_id', $validated['faculty_id'])
                ->pluck('group_name')
                ->toArray();
            $validated['group_selection'] = $groups;
        }

        // Extract start_time and end_time from time_slot if not directly provided
        if ($timeSlot && isset($timeSlot['start_time']) && isset($timeSlot['end_time'])) {
            $validated['start_time'] = $timeSlot['start_time'];
            $validated['end_time'] = $timeSlot['end_time'];
        }

        // Check for conflicts
        $conflicts = $this->checkConflicts($request, $validated);
        Log::info('Conflict check result:', ['conflicts' => $conflicts]);
        if (!empty($conflicts)) {
            $errorMessage = implode(' ', $conflicts);
            Log::info('Returning conflict error:', ['errorMessage' => $errorMessage]);
            return $request->ajax()
                ? response()->json(['errors' => ['conflict' => $errorMessage]], 422)
                : back()->withInput()->withErrors(['conflict' => $errorMessage]);
        }

        try {
            $validated['group_selection'] = implode(',', $validated['group_selection']);
            $timetable = ExaminationTimetable::create($validated);
            $timetable->lecturers()->sync($validated['lecturer_ids']);
            Log::info('Exam timetable created successfully:', ['id' => $timetable->id]);
            return $request->ajax()
                ? response()->json(['message' => 'Exam timetable created successfully.', 'id' => $timetable->id])
                : redirect()->route('timetables.index')->with('success', 'Exam timetable created successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error creating timetable:', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);
            $errorMessage = 'Failed to save exam timetable due to a database error.';
            return $request->ajax()
                ? response()->json(['errors' => ['database' => $errorMessage]], 422)
                : back()->withInput()->withErrors(['database' => $errorMessage]);
        } catch (\Exception $e) {
            Log::error('Unexpected error in store:', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            $errorMessage = 'Failed to save exam timetable due to an unexpected error.';
            return $request->ajax()
                ? response()->json(['errors' => ['error' => $errorMessage]], 422)
                : back()->withInput()->withErrors(['error' => $errorMessage]);
        }
    }


    public function update(Request $request, ExaminationTimetable $timetable)
    {
        Log::info('Update timetable:', ['id' => $timetable->id, 'input' => $request->all()]);
        $setup = ExamSetup::first();
        if (!$setup) {
            Log::error('No setup found');
            return response()->json(['error' => 'No setup found'], 422);
        }
        try {
            $timeSlot = json_decode($request->input('time_slot'), true);
            $validated = $request->validate([
                'faculty_id' => 'required|exists:faculties,id',
                'course_code' => 'required|exists:courses,course_code',
                'exam_date' => 'required|date|in:' . implode(',', $this->getValidDates($setup)),
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'venue_id' => 'required|exists:venues,id',
                'group_selection' => 'required|array|min:1',
                'group_selection.*' => 'string',
                'lecturer_ids' => 'required|array|min:1',
                'lecturer_ids.*' => 'exists:users,id',
            ]);

            // Handle "All Groups" selection
            if (in_array('all', $validated['group_selection'])) {
                $groups = FacultyGroup::where('faculty_id', $validated['faculty_id'])
                    ->pluck('group_name')
                    ->toArray();
                $validated['group_selection'] = $groups;
            }

            // Extract start_time and end_time from time_slot if not directly provided
            if ($timeSlot && isset($timeSlot['start_time'], $timeSlot['end_time'])) {
                $validated['start_time'] = Carbon::createFromFormat('H:i', $timeSlot['start_time'])->format('H:i');
                $validated['end_time'] = Carbon::createFromFormat('H:i', $timeSlot['end_time'])->format('H:i');
            }



            // Check for conflicts, excluding the current timetable
            $conflicts = $this->checkConflicts($request, $validated, $timetable->id);
            Log::info('Conflict check result:', ['conflicts' => $conflicts]);
            if (!empty($conflicts)) {
                $errorMessage = implode(' ', $conflicts);
                return response()->json(['errors' => ['conflict' => $errorMessage]], 422);
            }

            $validated['group_selection'] = implode(',', $validated['group_selection']);
            Log::info('Validated data:', $validated);
            $timetable->update($validated);
            $timetable->lecturers()->sync($validated['lecturer_ids']);
            return response()->json(['success' => 'Exam timetable updated successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors()]);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Update failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Update failed', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy(ExaminationTimetable $timetable)
    {
        $timetable->delete();
        return redirect()->route('timetables.index')->with('success', 'Exam timetable deleted successfully.');
    }

    public function show($id)
    {
        $timetable = ExaminationTimetable::with(['faculty', 'venue', 'lecturers', 'course'])->findOrFail($id);
        Log::info('Show timetable:', ['id' => $id, 'lecturers' => $timetable->lecturers->pluck('name')->toArray()]);

        $course = $timetable->course;
        $groupSelection = $timetable->group_selection;

        // Check if all groups are selected
        $allGroups = FacultyGroup::where('faculty_id', $timetable->faculty_id)
            ->pluck('group_name')
            ->toArray();
        $selectedGroups = explode(',', $timetable->group_selection);
        $isAllGroups = empty(array_diff($allGroups, $selectedGroups)) && empty(array_diff($selectedGroups, $allGroups));

        return response()->json([
            'course_code' => $timetable->course_code,
            'course_name' => $course ? $course->name : 'N/A',
            'exam_date' => $timetable->exam_date,
            'start_time' => $timetable->start_time,
            'end_time' => $timetable->end_time,
            'time_slot_name' => $timetable->time_slot_name ?? 'N/A',
            'venue_name' => $timetable->venue->name ?? 'N/A',
            'venue_capacity' => $timetable->venue->capacity ?? 'N/A',
            'group_selection' => $isAllGroups ? 'All Groups' : $groupSelection,
            'lecturers' => $timetable->lecturers->pluck('name')->toArray(),
            'faculty_name' => $timetable->faculty->name ?? 'N/A',
        ]);
    }
    public function getFacultyByProgramYear($programId, $yearNum)
    {
        $faculty = Faculty::where('program_id', $programId)
            ->where('name', 'LIKE', "% {$yearNum}")
            ->first();

        if (!$faculty) {
            return response()->json(['id' => null, 'name' => 'Invalid year']);
        }

        return response()->json([
            'id' => $faculty->id,
            'name' => $faculty->name,
        ]);
    }

    public function getFacultyCourses(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $courses = Course::whereHas('faculties', fn($q) => $q->where('faculties.id', $facultyId))
            ->select('course_code', 'name')
            ->get()
            ->map(function ($course) {
                return ['course_code' => $course->course_code, 'name' => $course->name];
            })
            ->toArray();
        return response()->json(['course_codes' => $courses]);
    }

    public function getFacultyGroups(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $groups = FacultyGroup::where('faculty_id', $facultyId)
            ->select('group_name', 'student_count')
            ->get()
            ->toArray();
        return response()->json(['groups' => $groups]);
    }

    public function getCourseLecturers(Request $request)
    {
        $courseCode = $request->query('course_code');
        $timetableId = $request->query('timetable_id', null);
        $course = Course::where('course_code', $courseCode)->first();
        if (!$course) {
            Log::warning('Course not found:', ['course_code' => $courseCode]);
            return response()->json(['lecturers' => []]);
        }
        $lecturers = $course->lecturers()->select('users.id', 'users.name')->get()->toArray();
        if ($timetableId) {
            $timetable = ExaminationTimetable::with('lecturers')->find($timetableId);
            $assignedLecturerIds = $timetable ? $timetable->lecturers->pluck('id')->toArray() : [];
            $lecturers = array_map(function ($lecturer) use ($assignedLecturerIds) {
                $lecturer['selected'] = in_array($lecturer['id'], $assignedLecturerIds);
                return $lecturer;
            }, $lecturers);
        }
        Log::info('Course lecturers:', ['course_code' => $courseCode, 'lecturers' => $lecturers]);
        return response()->json(['lecturers' => $lecturers]);
    }

    private function getValidDates(ExamSetup $setup): array
    {
        $startDate = Carbon::parse($setup->start_date);
        $endDate = Carbon::parse($setup->end_date);
        $includeWeekends = $setup->include_weekends;
        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            if ($includeWeekends || !in_array($currentDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $dates[] = $currentDate->toDateString();
            }
            $currentDate->addDay();
        }
        return $dates;
    }

    public function generatePdf(Request $request)
    {
        $selectedType = $request->query('exam_type');
        $setup = ExamSetup::whereJsonContains('type', $selectedType)->first();
        if (!$setup) {
            return back()->with('error', 'No setup found for the selected type.');
        }

        $days = $this->getValidDates($setup);
        $timeSlots = $setup->time_slots;
        $programs = Program::whereIn('id', $setup->programs)->get();
        $timetables = ExaminationTimetable::with(['faculty', 'venue', 'lecturers'])
            ->whereIn('exam_date', $days)
            ->whereIn('faculty_id', Faculty::whereIn('program_id', $setup->programs)->pluck('id'))
            ->get();

        $dateChunks = array_chunk($days, 5); // 5 dates per table for readability

        // Sanitize the academic year by replacing '/' with '-'
        $sanitizedAcademicYear = str_replace('/', '-', $setup->academic_year);
        $filename = 'examination_timetable_' . $sanitizedAcademicYear . '_draft_' . ($setup->draft_number ?? 1) . '.pdf';

        $pdf = Pdf::loadView('timetables.pdf', compact('setup', 'dateChunks', 'timeSlots', 'programs', 'timetables', 'selectedType'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }


   private function checkConflicts(Request $request, array $validated, ?int $excludeId = null): array
{
    Log::info('Checking conflicts', [
        'excludeId' => $excludeId,
        'validated' => $validated
    ]);

    $conflicts = [];

    // 1. Lecturer Conflict Check
    if (!empty($validated['lecturer_ids'])) {
        $lecturerConflict = ExaminationTimetable::where('exam_date', $validated['exam_date'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->whereHas('lecturers', fn($q) => $q->whereIn('users.id', $validated['lecturer_ids']))
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->with(['lecturers' => fn($q) => $q->whereIn('users.id', $validated['lecturer_ids'])])
            ->first();
        if ($lecturerConflict) {
            $conflictingLecturers = $lecturerConflict->lecturers->pluck('name')->toArray();
            $conflicts[] = sprintf(
                'Lecturer(s) %s are assigned to exam %s for %s from %s to %s.',
                implode(', ', $conflictingLecturers),
                $lecturerConflict->course_code,
                $lecturerConflict->faculty->name,
                $lecturerConflict->start_time,
                $lecturerConflict->end_time
            );
        }
    }

    // 2. Venue Conflict Check
    $venueConflict = ExaminationTimetable::where('exam_date', $validated['exam_date'])
        ->where('venue_id', $validated['venue_id'])
        ->where(function ($query) use ($validated) {
            $query->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                  ->where('end_time', '>', $validated['start_time']);
            });
        })
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->first();
    if ($venueConflict) {
        $conflicts[] = sprintf(
            'Venue %s is in use for exam %s for %s from %s to %s.',
            $venueConflict->venue->name,
            $venueConflict->course_code,
            $venueConflict->faculty->name,
            $venueConflict->start_time,
            $venueConflict->end_time
        );
    }

    // 3. Group Conflict Check
    $groups = $validated['group_selection'];
    foreach ($groups as $group) {
        $groupConflict = ExaminationTimetable::where('exam_date', $validated['exam_date'])
            ->where('group_selection', 'like', "%$group%")
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();
        if ($groupConflict) {
            $conflicts[] = sprintf(
                'Group %s has an exam for %s from %s to %s.',
                trim(str_replace('Group', '', $group)), // Remove "Group" prefix
                $groupConflict->course_code,
                $groupConflict->start_time,
                $groupConflict->end_time
            );
        }
    }

    // 4. Course Conflict Check
    $courseConflict = ExaminationTimetable::where('exam_date', $validated['exam_date'])
        ->where('course_code', $validated['course_code'])
        ->where('faculty_id', $validated['faculty_id'])
        ->where(function ($query) use ($validated) {
            $query->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                  ->where('end_time', '>', $validated['start_time']);
            });
        })
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->first();
    if ($courseConflict) {
        $conflicts[] = sprintf(
            'Course %s is already scheduled for %s from %s to %s.',
            $validated['course_code'],
            $courseConflict->faculty->name,
            $courseConflict->start_time,
            $courseConflict->end_time
        );
    }

    return $conflicts;
}
}

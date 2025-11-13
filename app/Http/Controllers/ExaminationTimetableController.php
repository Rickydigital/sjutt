<?php

namespace App\Http\Controllers;

use App\Models\ExaminationTimetable;
use App\Models\ExamSetup;
use App\Models\Faculty;
use App\Models\Venue;
use App\Models\Course;
use App\Models\Program;
use App\Models\FacultyGroup;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
            'venue_id' => 'required|string|regex:/^(\d+)(,\d+)*$/',
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
            if (empty($groups)) {
                Log::error('No groups found for faculty', ['faculty_id' => $validated['faculty_id']]);
                return $request->ajax()
                    ? response()->json(['errors' => ['group_selection' => 'No groups defined for this faculty']], 422)
                    : back()->withInput()->withErrors(['group_selection' => 'No groups defined for this faculty']);
            }
            $validated['group_selection'] = $groups;
        }

        // Extract start_time and end_time from time_slot if not directly provided
        if ($timeSlot && isset($timeSlot['start_time']) && isset($timeSlot['end_time'])) {
            $validated['start_time'] = $timeSlot['start_time'];
            $validated['end_time'] = $timeSlot['end_time'];
        }

        // Calculate student count
        $faculty = Faculty::findOrFail($validated['faculty_id']);
        $studentCount = $this->calculateStudentCount($faculty, implode(',', $validated['group_selection']));
        if ($studentCount === 0) {
            Log::error('No students found for selected groups', [
                'faculty_id' => $faculty->id,
                'group_selection' => $validated['group_selection']
            ]);
            return $request->ajax()
                ? response()->json(['errors' => ['group_selection' => 'No students found for the selected groups']], 422)
                : back()->withInput()->withErrors(['group_selection' => 'No students found for the selected groups']);
        }

        // Calculate venue capacity
        $venueIds = explode(',', $validated['venue_id']);
        $venues = Venue::whereIn('id', $venueIds)->get();
        $totalVenueCapacity = $venues->sum('capacity') * 0.75;

        Log::info('Venue capacity check', [
            'venue_ids' => $venueIds,
            'total_capacity' => round($totalVenueCapacity),
            'student_count' => $studentCount
        ]);

        if ($totalVenueCapacity < $studentCount) {
            $errorMessage = "Total venue capacity (" . round($totalVenueCapacity) . ") is less than required students ($studentCount).";
            Log::error('Insufficient venue capacity', [
                'venue_ids' => $venueIds,
                'total_capacity' => round($totalVenueCapacity),
                'student_count' => $studentCount
            ]);
            return $request->ajax()
                ? response()->json(['errors' => ['venue_id' => $errorMessage]], 422)
                : back()->withInput()->withErrors(['venue_id' => $errorMessage]);
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
            DB::beginTransaction();
            $validated['group_selection'] = implode(',', $validated['group_selection']);
            $validated['venue_id'] = $venueIds[0];
            $timetable = ExaminationTimetable::create($validated);
            $timetable->lecturers()->sync($validated['lecturer_ids']);
            if (count($venueIds) > 1) {
                foreach (array_slice($venueIds, 1) as $additionalVenueId) {
                    $additionalTimetable = $timetable->replicate()->fill(['venue_id' => $additionalVenueId]);
                    $additionalTimetable->save();
                    $additionalTimetable->lecturers()->sync($validated['lecturer_ids']);
                }
            }
            DB::commit();
            Log::info('Exam timetable created successfully:', [
                'id' => $timetable->id,
                'student_count' => $studentCount,
                'total_venue_capacity' => round($totalVenueCapacity)
            ]);
            return $request->ajax()
                ? response()->json(['message' => 'Exam timetable created successfully.', 'id' => $timetable->id])
                : redirect()->route('timetables.index')->with('success', 'Exam timetable created successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
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
            DB::rollBack();
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
                'venue_id' => 'required|string|regex:/^(\d+)(,\d+)*$/',
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
                if (empty($groups)) {
                    Log::error('No groups found for faculty', ['faculty_id' => $validated['faculty_id']]);
                    return response()->json(['errors' => ['group_selection' => 'No groups defined for this faculty']], 422);
                }
                $validated['group_selection'] = $groups;
            }

            // Extract start_time and end_time from time_slot if not directly provided
            if ($timeSlot && isset($timeSlot['start_time'], $timeSlot['end_time'])) {
                $validated['start_time'] = Carbon::createFromFormat('H:i', $timeSlot['start_time'])->format('H:i');
                $validated['end_time'] = Carbon::createFromFormat('H:i', $timeSlot['end_time'])->format('H:i');
            }

            // Calculate student count
            $faculty = Faculty::findOrFail($validated['faculty_id']);
            $studentCount = $this->calculateStudentCount($faculty, implode(',', $validated['group_selection']));
            if ($studentCount === 0) {
                Log::error('No students found for selected groups', [
                    'faculty_id' => $faculty->id,
                    'group_selection' => $validated['group_selection']
                ]);
                return response()->json(['errors' => ['group_selection' => 'No students found for the selected groups']], 422);
            }

            // Calculate venue capacity
            $venueIds = explode(',', $validated['venue_id']);
            $venues = Venue::whereIn('id', $venueIds)->get();
            $totalVenueCapacity = $venues->sum('capacity') * 0.75;

            Log::info('Venue capacity check', [
                'venue_ids' => $venueIds,
                'total_capacity' => round($totalVenueCapacity),
                'student_count' => $studentCount
            ]);

            if ($totalVenueCapacity < $studentCount) {
                $errorMessage = "Total venue capacity (" . round($totalVenueCapacity) . ") is less than required students ($studentCount).";
                Log::error('Insufficient venue capacity', [
                    'venue_ids' => $venueIds,
                    'total_capacity' => round($totalVenueCapacity),
                    'student_count' => $studentCount
                ]);
                return response()->json(['errors' => ['venue_id' => $errorMessage]], 422);
            }

            // Check for conflicts, excluding the current timetable
            $conflicts = $this->checkConflicts($request, $validated, $timetable->id);
            Log::info('Conflict check result:', ['conflicts' => $conflicts]);
            if (!empty($conflicts)) {
                $errorMessage = implode(' ', $conflicts);
                return response()->json(['errors' => ['conflict' => $errorMessage]], 422);
            }

            DB::beginTransaction();
            try {
                $validated['group_selection'] = implode(',', $validated['group_selection']);
                $validated['venue_id'] = $venueIds[0];
                $timetable->update($validated);
                $timetable->lecturers()->sync($validated['lecturer_ids']);
                $existingAdditional = ExaminationTimetable::where('exam_date', $timetable->exam_date)
                    ->where('course_code', $timetable->course_code)
                    ->where('id', '!=', $timetable->id)
                    ->get();
                $existingAdditional->each->delete();
                if (count($venueIds) > 1) {
                    foreach (array_slice($venueIds, 1) as $additionalVenueId) {
                        $additionalTimetable = $timetable->replicate()->fill(['venue_id' => $additionalVenueId]);
                        $additionalTimetable->save();
                        $additionalTimetable->lecturers()->sync($validated['lecturer_ids']);
                    }
                }
                DB::commit();
                Log::info('Exam timetable updated successfully:', [
                    'id' => $timetable->id,
                    'student_count' => $studentCount,
                    'total_venue_capacity' => round($totalVenueCapacity)
                ]);
                return response()->json(['success' => 'Exam timetable updated successfully']);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Update failed:', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Update failed', 'details' => $e->getMessage()], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors()]);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error in update:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Update failed', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy(ExaminationTimetable $timetable)
    {
        DB::beginTransaction();
        try {
            // Delete related timetables for the same course and date (for multi-venue cases)
            ExaminationTimetable::where('exam_date', $timetable->exam_date)
                ->where('course_code', $timetable->course_code)
                ->delete();
            DB::commit();
            return redirect()->route('timetables.index')->with('success', 'Exam timetable deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete failed:', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete timetable: ' . $e->getMessage()]);
        }
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

        // Get additional venues for the same course and date
        $additionalVenues = ExaminationTimetable::where('exam_date', $timetable->exam_date)
            ->where('course_code', $timetable->course_code)
            ->where('id', '!=', $timetable->id)
            ->with('venue')
            ->get()
            ->pluck('venue.name')
            ->toArray();

        return response()->json([
            'course_code' => $timetable->course_code,
            'course_name' => $course ? $course->name : 'N/A',
            'exam_date' => $timetable->exam_date,
            'start_time' => $timetable->start_time,
            'end_time' => $timetable->end_time,
            'time_slot_name' => $timetable->time_slot_name ?? 'N/A',
            'venue_name' => $timetable->venue->name ?? 'N/A',
            'additional_venues' => $additionalVenues,
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
        $dates = [];
        $currentDate = strtotime($setup->start_date);
        $endDate = strtotime($setup->end_date);

        while ($currentDate <= $endDate) {
            $dates[] = date('Y-m-d', $currentDate);
            $currentDate = strtotime('+1 day', $currentDate);
        }

        return $dates;
    }

    private function calculateStudentCount($faculty, $groupSelection)
    {
        if (!$faculty) {
            Log::warning('Invalid faculty provided for student count calculation', [
                'group_selection' => $groupSelection
            ]);
            return 0;
        }

        Log::info('Calculating student count for faculty', [
            'faculty_id' => $faculty->id,
            'group_selection' => $groupSelection,
            'total_students_no' => $faculty->total_students_no
        ]);

        // Use total_students_no from Faculty model
        $studentCount = $faculty->total_students_no ?? 0;

        Log::info('Calculated student count', [
            'faculty_id' => $faculty->id,
            'student_count' => $studentCount
        ]);

        return $studentCount;
    }

    public function generateTimetable(Request $request)
    {
        Log::info('Generate timetable request:', $request->all());

        try {
            $validated = $request->validate([
                'exam_setup_id' => 'required|exists:exam_setups,id',
            ]);

            $setup = ExamSetup::findOrFail($validated['exam_setup_id']);
            $faculties = Faculty::whereIn('program_id', $setup->programs)->get();
            $days = $this->getValidDates($setup);
            $timeSlots = collect($setup->time_slots)->map(function ($slot) {
                return ['start_time' => $slot['start_time'], 'end_time' => $slot['end_time']];
            })->toArray();
            $venues = Venue::select('id', 'name', 'capacity')->get();

            // Build course groups automatically
            $courseGroups = [];
            foreach ($faculties as $faculty) {
                $courses = Course::whereHas('faculties', function ($q) use ($faculty) {
                    $q->where('faculties.id', $faculty->id);
                })->get();

                foreach ($courses as $course) {
                    $lecturerIds = $course->lecturers()->pluck('id')->toArray();
                    $groups = FacultyGroup::where('faculty_id', $faculty->id)->pluck('group_name')->toArray();
                    if (empty($groups)) {
                        Log::warning('No groups defined for faculty', [
                            'faculty_id' => $faculty->id,
                            'course_code' => $course->course_code
                        ]);
                        continue;
                    }
                    $studentCount = $this->calculateStudentCount($faculty, implode(',', $groups));
                    if ($studentCount === 0) {
                        Log::warning('No students found for faculty', [
                            'faculty_id' => $faculty->id,
                            'course_code' => $course->course_code,
                            'groups' => $groups
                        ]);
                        continue;
                    }
                    $courseGroups[$course->course_code][] = [
                        'course_code' => $course->course_code,
                        'course_name' => $course->name,
                        'faculty_id' => $faculty->id,
                        'lecturer_ids' => $lecturerIds,
                        'group_selection' => $groups,
                        'student_count' => $studentCount,
                    ];
                }
            }

            $sessions = [];
            foreach ($courseGroups as $courseCode => $courseFaculties) {
                $totalStudentCount = array_sum(array_column($courseFaculties, 'student_count'));
                $sessions[] = [
                    'course_code' => $courseCode,
                    'faculties' => $courseFaculties,
                    'total_student_count' => $totalStudentCount,
                ];
            }

            $result = $this->scheduleTimetable($sessions, $days, $timeSlots, $venues, $setup, $request);

            if (empty($result['timetables'])) {
                Log::warning('No timetables generated due to scheduling conflicts or insufficient resources.', [
                    'errors' => $result['errors'] ?? []
                ]);
                return response()->json([
                    'errors' => [
                        'scheduling' => $result['errors'] ? implode(' ', $result['errors']) : 'Unable to generate a conflict-free timetable. Try adjusting venues or lecturers.'
                    ]
                ], 422);
            }

            DB::beginTransaction();
            try {
                $createdTimetables = [];
                $warnings = $result['warnings'] ?? [];
                foreach ($result['timetables'] as $timetable) {
                    $created = ExaminationTimetable::create([
                        'exam_date' => $timetable['exam_date'],
                        'start_time' => $timetable['start_time'],
                        'end_time' => $timetable['end_time'],
                        'course_code' => $timetable['course_code'],
                        'course_name' => $timetable['course_name'],
                        'venue_id' => $timetable['venue_id'],
                        'group_selection' => $timetable['group_selection'],
                        'faculty_id' => $timetable['faculty_id'],
                    ]);
                    $created->lecturers()->sync($timetable['lecturer_ids']);
                    $createdTimetables[] = $created;

                    // Log capacity and student count
                    $faculty = Faculty::find($timetable['faculty_id']);
                    $studentCount = $timetable['student_count'];
                    $venue = Venue::find($timetable['venue_id']);
                    Log::info('Timetable entry created', [
                        'timetable_id' => $created->id,
                        'course_code' => $timetable['course_code'],
                        'faculty_id' => $timetable['faculty_id'],
                        'student_count' => $studentCount,
                        'venue_id' => $timetable['venue_id'],
                        'venue_capacity' => $venue ? floor($venue->capacity * 0.75) : 'N/A'
                    ]);
                }
                DB::commit();
                Log::info('Timetable generated successfully:', ['timetables' => $createdTimetables]);

                if (!empty($warnings) && !$request->has('force_proceed')) {
                    return response()->json([
                        'message' => 'Timetable generated with warnings.',
                        'timetables' => $createdTimetables,
                        'warnings' => $warnings,
                        'proceed' => true
                    ], 422);
                }

                return response()->json([
                    'message' => 'Timetable generated successfully.',
                    'timetables' => $createdTimetables
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error saving generated timetable:', ['error' => $e->getMessage()]);
                return response()->json(['errors' => ['database' => 'Failed to save timetable: ' . $e->getMessage()]], 422);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error in generateTimetable:', ['error' => $e->getMessage()]);
            return response()->json(['errors' => ['error' => 'Unexpected error: ' . $e->getMessage()]], 422);
        }
    }

    private function scheduleTimetable(array $sessions, array $days, array $timeSlots, $venues, ExamSetup $setup, Request $request): array
    {
        $timetables = [];
        $warnings = [];
        $errors = [];
        $usedSlots = [];
        $lecturerSlots = [];
        $groupSlots = [];
        $courseSlots = [];

        shuffle($sessions);

        foreach ($sessions as $session) {
            $courseCode = $session['course_code'];
            $totalStudentCount = $session['total_student_count'];
            $faculties = $session['faculties'];
            $scheduled = false;
            $sessionConflicts = [];

            Log::info('Scheduling session for course:', [
                'course_code' => $courseCode,
                'total_student_count' => $totalStudentCount,
                'faculties' => array_column($faculties, 'faculty_id')
            ]);

            foreach ($days as $day) {
                foreach ($timeSlots as $timeSlot) {
                    $startTime = $timeSlot['start_time'];
                    $endTime = $timeSlot['end_time'];

                    // Filter venues for the specific day and time slot
                    $availableVenues = $venues->filter(function ($venue) use ($usedSlots, $day, $startTime) {
                        return !isset($usedSlots[$day][$startTime][$venue->id]);
                    })->sortByDesc('capacity')->values();

                    // Assign venues for the entire course
                    $courseVenueAssignments = $this->assignVenues($totalStudentCount, $availableVenues);
                    if (empty($courseVenueAssignments)) {
                        $sessionConflicts[] = "No suitable venues available for {$courseCode} with {$totalStudentCount} students.";
                        $errors[] = implode(' ', $sessionConflicts);
                        Log::error('Venue assignment failed for course', [
                            'course_code' => $courseCode,
                            'student_count' => $totalStudentCount,
                            'available_venues' => $availableVenues->pluck('name')->toArray()
                        ]);
                        continue;
                    }

                    Log::info('Venue assignments for course', [
                        'course_code' => $courseCode,
                        'student_count' => $totalStudentCount,
                        'venues' => array_map(fn($v) => [
                            'id' => $v->id,
                            'name' => $v->name,
                            'raw_capacity' => $v->raw_capacity,
                            'effective_capacity' => $v->capacity,
                            'students_assigned' => $v->students_assigned
                        ], $courseVenueAssignments)
                    ]);

                    // Distribute venues among faculties
                    $facultyVenueAssignments = [];
                    $remainingVenues = $courseVenueAssignments;
                    foreach ($faculties as $faculty) {
                        $studentCount = $this->calculateStudentCount(Faculty::find($faculty['faculty_id']), implode(',', $faculty['group_selection']));
                        // Use all course-assigned venues for each faculty to avoid premature removal
                        $facultyVenues = $this->assignVenues($studentCount, collect($courseVenueAssignments));
                        if (empty($facultyVenues)) {
                            $sessionConflicts[] = "Insufficient venue capacity for faculty ID {$faculty['faculty_id']} with $studentCount students.";
                            $errors[] = implode(' ', $sessionConflicts);
                            Log::error('Insufficient venue capacity for faculty', [
                                'faculty_id' => $faculty['faculty_id'],
                                'student_count' => $studentCount,
                                'available_venues' => array_map(fn($v) => [
                                    'id' => $v->id,
                                    'name' => $v->name,
                                    'raw_capacity' => $v->raw_capacity,
                                    'effective_capacity' => $v->capacity
                                ], $courseVenueAssignments)
                            ]);
                            $scheduled = false;
                            break;
                        }
                        $facultyVenueAssignments[$faculty['faculty_id']] = $facultyVenues;
                    }

                    if (!empty($sessionConflicts)) {
                        continue;
                    }

                    // Check if the course is already scheduled
                    if (isset($courseSlots[$courseCode][$day][$startTime])) {
                        $sessionConflicts[] = "Course {$courseCode} is already scheduled on {$day} at {$startTime}.";
                        continue;
                    }

                    // Check venue availability
                    $venuesAvailable = true;
                    foreach ($courseVenueAssignments as $venue) {
                        if (isset($usedSlots[$day][$startTime][$venue->id])) {
                            $sessionConflicts[] = "Venue {$venue->name} is unavailable on {$day} at {$startTime}.";
                            $venuesAvailable = false;
                            break;
                        }
                    }
                    if (!$venuesAvailable) {
                        continue;
                    }

                    // Check lecturer availability for each faculty
                    $allLecturersAvailable = true;
                    foreach ($faculties as $faculty) {
                        foreach ($faculty['lecturer_ids'] as $lecturerId) {
                            if (isset($lecturerSlots[$lecturerId][$day][$startTime])) {
                                $sessionConflicts[] = "Lecturer ID {$lecturerId} is unavailable on {$day} at {$startTime}.";
                                $allLecturersAvailable = false;
                                break;
                            }
                        }
                        if (!$allLecturersAvailable) {
                            break;
                        }
                    }
                    if (!$allLecturersAvailable) {
                        continue;
                    }

                    // Check group availability for each faculty
                    $allGroupsAvailable = true;
                    foreach ($faculties as $faculty) {
                        $groups = is_array($faculty['group_selection']) ? $faculty['group_selection'] : explode(',', $faculty['group_selection']);
                        if (empty($groups)) {
                            Log::warning('Empty group selection for faculty', [
                                'faculty_id' => $faculty['faculty_id'],
                                'course_code' => $courseCode
                            ]);
                            $sessionConflicts[] = "No groups defined for faculty ID {$faculty['faculty_id']} in course {$courseCode}.";
                            $allGroupsAvailable = false;
                            break;
                        }
                        Log::info('Groups for faculty', ['faculty_id' => $faculty['faculty_id'], 'groups' => $groups, 'type' => gettype($groups)]);
                        /*
                        foreach ($groups as $group) {
                            if (isset($groupSlots[$group][$day][$startTime])) {
                                $sessionConflicts[] = "Group {$group} is unavailable on {$day} at {$startTime}.";
                                $allGroupsAvailable = false;
                                break;
                            }
                        }
                        if (!$allGroupsAvailable) {
                            break;
                        }
                        */
                    }
                    /*
                    if (!$allGroupsAvailable) {
                        continue;
                    }
                    */

                    // Validate with conflict checker
                    $hasConflict = false;
                    foreach ($faculties as $faculty) {
                        $facultyVenues = $facultyVenueAssignments[$faculty['faculty_id']];
                        if (empty($facultyVenues)) {
                            $sessionConflicts[] = "No venues assigned for faculty ID {$faculty['faculty_id']}.";
                            $hasConflict = true;
                            break;
                        }
                        $venueIds = array_column($facultyVenues, 'id');
                        $groupSelection = is_array($faculty['group_selection']) ? $faculty['group_selection'] : explode(',', $faculty['group_selection']);
                        Log::info('Group selection for conflict check', ['faculty_id' => $faculty['faculty_id'], 'group_selection' => $groupSelection, 'type' => gettype($groupSelection)]);

                        $timetableData = [
                            'exam_date' => $day,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'course_code' => $courseCode,
                            'course_name' => $faculty['course_name'],
                            'venue_id' => $venueIds,
                            'lecturer_ids' => $faculty['lecturer_ids'],
                            'group_selection' => $groupSelection,
                            'faculty_id' => $faculty['faculty_id'],
                        ];

                        $conflicts = $this->checkConflicts(new Request($timetableData), $timetableData);
                        if (!empty($conflicts)) {
                            $sessionConflicts = array_merge($sessionConflicts, $conflicts);
                            $hasConflict = true;
                            break;
                        }

                        // Create timetable entries for each venue
                        foreach ($facultyVenues as $venue) {
                            $timetables[] = [
                                'exam_date' => $day,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'course_code' => $courseCode,
                                'course_name' => $faculty['course_name'],
                                'venue_id' => $venue->id,
                                'lecturer_ids' => $faculty['lecturer_ids'],
                                'group_selection' => implode(',', $groupSelection),
                                'faculty_id' => $faculty['faculty_id'],
                                'student_count' => $venue->students_assigned
                            ];
                        }
                    }

                    if ($hasConflict) {
                        continue;
                    }

                    // Update tracking arrays
                    foreach ($faculties as $faculty) {
                        $facultyVenues = $facultyVenueAssignments[$faculty['faculty_id']];
                        foreach ($facultyVenues as $venue) {
                            $usedSlots[$day][$startTime][$venue->id] = true;
                        }
                        foreach ($faculty['lecturer_ids'] as $lecturerId) {
                            $lecturerSlots[$lecturerId][$day][$startTime] = true;
                        }
                        $groups = is_array($faculty['group_selection']) ? $faculty['group_selection'] : explode(',', $faculty['group_selection']);
                        Log::info('Groups for tracking', ['faculty_id' => $faculty['faculty_id'], 'groups' => $groups, 'type' => gettype($groups)]);
                        /*
                        foreach ($groups as $group) {
                            $groupSlots[$group][$day][$startTime] = true;
                        }
                        */
                        $courseSlots[$courseCode][$day][$startTime] = true;
                    }

                    $scheduled = true;
                    Log::info('Session scheduled successfully:', [
                        'course_code' => $courseCode,
                        'exam_date' => $day,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'venues' => array_column($courseVenueAssignments, 'name'),
                        'student_count' => $totalStudentCount,
                        'total_venue_capacity' => array_sum(array_map(fn($v) => $v->capacity, $courseVenueAssignments)),
                        'faculty_venue_assignments' => array_map(fn($fid, $venues) => [
                            'faculty_id' => $fid,
                            'venues' => array_map(fn($v) => [
                                'id' => $v->id,
                                'name' => $v->name,
                                'raw_capacity' => $v->raw_capacity,
                                'effective_capacity' => $v->capacity,
                                'students_assigned' => $v->students_assigned
                            ], $venues)
                        ], array_keys($facultyVenueAssignments), $facultyVenueAssignments)
                    ]);
                    break;
                }
                if ($scheduled) {
                    break;
                }
            }

            if (!$scheduled) {
                Log::info('Could not schedule session:', [
                    'session' => $session,
                    'conflicts' => array_unique($sessionConflicts)
                ]);
                $errors[] = "Cannot schedule {$courseCode}: " . implode(' ', array_unique($sessionConflicts));
            }
        }

        return [
            'timetables' => $timetables,
            'warnings' => $warnings,
            'errors' => $errors
        ];
    }

    private function assignVenues(int $studentCount, $availableVenues): array
    {
        $sortedVenues = $availableVenues->sortByDesc('capacity')->values();
        $selectedVenues = [];
        $remainingStudents = $studentCount;

        Log::info('Assigning venues for student count', ['student_count' => $studentCount]);

        foreach ($sortedVenues as $venue) {
            $effectiveCapacity = floor($venue->capacity * 0.75);
            if ($effectiveCapacity > 0) {
                $studentsToAssign = min($remainingStudents, $effectiveCapacity);
                $selectedVenues[] = (object) [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'capacity' => $effectiveCapacity,
                    'raw_capacity' => $venue->capacity,
                    'students_assigned' => $studentsToAssign
                ];
                $remainingStudents -= $studentsToAssign;
            }

            if ($remainingStudents <= 0) {
                break;
            }
        }

        if ($remainingStudents > 0) {
            Log::error('Insufficient venue capacity', [
                'student_count' => $studentCount,
                'remaining_students' => $remainingStudents,
                'selected_venues' => array_map(fn($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'raw_capacity' => $v->raw_capacity,
                    'effective_capacity' => $v->capacity,
                    'students_assigned' => $v->students_assigned
                ], $selectedVenues)
            ]);
            return [];
        }

        Log::info('Venue assignments completed', [
            'student_count' => $studentCount,
            'venues' => array_map(fn($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'raw_capacity' => $v->raw_capacity,
                'effective_capacity' => $v->capacity,
                'students_assigned' => $v->students_assigned
            ], $selectedVenues)
        ]);

        return $selectedVenues;
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

        // Calculate student count using total_students_no
        $faculty = Faculty::find($validated['faculty_id']);
        $studentCount = $this->calculateStudentCount($faculty, is_array($validated['group_selection']) ? implode(',', $validated['group_selection']) : $validated['group_selection']);
        
        // Validate venue capacity
        $venueIds = is_array($validated['venue_id']) ? $validated['venue_id'] : [$validated['venue_id']];
        $venues = Venue::whereIn('id', $venueIds)->get();
        $totalVenueCapacity = $venues->sum('capacity') * 0.75;

        Log::info('Conflict check capacity details', [
            'faculty_id' => $validated['faculty_id'],
            'student_count' => $studentCount,
            'venue_ids' => $venueIds,
            'total_venue_capacity' => round($totalVenueCapacity)
        ]);

        if ($totalVenueCapacity < $studentCount) {
            $conflicts[] = "Insufficient venue capacity (" . round($totalVenueCapacity) . ") for $studentCount students for faculty ID {$validated['faculty_id']}.";
            Log::error('Insufficient venue capacity in conflict check', [
                'faculty_id' => $validated['faculty_id'],
                'student_count' => $studentCount,
                'venue_ids' => $venueIds,
                'total_venue_capacity' => round($totalVenueCapacity)
            ]);
        }

        // Lecturer Conflict Check
        if (!empty($validated['lecturer_ids'])) {
            $lecturerConflict = ExaminationTimetable::where('exam_date', $validated['exam_date'])
                ->where(function ($query) use ($validated) {
                    $query->where('start_time', '<', $validated['end_time'])
                          ->where('end_time', '>', $validated['start_time']);
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

        // Venue Conflict Check
        foreach ($venueIds as $venueId) {
            $venueConflict = ExaminationTimetable::where('exam_date', $validated['exam_date'])
                ->where('venue_id', $venueId)
                ->where(function ($query) use ($validated) {
                    $query->where('start_time', '<', $validated['end_time'])
                          ->where('end_time', '>', $validated['start_time']);
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
        }

        // Group Conflict Check
        $groups = is_array($validated['group_selection']) ? $validated['group_selection'] : explode(',', $validated['group_selection']);
        if (empty($groups)) {
            $conflicts[] = "No groups defined for faculty ID {$validated['faculty_id']}.";
            Log::warning('Empty group selection in conflict check', [
                'faculty_id' => $validated['faculty_id'],
                'course_code' => $validated['course_code']
            ]);
        }
        /*
        foreach ($groups as $group) {
            $groupConflict = ExaminationTimetable::where('exam_date', $validated['exam_date'])
                ->where('group_selection', 'like', "%$group%")
                ->where(function ($query) use ($validated) {
                    $query->where('start_time', '<', $validated['end_time'])
                          ->where('end_time', '>', $validated['start_time']);
                })
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();
            if ($groupConflict) {
                $conflicts[] = sprintf(
                    'Group %s has an exam for %s from %s to %s.',
                    trim(str_replace('Group', '', $group)),
                    $groupConflict->course_code,
                    $groupConflict->start_time,
                    $groupConflict->end_time
                );
            }
        }
        */

        // Course Conflict Check
        $courseConflict = ExaminationTimetable::where('exam_date', $validated['exam_date'])
            ->where('course_code', $validated['course_code'])
            ->where('faculty_id', $validated['faculty_id'])
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
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
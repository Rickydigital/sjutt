<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\Faculty;
use App\Models\Venue;
use App\Models\Course;
use App\Models\User;
use App\Models\FacultyGroup;
use App\Imports\TimetableImport;
use App\Exports\TimetableExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $facultyId = $request->input('faculty');
        $timetables = $facultyId ? Timetable::where('faculty_id', $facultyId)->with('faculty', 'venue', 'lecturer')->get() : collect();
        $faculties = Faculty::pluck('name', 'id');
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00'];
        $venues = Venue::select('id', 'name', 'capacity')->get();

        return view('timetable.index', compact('timetables', 'faculties', 'days', 'timeSlots', 'facultyId', 'venues'));
    }

    public function store(Request $request)
    {
        Log::info('Store request data:', $request->all());

        try {
            $validated = $request->validate([
                'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'faculty_id' => 'required|exists:faculties,id',
                'time_start' => 'required|date_format:H:i',
                'time_end' => 'required|date_format:H:i|after:time_start',
                'course_code' => 'required|string|exists:courses,course_code',
                'activity' => 'required|string|max:255',
                'venue_id' => 'required|exists:venues,id',
                'lecturer_id' => 'required|exists:users,id',
                'group_selection' => 'required|array|min:1',
                'group_selection.*' => 'string'
            ]);
            Log::info('Validation passed:', $validated);

            $faculty = Faculty::findOrFail($validated['faculty_id']);
            $venue = Venue::findOrFail($validated['venue_id']);
            $course = Course::where('course_code', $validated['course_code'])->firstOrFail();

            $validated['group_selection'] = implode(',', $validated['group_selection']);
            $validated['course_name'] = $course->name ?? null;
            $validated['time_start'] = date('H:i:s', strtotime($validated['time_start']));
            $validated['time_end'] = date('H:i:s', strtotime($validated['time_end']));

            // Check for existing identical entry to prevent duplicates
            $existing = Timetable::where('faculty_id', $validated['faculty_id'])
                ->where('course_code', $validated['course_code'])
                ->where('day', $validated['day'])
                ->where('time_start', $validated['time_start'])
                ->where('time_end', $validated['time_end'])
                ->where('lecturer_id', $validated['lecturer_id'])
                ->where('venue_id', $validated['venue_id'])
                ->where('group_selection', $validated['group_selection'])
                ->exists();
            if ($existing) {
                Log::warning('Duplicate timetable entry detected', $validated);
                return $request->ajax()
                    ? response()->json(['errors' => ['duplicate' => 'This timetable entry already exists.']], 422)
                    : back()->withInput()->withErrors(['duplicate' => 'This timetable entry already exists.']);
            }

            $studentCount = $this->calculateStudentCount($faculty, $validated['group_selection']);
            Log::info('Student count calculated:', ['student_count' => $studentCount, 'venue_capacity' => $venue->capacity]);
            if ($venue->capacity < $studentCount) {
                Log::warning('Venue capacity insufficient:', [
                    'venue_capacity' => $venue->capacity,
                    'student_count' => $studentCount
                ]);
                return $request->ajax()
                    ? response()->json(['errors' => ['venue_id' => "Venue capacity ({$venue->capacity}) is less than required students ({$studentCount})."]], 422)
                    : back()->withInput()->withErrors(['venue_id' => "Venue capacity ({$venue->capacity}) is less than required students ({$studentCount})."]);
            }

            $conflicts = $this->checkConflicts($request, $validated);
            Log::info('Conflict check result:', ['conflicts' => $conflicts]);
            if (!empty($conflicts)) {
                Log::warning('Conflicts detected:', ['conflicts' => $conflicts]);
                $errorMessage = implode(' ', $conflicts);
                return $request->ajax()
                    ? response()->json(['errors' => ['conflict' => $errorMessage]], 422)
                    : back()->withInput()->withErrors(['conflict' => $errorMessage]);
            }

            DB::enableQueryLog();
            DB::beginTransaction();
            try {
                $timetable = Timetable::create($validated);
                DB::commit();
                Log::info('Timetable created successfully:', [
                    'id' => $timetable->id,
                    'query_log' => DB::getQueryLog()
                ]);
                return $request->ajax()
                    ? response()->json(['message' => 'Timetable entry created successfully.', 'id' => $timetable->id])
                    : redirect()->route('timetable.index')->with('success', 'Timetable entry created successfully.');
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                Log::error('Database error creating timetable: ' . $e->getMessage(), [
                    'exception' => $e,
                    'validated' => $validated,
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'query_log' => DB::getQueryLog()
                ]);
                return $request->ajax()
                    ? response()->json(['errors' => ['error' => 'Database error: ' . $e->getMessage()]], 422)
                    : back()->withInput()->withErrors(['error' => 'Database error: ' . $e->getMessage()]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors(), 'input' => $request->all()]);
            return $request->ajax()
                ? response()->json(['errors' => $e->errors()], 422)
                : back()->withInput()->withErrors($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Model not found: ' . $e->getMessage(), ['input' => $request->all()]);
            return $request->ajax()
                ? response()->json(['errors' => ['error' => 'Referenced record (faculty, venue, or course) not found.']], 422)
                : back()->withInput()->withErrors(['error' => 'Referenced record (faculty, venue, or course) not found.']);
        } catch (\Exception $e) {
            Log::error('Unexpected error in store: ' . $e->getMessage(), [
                'exception' => $e,
                'input' => $request->all()
            ]);
            return $request->ajax()
                ? response()->json(['errors' => ['error' => 'Unexpected error: ' . $e->getMessage()]], 422)
                : back()->withInput()->withErrors(['error' => 'Unexpected error: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, Timetable $timetable)
    {
        Log::info('Update request data:', $request->all());

        try {
            $validated = $request->validate([
                'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'faculty_id' => 'required|exists:faculties,id',
                'time_start' => 'required|date_format:H:i',
                'time_end' => 'required|date_format:H:i|after:time_start',
                'course_code' => 'required|string|exists:courses,course_code',
                'activity' => 'required|string|max:255',
                'venue_id' => 'required|exists:venues,id',
                'lecturer_id' => 'required|exists:users,id',
                'group_selection' => 'required|array|min:1',
                'group_selection.*' => 'string'
            ]);
            Log::info('Validation passed:', $validated);

            // Format times to HH:mm:ss for consistency with database
            $validated['time_start'] = date('H:i:s', strtotime($validated['time_start']));
            $validated['time_end'] = date('H:i:s', strtotime($validated['time_end']));

            $faculty = Faculty::findOrFail($validated['faculty_id']);
            $venue = Venue::findOrFail($validated['venue_id']);
            $course = Course::where('course_code', $validated['course_code'])->firstOrFail();

            $validated['group_selection'] = implode(',', $validated['group_selection']);
            $validated['course_name'] = $course->name ?? null;

            // Check if the updated details are identical to the current entry
            $current = [
                'day' => $timetable->day,
                'faculty_id' => $timetable->faculty_id,
                'time_start' => $timetable->time_start,
                'time_end' => $timetable->time_end,
                'course_code' => $timetable->course_code,
                'activity' => $timetable->activity,
                'venue_id' => $timetable->venue_id,
                'lecturer_id' => $timetable->lecturer_id,
                'group_selection' => $timetable->group_selection
            ];
            $new = [
                'day' => $validated['day'],
                'faculty_id' => $validated['faculty_id'],
                'time_start' => $validated['time_start'],
                'time_end' => $validated['time_end'],
                'course_code' => $validated['course_code'],
                'activity' => $validated['activity'],
                'venue_id' => $validated['venue_id'],
                'lecturer_id' => $validated['lecturer_id'],
                'group_selection' => $validated['group_selection']
            ];

            if ($current === $new) {
                Log::info('No changes detected in timetable entry', ['timetable_id' => $timetable->id]);
                return $request->ajax()
                    ? response()->json(['message' => 'No changes made to timetable entry.', 'id' => $timetable->id])
                    : redirect()->route('timetable.index')->with('success', 'No changes made to timetable entry.');
            }

            $studentCount = $this->calculateStudentCount($faculty, $validated['group_selection']);
            Log::info('Student count calculated:', ['student_count' => $studentCount, 'venue_capacity' => $venue->capacity]);
            if ($venue->capacity < $studentCount) {
                Log::warning('Venue capacity insufficient:', [
                    'venue_capacity' => $venue->capacity,
                    'student_count' => $studentCount
                ]);
                return $request->ajax()
                    ? response()->json(['errors' => ['venue_id' => "Venue capacity ({$venue->capacity}) is less than required students ({$studentCount})."]], 422)
                    : back()->withInput()->withErrors(['venue_id' => "Venue capacity ({$venue->capacity}) is less than required students ({$studentCount})."]);
            }

            $conflicts = $this->checkConflicts($request, $validated, $timetable->id);
            Log::info('Conflict check result:', ['conflicts' => $conflicts]);
            if (!empty($conflicts)) {
                Log::warning('Conflicts detected:', ['conflicts' => $conflicts]);
                $errorMessage = implode(' ', $conflicts);
                return $request->ajax()
                    ? response()->json(['errors' => ['conflict' => $errorMessage]], 422)
                    : back()->withInput()->withErrors(['conflict' => $errorMessage]);
            }

            DB::enableQueryLog();
            DB::beginTransaction();
            try {
                $timetable->update($validated);
                DB::commit();
                Log::info('Timetable updated successfully:', [
                    'id' => $timetable->id,
                    'query_log' => DB::getQueryLog()
                ]);
                return $request->ajax()
                    ? response()->json(['message' => 'Timetable entry updated successfully.', 'id' => $timetable->id])
                    : redirect()->route('timetable.index')->with('success', 'Timetable entry updated successfully.');
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                Log::error('Database error updating timetable: ' . $e->getMessage(), [
                    'exception' => $e,
                    'validated' => $validated,
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'query_log' => DB::getQueryLog()
                ]);
                return $request->ajax()
                    ? response()->json(['errors' => ['error' => 'Database error: ' . $e->getMessage()]], 422)
                    : back()->withInput()->withErrors(['error' => 'Database error: ' . $e->getMessage()]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors(), 'input' => $request->all()]);
            return $request->ajax()
                ? response()->json(['errors' => $e->errors()], 422)
                : back()->withInput()->withErrors($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Model not found: ' . $e->getMessage(), ['input' => $request->all()]);
            return $request->ajax()
                ? response()->json(['errors' => ['error' => 'Referenced record (faculty, venue, or course) not found.']], 422)
                : back()->withInput()->withErrors(['error' => 'Referenced record (faculty, venue, or course) not found.']);
        } catch (\Exception $e) {
            Log::error('Unexpected error in update: ' . $e->getMessage(), [
                'exception' => $e,
                'input' => $request->all()
            ]);
            return $request->ajax()
                ? response()->json(['errors' => ['error' => 'Unexpected error: ' . $e->getMessage()]], 422)
                : back()->withInput()->withErrors(['error' => 'Unexpected error: ' . $e->getMessage()]);
        }
    }

    public function show(Timetable $timetable)
    {
        $timetable->load('faculty', 'venue', 'lecturer');
        $groupSelection = $timetable->group_selection;
        $faculty = $timetable->faculty;

        if ($groupSelection === 'All Groups') {
            $studentCount = $faculty->total_students_no ?? FacultyGroup::where('faculty_id', $faculty->id)->sum('student_count');
            $groupDetails = "All Groups ({$studentCount} students)";
        } else {
            $groups = explode(',', $groupSelection);
            $groupCounts = FacultyGroup::where('faculty_id', $faculty->id)
                ->whereIn('group_name', $groups)
                ->pluck('student_count', 'group_name');
            $details = [];
            $total = 0;
            foreach ($groups as $group) {
                $count = $groupCounts[$group] ?? 0;
                $details[] = "{$group} ({$count})";
                $total += $count;
            }
            $groupDetails = implode(', ', $details) . " - Total: {$total}";
        }

        // Format time_start and time_end to HH:mm for frontend
        $timetableData = $timetable->toArray();
        $timetableData['time_start'] = date('H:i', strtotime($timetable->time_start));
        $timetableData['time_end'] = date('H:i', strtotime($timetable->time_end));
        $timetableData['group_details'] = $groupDetails;

        return response()->json($timetableData);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $import = new TimetableImport;
            Excel::import($import, $request->file('file'));

            if (!empty($import->errors)) {
                return redirect()->route('timetable.index')
                    ->withErrors(['import_errors' => $import->errors]);
            }

            return redirect()->route('timetable.index')
                ->with('success', 'Timetable imported successfully.');
        } catch (\Exception $e) {
            Log::error('Import failed: ' . $e->getMessage());
            return redirect()->route('timetable.index')
                ->withErrors(['import_errors' => 'Failed to import timetable.']);
        }
    }

    public function export()
    {
        $faculties = Faculty::orderBy('name')->with('timetables.venue')->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];

        // Log all timetable data for debugging
        foreach ($faculties as $faculty) {
            Log::info('Timetable data for Faculty: ' . $faculty->name, [
                'faculty_id' => $faculty->id,
                'timetables' => $faculty->timetables->map(function ($timetable) {
                    return [
                        'id' => $timetable->id,
                        'day' => $timetable->day,
                        'time_start' => $timetable->time_start,
                        'time_end' => $timetable->time_end,
                        'course_code' => $timetable->course_code,
                        'group_selection' => $timetable->group_selection,
                        'venue' => $timetable->venue->name,
                        'activity' => $timetable->activity
                    ];
                })->toArray()
            ]);
        }

        $pdf = Pdf::loadView('timetable.pdf', compact('faculties', 'days', 'timeSlots'));
        $randomNumber = mt_rand(1000, 9999);
        return $pdf->download("timetable_{$randomNumber}.pdf");
    }
    public function getStudentCount(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'groups' => 'required|array'
        ]);

        $faculty = Faculty::findOrFail($request->faculty_id);
        $groups = $request->groups;

        if (in_array('All Groups', $groups)) {
            return response()->json(['student_count' => $faculty->total_students_no]);
        }

        $studentCount = FacultyGroup::where('faculty_id', $faculty->id)
            ->whereIn('group_name', $groups)
            ->sum('student_count');

        return response()->json(['student_count' => $studentCount]);
    }

    private function calculateStudentCount(Faculty $faculty, string $groupSelection): int
    {
        if ($groupSelection === 'All Groups') {
            return $faculty->total_students_no;
        }
        $groups = explode(',', $groupSelection);
        return FacultyGroup::where('faculty_id', $faculty->id)
            ->whereIn('group_name', $groups)
            ->sum('student_count');
    }

    private function checkConflicts(Request $request, array $validated, ?int $excludeId = null): array
    {
        Log::info('Checking conflicts', [
            'excludeId' => $excludeId,
            'validated' => $validated
        ]);

        $conflicts = [];

        // 1. Lecturer Conflict Check
        if ($validated['lecturer_id']) {
            $lecturerConflict = Timetable::where('day', $validated['day'])
                ->where('lecturer_id', $validated['lecturer_id'])
                ->where('course_code', '!=', $validated['course_code']) // Allow same lecturer for different courses
                ->where(function ($query) use ($validated) {
                    $query->where(function ($q) use ($validated) {
                        $q->where('time_start', '<', $validated['time_end'])
                            ->where('time_end', '>', $validated['time_start']);
                    });
                })
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();
            if ($lecturerConflict) {
                Log::info('Lecturer conflict found', ['conflict' => $lecturerConflict->toArray()]);
                $conflicts[] = "Lecturer is assigned to session {$lecturerConflict->course_code} for {$lecturerConflict->faculty->name} from {$lecturerConflict->time_start} to {$lecturerConflict->time_end}.";
            }
        }

        // 2. Venue Conflict Check
        $venueConflict = Timetable::where('day', $validated['day'])
            ->where('venue_id', $validated['venue_id'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('time_start', '<', $validated['time_end'])
                        ->where('time_end', '>', $validated['time_start']);
                });
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();
        if ($venueConflict) {
            Log::info('Venue conflict found', ['conflict' => $venueConflict->toArray()]);
            $conflicts[] = "Venue {$venueConflict->venue->name} is in use by {$venueConflict->faculty->name} for {$venueConflict->course_code} from {$venueConflict->time_start} to {$venueConflict->time_end}.";
        }

        // 3. Group Conflict Check
        $groups = explode(',', $validated['group_selection']);
        if ($groups[0] === 'All Groups') {
            $groups = FacultyGroup::where('faculty_id', $validated['faculty_id'])->pluck('group_name')->toArray();
        }
        foreach ($groups as $group) {
            $groupConflict = Timetable::where('day', $validated['day'])
                ->where('faculty_id', $validated['faculty_id'])
                ->where('group_selection', 'like', "%$group%")
                ->where(function ($query) use ($validated) {
                    $query->where(function ($q) use ($validated) {
                        $q->where('time_start', '<', $validated['time_end'])
                            ->where('time_end', '>', $validated['time_start']);
                    });
                })
                ->where('venue_id', '!=', $validated['venue_id']) // Allow same group if venue differs
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();
            if ($groupConflict) {
                Log::info('Group conflict found', ['group' => $group, 'conflict' => $groupConflict->toArray()]);
                $conflicts[] = "Group $group is already assigned to session {$groupConflict->course_code} at {$groupConflict->venue->name} from {$groupConflict->time_start} to {$groupConflict->time_end}.";
            }
        }

        // 4. Faculty "All Groups" Conflict Check (relaxed to allow multiple sessions)
        if (in_array('All Groups', $groups)) {
            $facultyConflict = Timetable::where('day', $validated['day'])
                ->where('faculty_id', $validated['faculty_id'])
                ->where('group_selection', 'like', '%All Groups%')
                ->where(function ($query) use ($validated) {
                    $query->where(function ($q) use ($validated) {
                        $q->where('time_start', '<', $validated['time_end'])
                            ->where('time_end', '>', $validated['time_start']);
                    });
                })
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();
            if ($facultyConflict) {
                Log::info('Faculty All Groups conflict found', ['conflict' => $facultyConflict->toArray()]);
                $conflicts[] = "Faculty {$facultyConflict->faculty->name} has a session for all groups with {$facultyConflict->course_code} from {$facultyConflict->time_start} to {$facultyConflict->time_end}.";
            }
        } else {
            $allGroupsConflict = Timetable::where('day', $validated['day'])
                ->where('faculty_id', $validated['faculty_id'])
                ->where('group_selection', 'All Groups')
                ->where(function ($query) use ($validated) {
                    $query->where(function ($q) use ($validated) {
                        $q->where('time_start', '<', $validated['time_end'])
                            ->where('time_end', '>', $validated['time_start']);
                    });
                })
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();
            if ($allGroupsConflict) {
                Log::info('Faculty All Groups conflict found (alternative)', ['conflict' => $allGroupsConflict->toArray()]);
                $conflicts[] = "Faculty {$allGroupsConflict->faculty->name} has a session for all groups with {$allGroupsConflict->course_code} from {$allGroupsConflict->time_start} to {$allGroupsConflict->time_end}.";
            }
        }

        // 5. Course Conflict Check
        $courseConflict = Timetable::where('day', $validated['day'])
            ->where('course_code', $validated['course_code'])
            ->where('faculty_id', $validated['faculty_id'])
            ->where('activity', $validated['activity']) // Conflict only if same activity
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('time_start', '<', $validated['time_end'])
                        ->where('time_end', '>', $validated['time_start']);
                });
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();
        if ($courseConflict) {
            Log::info('Course conflict found', ['conflict' => $courseConflict->toArray()]);
            $conflicts[] = "Course {$validated['course_code']} is already scheduled for {$courseConflict->faculty->name} as {$courseConflict->activity} from {$courseConflict->time_start} to {$courseConflict->time_end}.";
        }

        return $conflicts;
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
        $course = Course::where('course_code', $courseCode)->first();
        if (!$course) {
            return response()->json(['lecturers' => []]);
        }
        $lecturers = $course->lecturers()->select('users.id', 'users.name')->get()->toArray();
        return response()->json(['lecturers' => $lecturers]);
    }

    public function destroy(Request $request, Timetable $timetable)
    {
        Log::info('Delete request for timetable:', ['id' => $timetable->id, 'course_code' => $timetable->course_code]);

        try {
            DB::enableQueryLog();
            DB::beginTransaction();
            try {
                $timetable->delete();
                DB::commit();
                Log::info('Timetable deleted successfully:', [
                    'id' => $timetable->id,
                    'query_log' => DB::getQueryLog()
                ]);
                return $request->ajax()
                    ? response()->json(['message' => 'Timetable entry deleted successfully.'])
                    : redirect()->route('timetable.index')->with('success', 'Timetable entry deleted successfully.');
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                Log::error('Database error deleting timetable: ' . $e->getMessage(), [
                    'exception' => $e,
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'query_log' => DB::getQueryLog()
                ]);
                return $request->ajax()
                    ? response()->json(['errors' => ['error' => 'Database error: ' . $e->getMessage()]], 422)
                    : back()->withErrors(['error' => 'Database error: ' . $e->getMessage()]);
            }
        } catch (\Exception $e) {
            Log::error('Unexpected error in delete: ' . $e->getMessage(), [
                'exception' => $e,
                'timetable_id' => $timetable->id
            ]);
            return $request->ajax()
                ? response()->json(['errors' => ['error' => 'Unexpected error: ' . $e->getMessage()]], 422)
                : back()->withErrors(['error' => 'Unexpected error: ' . $e->getMessage()]);
        }
    }
}

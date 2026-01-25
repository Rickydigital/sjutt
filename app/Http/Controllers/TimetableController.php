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
use App\Models\Semester;
use App\Models\TimetableSemester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function index(Request $request)
{
    // Initialize variables
    $timetables = collect();
    $facultyId = $request->input('faculty');
    $faculties = Faculty::pluck('name', 'id');
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];
    $venues = Venue::select('id', 'name', 'capacity')->get();
    $timetableSemesters = TimetableSemester::with('semester')->get();
    $semesters = Semester::all(); 

    if (!TimetableSemester::exists()) {
        return view('timetable.index', [
            'timetables' => $timetables,
            'faculties' => $faculties,
            'days' => $days,
            'timeSlots' => $timeSlots,
            'venues' => $venues,
            'facultyId' => null,
            'timetableSemester' => null,
            'timetableSemesters' => $timetableSemesters,
            'semesters' => $semesters,
            'error' => 'No timetable semester configured. Please add a timetable semester.'
        ]);
    }

    $timetableSemester = TimetableSemester::getFirstSemester();
    if ($facultyId) {
        $timetables = Timetable::where('faculty_id', $facultyId)
            ->where('semester_id', $timetableSemester->semester_id)
            ->with('faculty', 'venue', 'lecturer')
            ->get();
    }

    return view('timetable.index', compact(
        'timetables',
        'faculties',
        'days',
        'timeSlots',
        'facultyId',
        'venues',
        'timetableSemester',
        'timetableSemesters',
        'semesters'
    ));
}

public function getAvailableVenues(Request $request)
{
    $request->validate([
        'day'        => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
        'time_start' => 'required|date_format:H:i',
        'time_end'   => 'required|date_format:H:i|after:time_start',
        'faculty_id' => 'required|exists:faculties,id',
        'exclude_id' => 'nullable|integer', // For edit mode: exclude current timetable ID
    ]);

    $timetableSemester = TimetableSemester::getFirstSemester();

    // Find all venues that are ALREADY booked in the selected time slot
    $bookedVenueIds = Timetable::where('day', $request->day)
        ->where('semester_id', $timetableSemester->semester_id)
        ->where(function ($q) use ($request) {
            $q->where('time_start', '<', $request->time_end)
              ->where('time_end', '>', $request->time_start);
        })
        ->when($request->filled('exclude_id'), fn($q) => $q->where('id', '!=', $request->exclude_id))
        ->pluck('venue_id')
        ->unique();

    // Get all venues except the booked ones
    $availableVenues = Venue::whereNotIn('id', $bookedVenueIds)
        ->select('id', 'name', 'capacity')
        ->orderBy('name')
        ->get();

    return response()->json([
        'venues' => $availableVenues->map(function ($venue) {
            return [
                'id'       => $venue->id,
                'name'     => $venue->name,
                'capacity' => $venue->capacity,
                'text'     => $venue->name . ' (Capacity: ' . $venue->capacity . ')'
            ];
        })
    ]);
}

/* --------------------------------------------------------------------- */
/*  STORE (ONLY VENUE AVAILABILITY CHECK)                               */
/* --------------------------------------------------------------------- */
public function store(Request $request)
{
    Log::info('Store request data:', $request->all());

    try {
        if (!TimetableSemester::exists()) {
            return $request->ajax()
                ? response()->json(['errors' => ['semester' => 'No timetable semester configured.']], 422)
                : back()->withInput()->withErrors(['semester' => 'No timetable semester configured.']);
        }

        $timetableSemester = TimetableSemester::getFirstSemester();

        $validated = $request->validate([
            'day'            => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'faculty_id'     => 'required|exists:faculties,id',
            'time_start'     => 'required|date_format:H:i',
            'time_end'       => 'required|date_format:H:i|after:time_start',
            'course_code'    => 'required|string|exists:courses,course_code',
            'activity'       => 'nullable|in:Practical,Workshop,Lecture',
            'venue_id'       => 'required|string|regex:/^(\d+)(,\d+)*$/',
            'lecturer_id'    => 'required|exists:users,id',
            'group_selection'=> 'required|array|min:1',
            'group_selection.*' => 'string'
        ]);

        Log::info('Validation passed:', $validated);

        $course = Course::where('course_code', $validated['course_code'])->firstOrFail();

        $validated['group_selection'] = implode(',', $validated['group_selection']);
        $validated['course_name']     = $course->name ?? null;
        $validated['time_start']      = date('H:i:s', strtotime($validated['time_start']));
        $validated['time_end']        = date('H:i:s', strtotime($validated['time_end']));
        $validated['semester_id'] = $timetableSemester->semester_id;

        // -----------------------------------------------------------------
        // ONLY VENUE AVAILABILITY CHECK (no capacity, lecturer, or group checks)
        // -----------------------------------------------------------------
        $venueIds = explode(',', $validated['venue_id']);
        foreach ($venueIds as $venueId) {
            $venueConflict = Timetable::where('day', $validated['day'])
                ->where('venue_id', $venueId)
                ->where('semester_id', $timetableSemester->semester_id)
                ->where(function ($query) use ($validated) {
                    $query->where('time_start', '<', $validated['time_end'])
                          ->where('time_end', '>', $validated['time_start']);
                })
                ->first();

            if ($venueConflict) {
                Log::warning('Venue conflict found:', ['venue_id' => $venueId, 'conflict' => $venueConflict->toArray()]);
                return $request->ajax()
                    ? response()->json(['errors' => ['venue_id' => "Venue ID {$venueId} is already booked from {$venueConflict->time_start} to {$venueConflict->time_end}"]], 422)
                    : back()->withInput()->withErrors(['venue_id' => "Venue ID {$venueId} is already booked from {$venueConflict->time_start} to {$venueConflict->time_end}"]);
            }
        }

        DB::beginTransaction();
        $timetable = Timetable::create($validated);
        DB::commit();

        Log::info('Timetable created (venue availability only):', ['id' => $timetable->id]);

        return $request->ajax()
            ? response()->json(['message' => 'Timetable entry created successfully.', 'id' => $timetable->id])
            : redirect()->route('timetable.index')->with('success', 'Timetable entry created successfully.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed:', ['errors' => $e->errors()]);
        return $request->ajax()
            ? response()->json(['errors' => $e->errors()], 422)
            : back()->withInput()->withErrors($e->errors());

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Unexpected error in store:', ['msg' => $e->getMessage()]);
        return $request->ajax()
            ? response()->json(['errors' => ['error' => 'Unexpected error: '.$e->getMessage()]], 422)
            : back()->withInput()->withErrors(['error' => 'Unexpected error: '.$e->getMessage()]);
    }
}

/* --------------------------------------------------------------------- */
/*  UPDATE (ONLY VENUE AVAILABILITY CHECK)                              */
/* --------------------------------------------------------------------- */
public function update(Request $request, Timetable $timetable)
{
    Log::info('Update request data:', $request->all());

    try {
        if (!TimetableSemester::exists()) {
            return $request->ajax()
                ? response()->json(['errors' => ['semester' => 'No timetable semester configured.']], 422)
                : back()->withInput()->withErrors(['semester' => 'No timetable semester configured.']);
        }

        $timetableSemester = TimetableSemester::getFirstSemester();

        $validated = $request->validate([
            'day'            => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'faculty_id'     => 'required|exists:faculties,id',
            'time_start'     => 'required|date_format:H:i',
            'time_end'       => 'required|date_format:H:i|after:time_start',
            'course_code'    => 'required|string|exists:courses,course_code',
            'activity'       => 'nullable|in:Practical,Workshop,Lecture',
            'venue_id'       => 'required|string|regex:/^(\d+)(,\d+)*$/',
            'lecturer_id'    => 'required|exists:users,id',
            'group_selection'=> 'required|array|min:1',
            'group_selection.*' => 'string'
        ]);

        Log::info('Validation passed:', $validated);

        $validated['time_start'] = date('H:i:s', strtotime($validated['time_start']));
        $validated['time_end']   = date('H:i:s', strtotime($validated['time_end']));

        $course = Course::where('course_code', $validated['course_code'])->firstOrFail();
        $validated['group_selection'] = implode(',', $validated['group_selection']);
        $validated['course_name']     = $course->name ?? null;
        $validated['semester_id'] = $timetableSemester->semester_id;

        // Detect no changes
        $current = $timetable->only(array_keys($validated));
        if ($current === $validated) {
            return $request->ajax()
                ? response()->json(['message' => 'No changes made.', 'id' => $timetable->id])
                : redirect()->route('timetable.index')->with('success', 'No changes made.');
        }

        // -----------------------------------------------------------------
        // ONLY VENUE AVAILABILITY CHECK (excluding current record)
        // -----------------------------------------------------------------
        $venueIds = explode(',', $validated['venue_id']);
        foreach ($venueIds as $venueId) {
            $venueConflict = Timetable::where('day', $validated['day'])
                ->where('venue_id', $venueId)
                ->where('semester_id', $timetableSemester->semester_id)
                ->where(function ($query) use ($validated) {
                    $query->where('time_start', '<', $validated['time_end'])
                          ->where('time_end', '>', $validated['time_start']);
                })
                ->where('id', '!=', $timetable->id) // Exclude current record
                ->first();

            if ($venueConflict) {
                Log::warning('Venue conflict found:', ['venue_id' => $venueId, 'conflict' => $venueConflict->toArray()]);
                return $request->ajax()
                    ? response()->json(['errors' => ['venue_id' => "Venue ID {$venueId} is already booked from {$venueConflict->time_start} to {$venueConflict->time_end}"]], 422)
                    : back()->withInput()->withErrors(['venue_id' => "Venue ID {$venueId} is already booked from {$venueConflict->time_start} to {$venueConflict->time_end}"]);
            }
        }

        DB::beginTransaction();
        $timetable->update($validated);
        DB::commit();

        Log::info('Timetable updated (venue availability only):', ['id' => $timetable->id]);

        return $request->ajax()
            ? response()->json(['message' => 'Timetable entry updated successfully.', 'id' => $timetable->id])
            : redirect()->route('timetable.index')->with('success', 'Timetable entry updated successfully.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed:', ['errors' => $e->errors()]);
        return $request->ajax()
            ? response()->json(['errors' => $e->errors()], 422)
            : back()->withInput()->withErrors($e->errors());

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Unexpected error in update:', ['msg' => $e->getMessage()]);
        return $request->ajax()
            ? response()->json(['errors' => ['error' => 'Unexpected error: '.$e->getMessage()]], 422)
            : back()->withInput()->withErrors(['error' => 'Unexpected error: '.$e->getMessage()]);
    }
}

    public function show(Timetable $timetable)
    {
        $timetable->load('faculty', 'venue', 'lecturer', 'semester');
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

        $timetableData = $timetable->toArray();
        $timetableData['time_start'] = date('H:i', strtotime($timetable->time_start));
        $timetableData['time_end'] = date('H:i', strtotime($timetable->time_end));
        $timetableData['group_details'] = $groupDetails;
        $timetableData['semester_name'] = $timetable->semester ? $timetable->semester->name : 'N/A';

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

    public function export(Request $request)
{
    if (!TimetableSemester::exists()) {
        return redirect()->route('timetable.index')
            ->withErrors(['semester' => 'No timetable semester configured.']);
    }

    $timetableSemester = TimetableSemester::getFirstSemester();
    $draft = $request->query('draft', 'Final Draft'); // Default to Final

    $faculties = Faculty::orderBy('name')
        ->with(['timetables' => fn($q) => $q->where('semester_id', $timetableSemester->semester_id)->with('venue')])
        ->get();

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];

    // Sanitize filename
    $safeAcademicYear = str_replace(['/', '\\'], '-', $timetableSemester->academic_year);
    $safeSemesterName = str_replace(['/', '\\'], '-', $timetableSemester->semester->name);
    $safeDraft = str_replace([' ', '/'], '-', $draft);
    $filename = "timetable_{$safeAcademicYear}_{$safeSemesterName}_{$safeDraft}.pdf";

    $pdf = Pdf::loadView('timetable.pdf', compact('faculties', 'days', 'timeSlots', 'timetableSemester', 'draft'));
    return $pdf->download($filename);
}
    public function generateTimetable(Request $request)
{
    Log::info('Generate timetable request:', $request->all());

    try {
        if (!TimetableSemester::exists()) {
            return response()->json(['errors' => ['semester' => 'No timetable semester configured.']], 422);
        }

        $timetableSemester = TimetableSemester::getFirstSemester();
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'courses' => 'required|array|min:1',
            'courses.*' => [
                'required',
                'string',
                'exists:courses,course_code,semester_id,' . $timetableSemester->semester_id
            ],
            'lecturers' => 'required|array|min:1',
            'lecturers.*' => 'required|exists:users,id',
            'groups' => 'required|array|min:1',
            'groups.*' => 'required|array|min:1',
            'groups.*.*' => 'required|string',
            'venues' => 'required|array|min:1',
            'venues.*' => 'required|exists:venues,id',
            'activities' => 'required|array|min:1',
            'activities.*' => [
                'nullable',
                'in:Practical,Workshop,Lecture',
                function ($attribute, $value, $fail) use ($request, $timetableSemester) {
                    $index = explode('.', $attribute)[1];
                    $courseCode = $request->input("courses.$index");
                    if ($value === 'Practical') {
                        $course = Course::where('course_code', $courseCode)
                            ->where('semester_id', $timetableSemester->semester_id)
                            ->first();
                        if (!$course || !$course->practical_hrs) {
                            $fail('Practical sessions are not allowed for courses without practical hours.');
                        }
                    }
                },
            ],
        ]);

        $faculty = Faculty::findOrFail($validated['faculty_id']);
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];
        $venues = Venue::whereIn('id', $validated['venues'])->select('id', 'name', 'capacity')->get();

    $existingSessions = [];
foreach ($validated['courses'] as $index => $courseCode) {
    $groupSelection = isset($validated['groups'][$index]) ? implode(',', $validated['groups'][$index]) : 'All Groups';
    $activity = $validated['activities'][$index] ?? 'Lecture';
    $lecturerId = $validated['lecturers'][$index];

    // For 'All Groups' with Practical, count existing sessions per group
    if ($groupSelection === 'All Groups' && $activity === 'Practical') {
        $groups = FacultyGroup::where('faculty_id', $validated['faculty_id'])->pluck('group_name')->toArray();
        foreach ($groups as $group) {
            $existing = Timetable::where('faculty_id', $validated['faculty_id'])
                ->where('course_code', $courseCode)
                ->where('lecturer_id', $lecturerId)
                ->where('group_selection', $group)
                ->where('activity', $activity)
                ->where('semester_id', $timetableSemester->semester_id)
                ->count();
            $existingSessions[$courseCode][$group][$lecturerId][$activity] = $existing;
        }
    } else {
        $existing = Timetable::where('faculty_id', $validated['faculty_id'])
            ->where('course_code', $courseCode)
            ->where('lecturer_id', $lecturerId)
            ->where('group_selection', $groupSelection)
            ->where('activity', $activity)
            ->where('semester_id', $timetableSemester->semester_id)
            ->count();
        $existingSessions[$courseCode][$groupSelection][$lecturerId][$activity] = $existing;
    }
}

$sessions = [];
foreach ($validated['courses'] as $index => $courseCode) {
    $course = Course::where('course_code', $courseCode)
        ->where('semester_id', $timetableSemester->semester_id)
        ->firstOrFail();
    $groupSelection = isset($validated['groups'][$index]) ? implode(',', $validated['groups'][$index]) : 'All Groups';
    $activity = $validated['activities'][$index] ?? 'Lecture';
    $lecturerId = $validated['lecturers'][$index];

    $existingCount = $existingSessions[$courseCode][$groupSelection][$lecturerId][$activity] ?? 0;
    $requiredSessions = $activity === 'Practical' ? 1 : $course->session - $existingCount;

    if ($requiredSessions <= 0) {
        Log::info('Skipping course due to sufficient existing sessions:', [
            'course_code' => $courseCode,
            'group_selection' => $groupSelection,
            'lecturer_id' => $lecturerId,
            'activity' => $activity,
            'existing_count' => $existingCount
        ]);
        continue;
    }

    $practicalHours = $course->practical_hrs ?? 0;
    $lectureHours = $activity === 'Practical' ? ($course->hours - $practicalHours) : $course->hours;
    $sessionDurations = [];

    if ($activity === 'Practical' && $practicalHours > 0) {
        $sessionDurations[] = $practicalHours;
        $groups = FacultyGroup::where('faculty_id', $validated['faculty_id'])->pluck('group_name')->toArray();
        $groupCount = count($groups);
        $venueCount = count($venues);
        $requiredSessions = ceil($groupCount / $venueCount);

        // Check existing sessions per group to determine remaining groups to schedule
        $groupsToSchedule = [];
        foreach ($groups as $group) {
            $existingForGroup = $existingSessions[$courseCode][$group][$lecturerId][$activity] ?? 0;
            if ($existingForGroup < 1) { // Only schedule if no session exists for this group
                $groupsToSchedule[] = $group;
            }
        }

        if (empty($groupsToSchedule)) {
            Log::info('Skipping practical session: all groups already scheduled:', [
                'course_code' => $courseCode,
                'lecturer_id' => $lecturerId,
                'activity' => $activity
            ]);
            continue;
        }

        $requiredSessions = ceil(count($groupsToSchedule) / $venueCount);
        for ($sessionNum = 0; $sessionNum < $requiredSessions; $sessionNum++) {
            $sessionGroups = array_splice($groupsToSchedule, 0, $venueCount);
            if (!empty($sessionGroups)) {
                $sessions[] = [
                    'course_code' => $courseCode,
                    'course_name' => $course->name,
                    'sessions_per_week' => 1,
                    'hours_per_session' => [$practicalHours],
                    'lecturer_id' => $lecturerId,
                    'group_selection' => 'All Groups', // Placeholder, will be split
                    'faculty_id' => $validated['faculty_id'],
                    'activity' => $activity,
                    'groups_to_schedule' => $sessionGroups,
                ];
            }
        }
    } else {
        $sessionDurations = $this->calculateSessionDurations($lectureHours, $requiredSessions);
        $sessions[] = [
            'course_code' => $courseCode,
            'course_name' => $course->name,
            'sessions_per_week' => $requiredSessions,
            'hours_per_session' => $sessionDurations,
            'lecturer_id' => $lecturerId,
            'group_selection' => $groupSelection,
            'faculty_id' => $validated['faculty_id'],
            'activity' => $activity,
        ];
    }
}

        if (empty($sessions)) {
            Log::warning('No sessions to schedule; all required sessions already exist.');
            return response()->json([
                'success' => true,
                'message' => 'All requested sessions are already scheduled.',
                'timetables' => []
            ], 200);
        }

        $result = $this->scheduleTimetable($sessions, $days, $timeSlots, $venues, $faculty, $request);

        if (empty($result['timetables'])) {
            Log::warning('No timetables generated due to scheduling conflicts or insufficient resources.', [
                'errors' => $result['errors'] ?? []
            ]);
            return response()->json([
                'errors' => [
                    'scheduling' => $result['errors'] ? implode(' ', $result['errors']) : 'Unable to generate a conflict-free timetable. Try adjusting sessions, venues, or lecturers.'
                ]
            ], 422);
        }

        $warnings = $result['warnings'] ?? [];

        if (!empty($warnings) && !$request->has('force_proceed')) {
            return response()->json([
                'success' => true,
                'message' => 'Timetable generated with warnings.',
                'timetables' => $result['timetables'], // Return proposed timetables without saving
                'warnings' => $warnings,
                'proceed' => true
            ], 200);
        }

        // Save if no warnings or force_proceed is set
        DB::beginTransaction();
        try {
            $createdTimetables = [];
            foreach ($result['timetables'] as $timetable) {
                $timetable['semester_id'] = $timetableSemester->semester_id;
                $created = Timetable::create($timetable);
                $createdTimetables[] = $created;
            }
            DB::commit();
            Log::info('Timetable generated successfully:', ['timetables' => $createdTimetables]);
            return response()->json([
                'success' => true,
                'message' => 'Timetable generated successfully.',
                'timetables' => $createdTimetables
            ], 200);
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

private function scheduleTimetable(array $sessions, array $days, array $timeSlots, $venues, Faculty $faculty, Request $request): array
{
    $timetables = [];
    $warnings = [];
    $errors = [];
    $usedSlots = [];
    $lecturerSlots = [];
    $groupSlots = [];
    $courseSlots = [];

    // Define church times with start time and duration (in hours)
    $churchTimes = [
        'Tuesday' => [['start' => '10:00', 'duration' => 1]],
        'Friday' => [['start' => '12:00', 'duration' => 2]]
    ];

    // Categorize time slots into morning, afternoon, evening
    $timePeriods = [
        'morning' => [], // 08:00–12:00
        'afternoon' => [], // 12:00–16:00
        'evening' => [] // 16:00–20:00
    ];
    foreach ($timeSlots as $startTime) {
        $hour = (int)date('H', strtotime($startTime));
        if ($hour >= 8 && $hour < 12) {
            $timePeriods['morning'][] = $startTime;
        } elseif ($hour >= 12 && $hour < 16) {
            $timePeriods['afternoon'][] = $startTime;
        } elseif ($hour >= 16 && $hour <= 20) {
            $timePeriods['evening'][] = $startTime;
        }
    }

    // Generate combinations with period tracking
    $combinations = [];
    foreach ($days as $day) {
        foreach (['morning', 'afternoon', 'evening'] as $period) {
            foreach ($timePeriods[$period] as $startTime) {
                $skipSlot = false;
                if (isset($churchTimes[$day])) {
                    foreach ($churchTimes[$day] as $churchTime) {
                        $churchStart = strtotime($churchTime['start']);
                        $churchEnd = strtotime($churchTime['start']) + ($churchTime['duration'] * 3600);
                        $slotStart = strtotime($startTime);
                        if ($slotStart >= $churchStart && $slotStart < $churchEnd) {
                            Log::info('Skipping church time slot:', [
                                'day' => $day,
                                'start_time' => $startTime,
                                'church_time' => $churchTime['start'],
                                'church_duration' => $churchTime['duration']
                            ]);
                            $skipSlot = true;
                            break;
                        }
                    }
                }
                if (!$skipSlot) {
                    $combinations[] = ['day' => $day, 'startTime' => $startTime, 'period' => $period];
                }
            }
        }
    }

    // Shuffle sessions to prioritize practicals
    usort($sessions, fn($a, $b) => $a['activity'] === 'Practical' ? -1 : 1);
    shuffle($combinations);

    // Track sessions per period per day for balancing
    $sessionsPerPeriod = [];
    foreach ($days as $day) {
        $sessionsPerPeriod[$day] = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];
    }

    foreach ($sessions as $session) {
        $scheduled = 0;
        $requiredSessions = $session['sessions_per_week'];
        $sessionDurations = is_array($session['hours_per_session']) ? $session['hours_per_session'] : explode(', ', $session['hours_per_session']);
        $studentCount = $this->calculateStudentCount($faculty, $session['group_selection']);
        $sessionConflicts = [];

        Log::info('Scheduling session for course:', [
            'course_code' => $session['course_code'],
            'activity' => $session['activity'],
            'group_selection' => $session['group_selection'],
            'student_count' => $studentCount,
            'sessions_per_week' => $requiredSessions,
            'hours_per_session' => implode(', ', $sessionDurations)
        ]);

        $filteredVenues = $session['activity'] === 'Practical'
            ? $venues->filter(fn($venue) => (stripos($venue->name, 'laboratory') !== false || $venue->capacity <= 50))
            : $venues->filter(fn($venue) => $venue->capacity + 15 >= $studentCount);

        if ($filteredVenues->isEmpty()) {
            $errors[] = "No suitable venues available for {$session['course_code']} (Activity: {$session['activity']}, Students: {$studentCount}, Required: Laboratory or capacity ≤ 50 for Practical).";
            continue;
        }

        $groups = $session['group_selection'] === 'All Groups'
            ? FacultyGroup::where('faculty_id', $session['faculty_id'])->pluck('group_name')->toArray()
            : explode(',', $session['group_selection']);
        $groupCounts = FacultyGroup::where('faculty_id', $session['faculty_id'])
            ->whereIn('group_name', $groups)
            ->pluck('student_count', 'group_name')->toArray();

        $durationIndex = 0;
        $groupsToSchedule = $session['activity'] === 'Practical' && $session['group_selection'] === 'All Groups'
            ? array_keys($groupCounts)
            : [$session['group_selection']];

        $existingTimetables = Timetable::where('faculty_id', $session['faculty_id'])
            ->where('semester_id', TimetableSemester::getFirstSemester()->semester_id)
            ->get()
            ->mapWithKeys(function ($t) {
                return ["{$t->day}:{$t->time_start}:{$t->course_code}" => $t];
            });

        while ($scheduled < $requiredSessions && !empty($combinations)) {
            $scheduledSession = false;
            $availableVenues = $filteredVenues->shuffle()->values();
            $hours = $sessionDurations[$durationIndex % count($sessionDurations)];

            // Sort combinations to prefer underutilized periods
            usort($combinations, function ($a, $b) use ($sessionsPerPeriod) {
                $periodA = $sessionsPerPeriod[$a['day']][$a['period']];
                $periodB = $sessionsPerPeriod[$b['day']][$b['period']];
                return $periodA <=> $periodB; // Prefer periods with fewer sessions
            });

            foreach ($combinations as $index => $combo) {
                $day = $combo['day'];
                $startTime = $combo['startTime'];
                $period = $combo['period'];
                $endTime = date('H:i', strtotime($startTime) + ($hours * 3600));

                if (strtotime($endTime) > strtotime('20:00')) {
                    continue;
                }

                $startTimestamp = strtotime($startTime);
                $endTimestamp = strtotime($endTime);
                $churchConflict = false;
                if (isset($churchTimes[$day])) {
                    foreach ($churchTimes[$day] as $churchTime) {
                        $churchStart = strtotime($churchTime['start']);
                        $churchEnd = strtotime($churchTime['start']) + ($churchTime['duration'] * 3600);
                        if ($startTimestamp < $churchEnd && $endTimestamp > $churchStart) {
                            $sessionConflicts[] = "Cannot schedule {$session['course_code']} on {$day} at {$startTime} due to church time conflict ({$churchTime['start']} for {$churchTime['duration']} hours).";
                            $churchConflict = true;
                            break;
                        }
                    }
                }
                if ($churchConflict) continue;

               

                $lecturerAvailable = true;
                for ($h = 0; $h < $hours; $h++) {
                    $currentTime = date('H:i', strtotime($startTime) + ($h * 3600));
                    if (isset($lecturerSlots[$session['lecturer_id']][$day][$currentTime])) {
                        $lecturerAvailable = false;
                        $sessionConflicts[] = "Lecturer ID {$session['lecturer_id']} is unavailable on {$day} at {$currentTime}.";
                        break;
                    }
                }
                if (!$lecturerAvailable) continue;

                $key = "$day:$startTime:{$session['course_code']}";
                if (isset($existingTimetables[$key]) || isset($courseSlots[$session['course_code']][$day][$startTime])) {
                    $sessionConflicts[] = "Course {$session['course_code']} is already scheduled on {$day} at {$startTime}.";
                    continue;
                }

                if ($session['activity'] === 'Practical' && $session['group_selection'] === 'All Groups') {
                    $assignedGroups = [];
                    $venueAssignments = [];
                    $availableVenuesForSession = $availableVenues->filter(function ($venue) use ($day, $startTime, $hours, $usedSlots) {
                        for ($h = 0; $h < $hours; $h++) {
                            $currentTime = date('H:i', strtotime($startTime) + ($h * 3600));
                            if (isset($usedSlots[$day][$currentTime][$venue->id])) {
                                return false;
                            }
                        }
                        return true;
                    })->values();

                    if ($availableVenuesForSession->isEmpty()) {
                        $sessionConflicts[] = "No available venues on {$day} at {$startTime} for {$session['course_code']}.";
                        Log::warning('No available venues for practical session:', [
                            'course_code' => $session['course_code'],
                            'day' => $day,
                            'start_time' => $startTime,
                            'available_venues' => $availableVenuesForSession->pluck('name')->toArray()
                        ]);
                        continue;
                    }

                    $groupsToSchedule = $session['groups_to_schedule'] ?? array_keys($groupCounts);
                    if (empty($groupsToSchedule)) {
                        Log::info('No groups to schedule for practical session:', ['course_code' => $session['course_code'], 'day' => $day, 'start_time' => $startTime]);
                        continue;
                    }

                    $groupsToScheduleCopy = $groupsToSchedule;
                    foreach ($availableVenuesForSession as $venue) {
                        if (empty($groupsToScheduleCopy)) break;
                        $group = array_shift($groupsToScheduleCopy);
                        $groupStudentCount = $groupCounts[$group] ?? 0;

                        if ($venue->capacity + 15 < $groupStudentCount) {
                            $sessionConflicts[] = "Venue {$venue->name} capacity ({$venue->capacity} + 15) is insufficient for group {$group} ({$groupStudentCount} students).";
                            continue;
                        }

                        if ($venue->capacity < $groupStudentCount && !$request->has('force_proceed')) {
                            $warnings[] = "Venue {$venue->name} (capacity: {$venue->capacity}) is assigned {$groupStudentCount} students for {$session['course_code']}, exceeding capacity but within 15-student buffer.";
                        }

                        $timetableData = [
                            'day' => $day,
                            'time_start' => $startTime,
                            'time_end' => $endTime,
                            'course_code' => $session['course_code'],
                            'course_name' => $session['course_name'],
                            'activity' => $session['activity'],
                            'venue_id' => $venue->id,
                            'lecturer_id' => $session['lecturer_id'],
                            'group_selection' => $group,
                            'faculty_id' => $session['faculty_id'],
                            'semester_id' => TimetableSemester::getFirstSemester()->semester_id
                        ];

                        $conflicts = $this->checkConflicts(new Request($timetableData), $timetableData);
                        if (!empty($conflicts)) {
                            $sessionConflicts = array_merge($sessionConflicts, $conflicts);
                            continue;
                        }

                        $timetables[] = $timetableData;
                        $assignedGroups[] = $group;
                        $venueAssignments[] = [
                            'venue_id' => $venue->id,
                            'venue_name' => $venue->name,
                            'group' => $group,
                            'student_count' => $groupStudentCount
                        ];

                        for ($h = 0; $h < $hours; $h++) {
                            $currentTime = date('H:i', strtotime($startTime) + ($h * 3600));
                            $usedSlots[$day][$currentTime][$venue->id] = true;
                            $lecturerSlots[$session['lecturer_id']][$day][$currentTime] = true;
                            $groupSlots[$group][$day][$currentTime] = true;
                        }
                    }

                    if (!empty($assignedGroups)) {
                        Log::info('Session scheduled for groups:', [
                            'course_code' => $session['course_code'],
                            'day' => $day,
                            'time_start' => $startTime,
                            'time_end' => $endTime,
                            'venues_assigned' => $venueAssignments,
                            'period' => $period
                        ]);

                        unset($combinations[$index]);
                        $combinations = array_values($combinations);
                        $scheduled++;
                        $sessionsPerPeriod[$day][$period]++;
                        $scheduledSession = true;
                        $durationIndex++;
                        $session['groups_to_schedule'] = array_diff($groupsToSchedule, $assignedGroups);
                    }
                } else {
                    foreach ($availableVenues as $venue) {
                        $venueAvailable = true;
                        for ($h = 0; $h < $hours; $h++) {
                            $currentTime = date('H:i', strtotime($startTime) + ($h * 3600));
                            if (isset($usedSlots[$day][$currentTime][$venue->id])) {
                                $venueAvailable = false;
                                $sessionConflicts[] = "Venue {$venue->name} is unavailable on {$day} at {$currentTime}.";
                                break;
                            }
                        }
                        if (!$venueAvailable) continue;

                        $groupsAvailable = true;
                        foreach ($groups as $group) {
                            for ($h = 0; $h < $hours; $h++) {
                                $currentTime = date('H:i', strtotime($startTime) + ($h * 3600));
                                if (isset($groupSlots[$group][$day][$currentTime])) {
                                    $groupsAvailable = false;
                                    $sessionConflicts[] = "Group {$group} is unavailable on {$day} at {$currentTime}.";
                                    break 2;
                                }
                            }
                        }
                        if (!$groupsAvailable) continue;

                        if ($venue->capacity < $studentCount && $venue->capacity + 15 >= $studentCount) {
                            Log::warning('Venue capacity exceeded but within buffer:', [
                                'venue_capacity' => $venue->capacity,
                                'student_count' => $studentCount,
                                'course_code' => $session['course_code']
                            ]);
                            if (!$request->has('force_proceed')) {
                                $warnings[] = "Venue {$venue->name} (capacity: {$venue->capacity}) is assigned {$studentCount} students for {$session['course_code']}, exceeding capacity but within 15-student buffer.";
                            }
                        }

                        $timetableData = [
                            'day' => $day,
                            'time_start' => $startTime,
                            'time_end' => $endTime,
                            'course_code' => $session['course_code'],
                            'course_name' => $session['course_name'],
                            'activity' => $session['activity'],
                            'venue_id' => $venue->id,
                            'lecturer_id' => $session['lecturer_id'],
                            'group_selection' => $session['group_selection'],
                            'faculty_id' => $session['faculty_id'],
                            'semester_id' => TimetableSemester::getFirstSemester()->semester_id
                        ];

                        $conflicts = $this->checkConflicts(new Request($timetableData), $timetableData);
                        if (!empty($conflicts)) {
                            $sessionConflicts = array_merge($sessionConflicts, $conflicts);
                            continue;
                        }

                        Log::info('Session scheduled successfully:', [
                            'course_code' => $session['course_code'],
                            'day' => $day,
                            'time_start' => $startTime,
                            'time_end' => $endTime,
                            'venue_id' => $venue->id,
                            'venue_name' => $venue->name,
                            'venue_capacity' => $venue->capacity,
                            'student_count' => $studentCount,
                            'period' => $period
                        ]);

                        $timetables[] = $timetableData;

                        for ($h = 0; $h < $hours; $h++) {
                            $currentTime = date('H:i', strtotime($startTime) + ($h * 3600));
                            $usedSlots[$day][$currentTime][$venue->id] = true;
                            $lecturerSlots[$session['lecturer_id']][$day][$currentTime] = true;
                            foreach ($groups as $group) {
                                $groupSlots[$group][$day][$currentTime] = true;
                            }
                            $courseSlots[$session['course_code']][$day][$startTime] = true;
                        }

                        unset($combinations[$index]);
                        $combinations = array_values($combinations);
                        $scheduled++;
                        $sessionsPerPeriod[$day][$period]++;
                        $scheduledSession = true;
                        $durationIndex++;
                        break;
                    }
                }

                if ($scheduledSession) {
                    break;
                }
            }

            if (!$scheduledSession) {
                Log::warning('Could not schedule session:', [
                    'session' => $session,
                    'conflicts' => array_unique($sessionConflicts)
                ]);
                $errors[] = "Cannot schedule {$session['course_code']}: " . implode(' ', array_unique($sessionConflicts));
                break;
            }
        }

        if ($scheduled < $requiredSessions) {
            $errors[] = "Could only schedule {$scheduled} of {$requiredSessions} sessions for {$session['course_code']} due to conflicts or insufficient resources.";
        }
    }

    // Log session distribution for debugging
    Log::info('Session distribution across periods:', ['sessions_per_period' => $sessionsPerPeriod]);

    return [
        'timetables' => $timetables,
        'warnings' => $warnings,
        'errors' => $errors
    ];
}

private function checkBreakRequirement($facultyId, $lecturerId, $day, $startTime, $duration, $timetables, $activity = null, $relaxBreak = false)
{

    return true;
}

    private function calculateSessionDurations($totalHours, $totalSessions)
    {
        if ($totalHours < $totalSessions || $totalSessions <= 0) {
            throw new \Exception("Invalid combination of total hours ($totalHours) and sessions ($totalSessions).");
        }

        $baseHours = floor($totalHours / $totalSessions);
        $remainder = $totalHours % $totalSessions;
        $durations = array_fill(0, $totalSessions, $baseHours);

        for ($i = 0; $i < $remainder && $durations[$i] < 2; $i++) {
            $durations[$i]++;
        }

        $sum = array_sum($durations);
        if ($sum < $totalHours) {
            throw new \Exception("Cannot distribute $totalHours hours across $totalSessions sessions with max 2 hours per session.");
        }

        return array_filter($durations);
    }

    private function checkConflicts(Request $request, array $validated, ?int $excludeId = null): array
    {
        Log::info('Checking conflicts', [
            'excludeId' => $excludeId,
            'validated' => $validated
        ]);

        $conflicts = [];
        $timetableSemester = TimetableSemester::getFirstSemester();

        if ($validated['lecturer_id']) {
            $lecturerConflict = Timetable::where('day', $validated['day'])
                ->where('lecturer_id', $validated['lecturer_id'])
                ->where('semester_id', $timetableSemester->semester_id)
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

        $venueConflict = Timetable::where('day', $validated['day'])
            ->where('venue_id', $validated['venue_id'])
            ->where('semester_id', $timetableSemester->semester_id)
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

        $groups = explode(',', $validated['group_selection']);
        if ($groups[0] === 'All Groups') {
            $groups = FacultyGroup::where('faculty_id', $validated['faculty_id'])->pluck('group_name')->toArray();
        }
        foreach ($groups as $group) {
            $groupConflict = Timetable::where('day', $validated['day'])
                ->where('faculty_id', $validated['faculty_id'])
                ->where('group_selection', 'like', "%$group%")
                ->where('semester_id', $timetableSemester->semester_id)
                ->where(function ($query) use ($validated) {
                    $query->where(function ($q) use ($validated) {
                        $q->where('time_start', '<', $validated['time_end'])
                          ->where('time_end', '>', $validated['time_start']);
                    });
                })
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();
            if ($groupConflict) {
                Log::info('Group conflict found', ['group' => $group, 'conflict' => $groupConflict->toArray()]);
                $conflicts[] = "Group $group is already assigned to session {$groupConflict->course_code} at {$groupConflict->venue->name} from {$groupConflict->time_start} to {$groupConflict->time_end}.";
            }
        }

        return $conflicts;
    }

    public function getFacultyCourses(Request $request)
    {
        if (!TimetableSemester::exists()) {
            return response()->json(['errors' => ['semester' => 'No timetable semester configured.']], 422);
        }

        $facultyId = $request->query('faculty_id');
        $timetableSemester = TimetableSemester::getFirstSemester();
        $courses = Course::whereHas('faculties', fn($q) => $q->where('faculties.id', $facultyId))
            ->where('semester_id', $timetableSemester->semester_id)
            ->select('course_code', 'name', 'practical_hrs')
            ->get()
            ->map(function ($course) {
                return [
                    'course_code' => $course->course_code,
                    'name' => $course->name,
                    'practical_hrs' => $course->practical_hrs
                ];
            })
            ->toArray();
        return response()->json([
            'course_codes' => $courses,
            'semester_name' => $timetableSemester->semester->name
        ]);
    }

    public function getCourseDetails(Request $request)
    {
        if (!TimetableSemester::exists()) {
            return response()->json(['errors' => ['semester' => 'No timetable semester configured.']], 422);
        }

        $timetableSemester = TimetableSemester::getFirstSemester();
        $course = Course::where('course_code', $request->course_code)
            ->where('semester_id', $timetableSemester->semester_id)
            ->firstOrFail();
        return response()->json(['practical_hrs' => $course->practical_hrs]);
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
            return $faculty->total_students_no ?? FacultyGroup::where('faculty_id', $faculty->id)->sum('student_count');
        }
        $groups = explode(',', $groupSelection);
        return FacultyGroup::where('faculty_id', $faculty->id)
            ->whereIn('group_name', $groups)
            ->sum('student_count');
    }

    public function getCourseLecturers(Request $request)
    {
        if (!TimetableSemester::exists()) {
            return response()->json(['errors' => ['semester' => 'No timetable semester configured.']], 422);
        }

        $timetableSemester = TimetableSemester::getFirstSemester();
        $course = Course::where('course_code', $request->course_code)
            ->where('semester_id', $timetableSemester->semester_id)
            ->first();
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
    
    
    public function venuesTimetable(Request $request)
{
    $venueId = $request->input('venue');
    $venues = Venue::orderBy('name')->get();

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];

    $timetables = collect();
    $selectedVenue = null;
    $timetableSemester = null;
    $error = null;

    if (!TimetableSemester::exists()) {
        $error = 'No timetable semester configured. Please add one first.';
    } elseif ($venueId) {
        $timetableSemester = TimetableSemester::getFirstSemester();
        $selectedVenue = Venue::findOrFail($venueId);

        $timetables = Timetable::where('venue_id', $venueId)
            ->where('semester_id', $timetableSemester->semester_id)
            ->with('faculty', 'lecturer', 'course')
            ->orderBy('day')
            ->orderBy('time_start')
            ->get();
    }

    return view('timetable.venues', compact(
        'timetables', 'venues', 'days', 'timeSlots',
        'venueId', 'selectedVenue', 'timetableSemester', 'error'
    ));
}

public function exportVenueTimetable(Request $request)
{
    $venueId = request()->query('venue');
    if (!$venueId) {
        return redirect()->route('venues.timetable')->with('error', 'Please select a venue to export.');
    }

    $venue = Venue::findOrFail($venueId);
    $timetableSemester = TimetableSemester::getFirstSemester();

    $timetables = Timetable::where('venue_id', $venueId)
        ->where('semester_id', $timetableSemester->semester_id)
        ->with('faculty', 'lecturer')
        ->orderBy('day')
        ->orderBy('time_start')
        ->get();

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];

    // SANITIZE EVERYTHING PROPERLY
    $safeVenueName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $venue->name);
    $safeAcademicYear = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $timetableSemester->academic_year);

    $filename = "Venue_Timetable_{$safeVenueName}_{$safeAcademicYear}.pdf";

    // Optional: Limit length and remove double underscores
    $filename = preg_replace('/_+/', '_', $filename);
    $filename = substr($filename, 0, 200); // Max safe length

    $pdf = Pdf::loadView('timetable.venue-pdf', compact(
        'venue', 'timetables', 'days', 'timeSlots', 'timetableSemester'
    ));

    // This is the KEY: Use setOptions to avoid header issues
    $pdf->getDomPDF()->setHttpContext(
        stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ])
    );

    return $pdf->download($filename);
}
}
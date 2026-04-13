<?php

namespace App\Http\Controllers;

use App\Imports\TimetableImport;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\FacultyGroup;
use App\Models\Semester;
use App\Models\Timetable;
use App\Models\TimetableSemester;
use App\Models\Venue;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TimetableController extends Controller
{
    private array $defaultDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    private array $defaultTimeSlots = [
        '08:00',
        '09:00',
        '10:00',
        '11:00',
        '12:00',
        '13:00',
        '14:00',
        '15:00',
        '16:00',
        '17:00',
        '18:00',
        '19:00',
    ];

    private array $forbiddenSlots = [
        ['day' => 'Tuesday', 'start_time' => '10:00', 'end_time' => '11:00'],
        ['day' => 'Friday', 'start_time' => '12:00', 'end_time' => '14:00'],
    ];

    public function index(Request $request)
    {
        $timetables = collect();
        $facultyId = $request->input('faculty');
        $selectedSetupId = $request->input('setup_id');

        $faculties = Faculty::pluck('name', 'id');
        $days = $this->defaultDays;
        $timeSlots = [...$this->defaultTimeSlots, '20:00'];
        $venues = Venue::select('id', 'name', 'capacity')->get();
        $timetableSemesters = TimetableSemester::with('semester')->latest()->get();
        $semesters = Semester::all();

        $timetableSemester = $this->resolveRequestedSetup($selectedSetupId);

        if (!$timetableSemester) {
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
                'selectedSetupId' => null,
                'error' => 'No timetable setup available. Please create or activate one.',
            ]);
        }

        if ($facultyId) {
            $timetables = Timetable::where('faculty_id', $facultyId)
                ->where('semester_id', $timetableSemester->id)
                ->with(['faculty', 'lecturer', 'semester.semester', 'course'])
                ->orderBy('day')
                ->orderBy('time_start')
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
            'semesters',
            'selectedSetupId'
        ));
    }


    public function getFacultyCourses(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'setup_id' => 'nullable|exists:timetable_semesters,id',
        ]);

        $setup = $this->resolveRequestedSetup($request->query('setup_id'));
        if (!$setup) {
            return response()->json(['errors' => ['setup' => 'No setup found.']], 422);
        }

        $courses = Course::whereHas('faculties', fn($q) => $q->where('faculties.id', $request->faculty_id))
            ->where('semester_id', $setup->semester_id)
            ->select('course_code', 'name', 'practical_hrs', 'cross_catering', 'hours', 'session', 'is_workshop')
            ->orderBy('course_code')
            ->get()
            ->map(function ($course) use ($setup, $request) {
                $countQuery = Timetable::where('semester_id', $setup->id)
                    ->where('course_code', $course->course_code)
                    ->where('activity', 'Lecture')
                    ->select('day', 'time_start', 'time_end')
                    ->distinct();

                if (!(bool) $course->cross_catering) {
                    $countQuery->where('faculty_id', $request->faculty_id);
                }

                $scheduledCount = $countQuery->count();
                $requiredSessions = (int) $course->session;
                $remainingSessions = max(0, $requiredSessions - $scheduledCount);
                $isComplete = $remainingSessions <= 0;

                return [
                    'course_code' => $course->course_code,
                    'name' => $course->name,
                    'practical_hrs' => $course->practical_hrs,
                    'cross_catering' => (bool) $course->cross_catering,
                    'is_workshop' => (bool) $course->is_workshop,
                    'hours' => $course->hours,
                    'session' => $requiredSessions,
                    'scheduled_count' => $scheduledCount,
                    'remaining_sessions' => $remainingSessions,
                    'is_complete' => $isComplete,
                    'completion_text' => $isComplete
                        ? "Lecture complete ({$scheduledCount}/{$requiredSessions})"
                        : "Lecture remaining {$remainingSessions} of {$requiredSessions}",
                ];
            })
            ->values();

        return response()->json([
            'setup_id' => $setup->id,
            'semester_id' => $setup->semester_id,
            'semester_name' => $setup->semester?->name,
            'academic_year' => $setup->academic_year,
            'course_codes' => $courses,
        ]);
    }


    public function setupDecision(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'setup_id' => 'required|exists:timetable_semesters,id',
        ]);

        $setup = TimetableSemester::with('semester')->findOrFail($request->setup_id);
        $currentSetup = TimetableSemester::getCurrent();

        $targetCourses = Course::whereHas('faculties', fn($q) => $q->where('faculties.id', $request->faculty_id))
            ->where('semester_id', $setup->semester_id)
            ->pluck('course_code')
            ->values();

        if (!$currentSetup) {
            return response()->json([
                'same_semester' => false,
                'requires_decision' => false,
                'message' => 'No active setup exists. The selected setup can be used directly.',
                'target_courses' => $targetCourses,
            ]);
        }

        if ((int) $currentSetup->semester_id === (int) $setup->semester_id) {
            return response()->json([
                'same_semester' => true,
                'requires_decision' => false,
                'message' => 'Selected setup is in the same semester. Current faculty courses can be used directly.',
                'target_courses' => $targetCourses,
                'current_setup' => $currentSetup->only(['id', 'semester_id', 'academic_year']),
                'selected_setup' => $setup->only(['id', 'semester_id', 'academic_year']),
            ]);
        }

        $currentCourses = Course::whereHas('faculties', fn($q) => $q->where('faculties.id', $request->faculty_id))
            ->where('semester_id', $currentSetup->semester_id)
            ->pluck('course_code')
            ->values();

        return response()->json([
            'same_semester' => false,
            'requires_decision' => true,
            'message' => 'Selected setup belongs to a different semester. Choose whether to adopt current timetable structure, shift old timetable sessions, or swap to the selected semester course list.',
            'current_setup' => $currentSetup->only(['id', 'semester_id', 'academic_year']),
            'selected_setup' => $setup->only(['id', 'semester_id', 'academic_year']),
            'current_courses' => $currentCourses,
            'target_courses' => $targetCourses,
            'options' => [
                ['value' => 'keep_current', 'label' => 'Keep current course structure'],
                ['value' => 'shift_previous', 'label' => 'Shift previous timetable to selected setup'],
                ['value' => 'swap_courses', 'label' => 'Swap to selected semester courses'],
            ],
        ]);
    }

   public function generateTimetable(Request $request)
{
    Log::info('Generate timetable request', $request->all());

    try {
        $validated = $request->validate([
            'setup_id' => 'required|exists:timetable_semesters,id',
            'faculty_id' => 'required|exists:faculties,id',
            'courses' => 'required|array|min:1',
            'courses.*' => 'required|string|exists:courses,course_code',
            'lecturers' => 'required|array|min:1',
            'lecturers.*' => 'required|exists:users,id',
            'groups' => 'required|array|min:1',
            'groups.*' => 'required|array|min:1',
            'groups.*.*' => 'required|string',
            'venues' => 'required|array|min:1',
            'venues.*' => 'required|exists:venues,id',
            'activities' => 'required|array|min:1',
            'activities.*' => 'nullable|in:Practical,Workshop,Lecture',
            'generation_mode' => 'nullable|in:keep_current,shift_previous,swap_courses',
            'force_proceed' => 'nullable|boolean',
        ]);

        $setup = TimetableSemester::with('semester')->findOrFail($validated['setup_id']);
        $currentSetup = TimetableSemester::getCurrent();

        if (
            $currentSetup &&
            (int) $currentSetup->semester_id !== (int) $setup->semester_id &&
            empty($validated['generation_mode'])
        ) {
            return response()->json([
                'requires_decision' => true,
                'message' => 'This setup is in a different semester. Please choose how to handle course shifting/swapping before generation.',
                'options' => [
                    ['value' => 'keep_current', 'label' => 'Keep current course structure'],
                    ['value' => 'shift_previous', 'label' => 'Shift previous timetable'],
                    ['value' => 'swap_courses', 'label' => 'Swap to selected semester courses'],
                ],
            ], 409);
        }

        $venues = Venue::whereIn('id', $validated['venues'])
            ->select('id', 'name', 'capacity')
            ->get();

        if ($venues->isEmpty()) {
            return response()->json([
                'errors' => ['venues' => 'No venues selected.']
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Split selected courses into workshop vs non-workshop
        |--------------------------------------------------------------------------
        */
        $selectedCourses = Course::with(['faculties', 'lecturers'])
            ->whereIn('course_code', $validated['courses'])
            ->where('semester_id', $setup->semester_id)
            ->get()
            ->keyBy('course_code');

        $workshopRows = [];
        $workshopWarnings = [];
        $buildErrors = [];

        $normalValidated = $validated;
        $normalValidated['courses'] = [];
        $normalValidated['lecturers'] = [];
        $normalValidated['groups'] = [];
        $normalValidated['activities'] = [];

        foreach ($validated['courses'] as $index => $courseCode) {
            $course = $selectedCourses->get($courseCode);

            if (!$course) {
                $buildErrors[] = "Course {$courseCode} was not found in the selected setup semester.";
                continue;
            }

            $activity = $validated['activities'][$index] ?? 'Lecture';

            if ((bool) $course->is_workshop && strtolower((string) $activity) === 'workshop') {
                $courseWorkshopRows = [];
                $courseWarnings = [];
                $courseErrors = [];

                $this->generateWorkshopLikeCrossCating(
                    course: $course,
                    venues: $venues,
                    days: $this->defaultDays,
                    timetables: $courseWorkshopRows,
                    warnings: $courseWarnings,
                    errors: $courseErrors,
                    setupId: (int) $setup->id
                );

                if (!empty($courseErrors)) {
                    $buildErrors = array_merge($buildErrors, $courseErrors);
                    continue;
                }

                $workshopRows = array_merge($workshopRows, $courseWorkshopRows);
                $workshopWarnings = array_merge($workshopWarnings, $courseWarnings);
            } else {
                $normalValidated['courses'][] = $courseCode;
                $normalValidated['lecturers'][] = $validated['lecturers'][$index] ?? null;
                $normalValidated['groups'][] = $validated['groups'][$index] ?? ['All Groups'];
                $normalValidated['activities'][] = $activity ?: 'Lecture';
            }
        }

        if (!empty($buildErrors)) {
            return response()->json([
                'errors' => [
                    'generation' => implode(' ', array_values(array_unique($buildErrors))),
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Build and schedule normal sessions
        |--------------------------------------------------------------------------
        */
        $normalRows = [];
        $normalWarnings = [];
        $normalErrors = [];

        if (!empty($normalValidated['courses'])) {
            $sessions = $this->buildSessionsForGeneration(
                validated: $normalValidated,
                setup: $setup,
                venues: $venues
            );

            Log::info('Generated sessions', $sessions);

            if (!empty($sessions)) {
                $result = $this->scheduleGeneratedSessions(
                    sessions: $sessions,
                    days: $this->defaultDays,
                    timeSlots: $this->defaultTimeSlots,
                    venues: $venues,
                    setupId: (int) $setup->id
                );

                $normalRows = $result['timetables'] ?? [];
                $normalWarnings = $result['warnings'] ?? [];
                $normalErrors = $result['errors'] ?? [];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Combine all generated rows
        |--------------------------------------------------------------------------
        */
        $allRows = array_values(array_merge($normalRows, $workshopRows));
        $allWarnings = array_values(array_unique(array_merge($normalWarnings, $workshopWarnings)));
        $allErrors = array_values(array_unique($normalErrors));

        if (empty($allRows)) {
            return response()->json([
                'errors' => [
                    'scheduling' => !empty($allErrors)
                        ? implode(' ', $allErrors)
                        : 'Unable to generate a conflict-free timetable.',
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Warning preview before saving
        |--------------------------------------------------------------------------
        */
        if (!empty($allWarnings) && !$request->boolean('force_proceed')) {
            return response()->json([
                'success' => true,
                'message' => 'Timetable generated with warnings.',
                'timetables' => $allRows,
                'warnings' => $allWarnings,
                'proceed' => true,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Save everything in one transaction
        |--------------------------------------------------------------------------
        */
        DB::beginTransaction();

        $created = [];
        foreach ($allRows as $row) {
            $row['semester_id'] = (int) $setup->id;
            $created[] = Timetable::create($row);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Timetable generated successfully for selected setup.',
            'timetables' => $created,
            'setup' => $setup->only(['id', 'semester_id', 'academic_year']),
            'warnings' => $allWarnings,
        ]);
    }catch (\Illuminate\Validation\ValidationException $e) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        Log::error('Unexpected error in generateTimetable', [
            'error' => $e->getMessage()
        ]);
        return response()->json([
            'errors' => ['error' => $e->getMessage()]
        ], 422);
    }
}


    private function generateWorkshopLikeCrossCating(
    Course $course,
    \Illuminate\Support\Collection $venues,
    array $days,
    array &$timetables,
    array &$warnings,
    array &$errors,
    int $setupId
): void {
    $timeSlots = $this->defaultTimeSlots;
    $sessionDurations = $this->calculateSessionDurations((int) $course->hours, (int) $course->session);

    $allGroups = [];
    $facultyGroups = [];

    foreach ($course->faculties as $faculty) {
        $groups = FacultyGroup::where('faculty_id', $faculty->id)
            ->get(['id', 'faculty_id', 'group_name', 'student_count']);

        $facultyGroups[$faculty->id] = [];

        foreach ($groups as $group) {
            $allGroups[] = $group;
            $facultyGroups[$faculty->id][] = $group;
        }
    }

    if (empty($allGroups)) {
        $errors[] = "No groups found for workshop course {$course->course_code}.";
        return;
    }

    $lecturers = $course->lecturers->pluck('id')->toArray();

    if (empty($lecturers)) {
        $errors[] = "No lecturers available for workshop course {$course->course_code}.";
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Assign lecturers to groups evenly
    |--------------------------------------------------------------------------
    */
    $groupLecturerAssignments = [];
    shuffle($lecturers);

    foreach ($allGroups as $index => $group) {
        $groupLecturerAssignments[$group->id] = $lecturers[$index % count($lecturers)];
    }

    /*
    |--------------------------------------------------------------------------
    | Track scheduled count per group
    |--------------------------------------------------------------------------
    */
    $scheduledGroups = [];

    /*
    |--------------------------------------------------------------------------
    | For each required session, schedule faculty rounds
    |--------------------------------------------------------------------------
    */
    foreach (range(0, ((int) $course->session) - 1) as $sessionIndex) {
        $duration = $sessionDurations[$sessionIndex % count($sessionDurations)];

        foreach ($course->faculties as $faculty) {
            $facultyId = (int) $faculty->id;
            $groups = $facultyGroups[$facultyId] ?? [];

            if (empty($groups)) {
                continue;
            }

            shuffle($groups);

            /*
            |--------------------------------------------------------------------------
            | Same idea as cross-cating: max 2 groups per round
            |--------------------------------------------------------------------------
            */
            $roundsPerSession = (int) ceil(count($groups) / 2);

            for ($round = 0; $round < $roundsPerSession; $round++) {
                $roundGroups = array_slice($groups, $round * 2, 2);

                if (empty($roundGroups)) {
                    break;
                }

                $roundSessions = [];

                foreach ($roundGroups as $group) {
                    $lecturerId = $groupLecturerAssignments[$group->id] ?? null;

                    if (!$lecturerId) {
                        $errors[] = "No lecturer assigned to group {$group->group_name} for {$course->course_code}.";
                        return;
                    }

                    $roundSessions[] = [
                        'lecturer_id' => (int) $lecturerId,
                        'student_count' => (int) $group->student_count,
                        'group_selection' => $group->group_name,
                        'faculty_id' => (int) $group->faculty_id,
                        'group' => $group,
                    ];
                }

                $scheduled = false;
                $maxRetries = 3;
                $retryCount = 0;

                while ($retryCount < $maxRetries && !$scheduled) {
                    $dayTimeCombinations = $this->getRandomizedWorkshopDayTimeCombinations($days, $timeSlots, $duration);

                    foreach ($dayTimeCombinations as $combo) {
                        $day = $combo['day'];
                        $startTime = $combo['start_time'];
                        $endTime = $combo['end_time'];

                        $availableVenues = $venues->values()->all();
                        shuffle($availableVenues);

                        $assignment = [];
                        $conflicts = false;

                        /*
                        |--------------------------------------------------------------------------
                        | First check faculty/group collisions
                        |--------------------------------------------------------------------------
                        */
                        foreach ($roundSessions as $session) {
                            $groupConflict = Timetable::where('semester_id', $setupId)
                                ->where('day', $day)
                                ->where('faculty_id', $session['faculty_id'])
                                ->where(function ($q) use ($startTime, $endTime) {
                                    $q->where('time_start', '<', date('H:i:s', strtotime($endTime)))
                                      ->where('time_end', '>', date('H:i:s', strtotime($startTime)));
                                })
                                ->where(function ($q) use ($session) {
                                    $q->where('group_selection', 'All Groups')
                                      ->orWhereRaw(
                                          "FIND_IN_SET(?, REPLACE(group_selection, ', ', ',')) > 0",
                                          [$session['group_selection']]
                                      );
                                })
                                ->exists();

                            if ($groupConflict) {
                                $conflicts = true;
                                break;
                            }
                        }

                        if ($conflicts) {
                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Assign different venue to each group in the round
                        |--------------------------------------------------------------------------
                        */
                        foreach ($roundSessions as $session) {
                            $assignedVenue = null;

                            for ($v = 0; $v < count($availableVenues); $v++) {
                                $venue = $availableVenues[$v];

                                if (((int) $venue['capacity']) + 15 < (int) $session['student_count']) {
                                    continue;
                                }

                                $venueConflict = Timetable::where('semester_id', $setupId)
                                    ->where('day', $day)
                                    ->where(function ($q) use ($startTime, $endTime) {
                                        $q->where('time_start', '<', date('H:i:s', strtotime($endTime)))
                                          ->where('time_end', '>', date('H:i:s', strtotime($startTime)));
                                    })
                                    ->get()
                                    ->contains(function ($row) use ($venue) {
                                        return in_array((int) $venue['id'], $this->extractVenueIds($row->venue_id), true);
                                    });

                                $lecturerConflict = Timetable::where('semester_id', $setupId)
                                    ->where('day', $day)
                                    ->where('lecturer_id', $session['lecturer_id'])
                                    ->where(function ($q) use ($startTime, $endTime) {
                                        $q->where('time_start', '<', date('H:i:s', strtotime($endTime)))
                                          ->where('time_end', '>', date('H:i:s', strtotime($startTime)));
                                    })
                                    ->exists();

                                if (!$venueConflict && !$lecturerConflict) {
                                    $assignedVenue = $venue;
                                    array_splice($availableVenues, $v, 1);
                                    break;
                                }
                            }

                            if ($assignedVenue === null) {
                                $conflicts = true;
                                break;
                            }

                            $assignment[] = [
                                'session' => $session,
                                'venue' => $assignedVenue,
                            ];
                        }

                        if ($conflicts) {
                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Save rows into array
                        |--------------------------------------------------------------------------
                        */
                        foreach ($assignment as $assign) {
                            $session = $assign['session'];
                            $venue = $assign['venue'];

                            if ((int) $venue['capacity'] < (int) $session['student_count']) {
                                $warnings[] = "Venue {$venue['name']} is slightly below capacity for group {$session['group_selection']} in {$course->course_code}, but within 15-student buffer.";
                            }

                            $timetables[] = [
                                'day' => $day,
                                'time_start' => date('H:i:s', strtotime($startTime)),
                                'time_end' => date('H:i:s', strtotime($endTime)),
                                'course_code' => $course->course_code,
                                'activity' => 'Workshop',
                                'venue_id' => (string) $venue['id'],
                                'lecturer_id' => (int) $session['lecturer_id'],
                                'group_selection' => $session['group_selection'],
                                'faculty_id' => (int) $session['faculty_id'],
                                'semester_id' => $setupId,
                            ];

                            $scheduledGroups[$session['group_selection']] = ($scheduledGroups[$session['group_selection']] ?? 0) + 1;
                        }

                        $scheduled = true;
                        break;
                    }

                    $retryCount++;
                }

                if (!$scheduled) {
                    $errors[] = "Could not schedule workshop round " . ($round + 1) . " for faculty {$faculty->name} in course {$course->course_code}.";
                    return;
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Final completeness check
    |--------------------------------------------------------------------------
    */
    foreach ($allGroups as $group) {
        $done = $scheduledGroups[$group->group_name] ?? 0;

        if ($done < (int) $course->session) {
            $errors[] = "Group {$group->group_name} was not scheduled for all workshop sessions in {$course->course_code}.";
            $timetables = [];
            return;
        }
    }
}

private function getRandomizedWorkshopDayTimeCombinations(array $days, array $timeSlots, int $duration): array
{
    $combinations = [];

    foreach ($days as $day) {
        foreach ($timeSlots as $startTime) {
            $endTime = date('H:i', strtotime($startTime) + ($duration * 3600));

            if (strtotime($endTime) > strtotime('20:00')) {
                continue;
            }

            $isForbidden =
                ($day === 'Tuesday' && $startTime === '10:00') ||
                ($day === 'Friday' && $startTime === '12:00');

            if (!$isForbidden) {
                $combinations[] = [
                    'day' => $day,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ];
            }
        }
    }

    shuffle($combinations);

    return $combinations;
}

private function validateCrossLectureSchedulingCompleteness(array $sessions, array $timetables): void
{
    $expectedByCourse = [];

    foreach ($sessions as $session) {
        if (($session['strategy'] ?? null) !== 'cross_non_workshop') {
            continue;
        }

        $courseCode = $session['course_code'];
        $expectedByCourse[$courseCode] = ($expectedByCourse[$courseCode] ?? 0)
            + count($session['faculty_rows'] ?? []) * (int) ($session['sessions_per_week'] ?? 1);
    }

    if (empty($expectedByCourse)) {
        return;
    }

    foreach ($expectedByCourse as $courseCode => $expectedRows) {
        $actualRows = collect($timetables)
            ->where('activity', 'Lecture')
            ->where('course_code', $courseCode)
            ->count();

        if ($actualRows < $expectedRows) {
            throw new \Exception(
                "Cross lecture scheduling incomplete for {$courseCode}. Expected {$expectedRows} lecture timetable rows but only {$actualRows} were scheduled. No partial lecture timetable should be saved."
            );
        }
    }
}

    public function getCourseLecturers(Request $request)
    {
        $request->validate([
            'course_code' => 'required|string|exists:courses,course_code',
            'setup_id' => 'nullable|exists:timetable_semesters,id',
        ]);

        $setup = $this->resolveRequestedSetup($request->input('setup_id'));

        if (!$setup) {
            return response()->json([
                'errors' => ['setup' => 'No timetable setup configured.']
            ], 422);
        }

        $course = Course::where('course_code', $request->course_code)
            ->where('semester_id', $setup->semester_id)
            ->first();

        if (!$course) {
            return response()->json(['lecturers' => []]);
        }

        $lecturers = $course->lecturers()
            ->select('users.id', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->toArray();

        return response()->json([
            'setup_id' => $setup->id,
            'semester_id' => $setup->semester_id,
            'lecturers' => $lecturers,
        ]);
    }

    private function overlaps(string $startA, string $endA, string $startB, string $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }

    private function hasReservedLecturerConflict(
        int $lecturerId,
        string $day,
        string $startTime,
        string $endTime,
        array $reservedLecturerSlots
    ): bool {
        if (empty($reservedLecturerSlots[$lecturerId][$day])) {
            return false;
        }

        foreach ($reservedLecturerSlots[$lecturerId][$day] as $slot) {
            if ($this->overlaps($startTime, $endTime, $slot['start'], $slot['end'])) {
                return true;
            }
        }

        return false;
    }

    private function hasReservedFacultyConflict(
        int $facultyId,
        string $day,
        string $startTime,
        string $endTime,
        array $reservedFacultySlots
    ): bool {
        if (empty($reservedFacultySlots[$facultyId][$day])) {
            return false;
        }

        foreach ($reservedFacultySlots[$facultyId][$day] as $slot) {
            if ($this->overlaps($startTime, $endTime, $slot['start'], $slot['end'])) {
                return true;
            }
        }

        return false;
    }

    private function hasReservedVenueConflict(
        int $venueId,
        string $day,
        string $startTime,
        string $endTime,
        array $reservedVenueSlots
    ): bool {
        if (empty($reservedVenueSlots[$venueId][$day])) {
            return false;
        }

        foreach ($reservedVenueSlots[$venueId][$day] as $slot) {
            if ($this->overlaps($startTime, $endTime, $slot['start'], $slot['end'])) {
                return true;
            }
        }

        return false;
    }

    private function hasReservedGroupConflict(
        int $facultyId,
        array $groups,
        string $day,
        string $startTime,
        string $endTime,
        array $reservedGroupSlots
    ): bool {
        foreach ($groups as $group) {
            if (empty($reservedGroupSlots[$facultyId][$group][$day])) {
                continue;
            }

            foreach ($reservedGroupSlots[$facultyId][$group][$day] as $slot) {
                if ($this->overlaps($startTime, $endTime, $slot['start'], $slot['end'])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function expandGroupsForConflictCheck(int $facultyId, string $groupSelection): array
    {
        $groups = array_map('trim', explode(',', $groupSelection));

        if (($groups[0] ?? null) === 'All Groups') {
            return FacultyGroup::where('faculty_id', $facultyId)
                ->pluck('group_name')
                ->map(fn($g) => trim($g))
                ->filter()
                ->values()
                ->all();
        }

        return array_values(array_filter($groups));
    }


 public function store(Request $request)
{
    try {
        $validated = $this->validateManualTimetableRequest($request);
        $setup = $this->resolveRequestedSetup($request->input('setup_id')) ?? $this->requireActiveSemesterSetup();
        $payload = $this->normalizeManualPayload($validated, (int) $setup->id);

        $isSharedCross = $this->isSharedCrossNonWorkshopCourse($payload['course_code']);

        $course = Course::where('course_code', $payload['course_code'])->firstOrFail();
        $isCross = (bool) $course->cross_catering;
        $isWorkshop = (bool) $course->is_workshop;

        $this->assertCourseSessionQuotaAvailable(
            setup: $setup,
            courseCode: $payload['course_code'],
            facultyId: (int) $payload['faculty_id'],
            isCrossCatering: $isSharedCross,
            activity: $payload['activity'] ?? 'Lecture'
        );

        DB::beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | Cross-catering NON-workshop:
        | attach to existing shared slot if found, else create shared set
        |--------------------------------------------------------------------------
        */
        if ($isSharedCross) {
            $this->assertVenueAvailability(
                setupId: (int) $setup->id,
                day: $payload['day'],
                startTime: $payload['time_start'],
                endTime: $payload['time_end'],
                requestedVenueIds: $this->extractVenueIds($payload['venue_id']),
                excludeIds: []
            );

            $existingSharedRows = Timetable::where('semester_id', $setup->id)
                ->where('course_code', $payload['course_code'])
                ->where('activity', $payload['activity'])
                ->where('day', $payload['day'])
                ->where('time_start', $payload['time_start'])
                ->where('time_end', $payload['time_end'])
                ->orderBy('faculty_id')
                ->get();

            if ($existingSharedRows->isNotEmpty()) {
                $alreadyAttached = $existingSharedRows->contains(
                    fn($row) => (int) $row->faculty_id === (int) $payload['faculty_id']
                );

                if ($alreadyAttached) {
                    throw new \Exception('This faculty is already attached to the selected shared cross-catering slot.');
                }

                $attachPayload = $payload;
                $attachPayload['group_selection'] = 'All Groups';

                $this->assertGeneralConflicts($attachPayload, (int) $setup->id, []);

                $created = Timetable::create($attachPayload);

                DB::commit();

                return response()->json([
                    'message' => 'Faculty attached successfully to the existing cross-catering timetable slot.',
                    'id' => $created->id,
                    'setup_id' => $setup->id,
                    'attached_to_existing_slot' => true,
                    'is_cross_catering' => true,
                    'is_workshop' => false,
                ]);
            }

            $faculties = $this->getFacultiesForCourse($payload['course_code']);

            foreach ($faculties as $faculty) {
                $row = $payload;
                $row['faculty_id'] = (int) $faculty->id;
                $row['group_selection'] = 'All Groups';
                $this->assertGeneralConflicts($row, (int) $setup->id, []);
            }

            $createdIds = [];
            foreach ($faculties as $faculty) {
                $row = $payload;
                $row['faculty_id'] = (int) $faculty->id;
                $row['group_selection'] = 'All Groups';
                $created = Timetable::create($row);
                $createdIds[] = $created->id;
            }

            DB::commit();

            return response()->json([
                'message' => 'Cross-catering non-workshop timetable created successfully for all related faculties.',
                'ids' => $createdIds,
                'setup_id' => $setup->id,
                'attached_to_existing_slot' => false,
                'is_cross_catering' => true,
                'is_workshop' => false,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normal row path:
        | - non cross-catering
        | - cross-catering workshop (treated as normal single row)
        |--------------------------------------------------------------------------
        */
        $this->assertVenueAvailability(
            setupId: (int) $setup->id,
            day: $payload['day'],
            startTime: $payload['time_start'],
            endTime: $payload['time_end'],
            requestedVenueIds: $this->extractVenueIds($payload['venue_id']),
            excludeIds: []
        );

        $this->assertGeneralConflicts($payload, (int) $setup->id, []);

        $created = Timetable::create($payload);

        DB::commit();

        return response()->json([
            'message' => $isCross && $isWorkshop
                ? 'Cross-catering workshop stored as a single timetable row successfully.'
                : 'Timetable entry created successfully.',
            'id' => $created->id,
            'setup_id' => $setup->id,
            'is_cross_catering' => $isCross,
            'is_workshop' => $isWorkshop,
        ]);
    } catch (\Exception $e) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
    }
}

public function update(Request $request, Timetable $timetable)
{
    try {
        $validated = $this->validateManualTimetableRequest($request);
        $setup = $this->resolveRequestedSetup($request->input('setup_id')) ?? $this->requireActiveSemesterSetup();
        $payload = $this->normalizeManualPayload($validated, (int) $setup->id);

        $oldCourse = Course::where('course_code', $timetable->course_code)->firstOrFail();
        $newCourse = Course::where('course_code', $payload['course_code'])->firstOrFail();

        $oldIsCross = (bool) $oldCourse->cross_catering;
        $oldIsWorkshop = (bool) $oldCourse->is_workshop;
        $oldIsSharedCross = $this->isSharedCrossNonWorkshopCourse($timetable->course_code);

        $newIsCross = (bool) $newCourse->cross_catering;
        $newIsWorkshop = (bool) $newCourse->is_workshop;
        $newIsSharedCross = $this->isSharedCrossNonWorkshopCourse($payload['course_code']);

        DB::beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | Shared cross non-workshop update:
        | update all linked rows together
        |--------------------------------------------------------------------------
        */
        if ($oldIsSharedCross || $newIsSharedCross) {
            if ($timetable->course_code !== $payload['course_code']) {
                throw new \Exception('Changing course code on a shared cross-catering non-workshop session is not allowed. Delete and recreate it instead.');
            }

            if ($newIsWorkshop) {
                throw new \Exception('A shared cross-catering non-workshop session cannot be converted into workshop through edit. Delete and recreate it instead.');
            }

            $relatedRows = $this->getCrossSessionRows($timetable);
            $excludeIds = $relatedRows->pluck('id')->map(fn($id) => (int) $id)->all();

            $this->assertVenueAvailability(
                setupId: (int) $setup->id,
                day: $payload['day'],
                startTime: $payload['time_start'],
                endTime: $payload['time_end'],
                requestedVenueIds: $this->extractVenueIds($payload['venue_id']),
                excludeIds: $excludeIds
            );

            $faculties = $this->getFacultiesForCourse($timetable->course_code);

            foreach ($faculties as $faculty) {
                $row = $payload;
                $row['faculty_id'] = (int) $faculty->id;
                $row['group_selection'] = 'All Groups';
                $this->assertGeneralConflicts($row, (int) $setup->id, $excludeIds);
            }

            Timetable::whereIn('id', $excludeIds)->delete();

            $createdIds = [];
            foreach ($faculties as $faculty) {
                $row = $payload;
                $row['faculty_id'] = (int) $faculty->id;
                $row['group_selection'] = 'All Groups';
                $created = Timetable::create($row);
                $createdIds[] = $created->id;
            }

            DB::commit();

            return response()->json([
                'message' => 'Cross-catering non-workshop timetable updated for all linked faculties.',
                'ids' => $createdIds,
                'is_cross_catering' => true,
                'is_workshop' => false,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normal single-row update:
        | - non cross-catering
        | - cross-catering workshop
        |--------------------------------------------------------------------------
        */
        $excludeIds = [(int) $timetable->id];

        $this->assertVenueAvailability(
            setupId: (int) $setup->id,
            day: $payload['day'],
            startTime: $payload['time_start'],
            endTime: $payload['time_end'],
            requestedVenueIds: $this->extractVenueIds($payload['venue_id']),
            excludeIds: $excludeIds
        );

        $this->assertGeneralConflicts($payload, (int) $setup->id, $excludeIds);

        $timetable->update($payload);

        DB::commit();

        return response()->json([
            'message' => ($newIsCross && $newIsWorkshop)
                ? 'Cross-catering workshop updated as a single timetable row.'
                : 'Timetable updated successfully.',
            'id' => $timetable->id,
            'is_cross_catering' => $newIsCross,
            'is_workshop' => $newIsWorkshop,
        ]);
    } catch (\Exception $e) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
    }
}



   
    public function getFacultyGroups(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
        ]);

        $groups = FacultyGroup::where('faculty_id', $request->faculty_id)
            ->select('id', 'group_name')
            ->orderBy('group_name')
            ->get();

        return response()->json([
            'groups' => $groups,
        ]);
    }
    public function show(Timetable $timetable)
    {
        $timetable->load(['faculty', 'lecturer', 'semester.semester', 'course']);

        $groupSelection = $timetable->group_selection;
        $faculty = $timetable->faculty;

        if ($groupSelection === 'All Groups') {
            $studentCount = $faculty->total_students_no
                ?? FacultyGroup::where('faculty_id', $faculty->id)->sum('student_count');

            $groupDetails = "All Groups ({$studentCount} students)";
        } else {
            $groups = array_map('trim', explode(',', $groupSelection));
            $groupCounts = FacultyGroup::where('faculty_id', $faculty->id)
                ->whereIn('group_name', $groups)
                ->pluck('student_count', 'group_name');

            $details = [];
            $total = 0;

            foreach ($groups as $group) {
                $count = (int) ($groupCounts[$group] ?? 0);
                $details[] = "{$group} ({$count})";
                $total += $count;
            }

            $groupDetails = implode(', ', $details) . " - Total: {$total}";
        }

        $isCross = $this->isCrossCateringCourse($timetable->course_code);

        $mainVenueIds = $this->extractVenueIds($timetable->venue_id);
        $mainVenues = Venue::whereIn('id', $mainVenueIds)
            ->select('id', 'name', 'capacity')
            ->get()
            ->map(fn($venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'capacity' => $venue->capacity,
            ])
            ->values();

        $crossFaculties = [];
        $collisions = [
            'lecturer' => [],
            'faculty' => [],
            'group' => [],
            'venue' => [],
        ];

        if ($isCross) {
            $relatedRows = $this->getCrossSessionRows($timetable)->load('faculty', 'lecturer');

            $crossFaculties = $relatedRows->map(function ($row) {
                $venueIds = $this->extractVenueIds($row->venue_id);
                $venueNames = Venue::whereIn('id', $venueIds)->pluck('name')->values();

                return [
                    'id' => $row->id,
                    'faculty_id' => $row->faculty_id,
                    'faculty_name' => $row->faculty?->name ?? 'N/A',
                    'groups' => $row->group_selection,
                    'assigned_user' => $row->lecturer?->name ?? 'N/A',
                    'venue_ids' => $venueIds,
                    'venue_names' => $venueNames,
                    'activity' => $row->activity,
                    'time_start' => date('H:i', strtotime($row->time_start)),
                    'time_end' => date('H:i', strtotime($row->time_end)),
                ];
            })->values();
        } else {
            $overlappingRows = Timetable::with(['faculty', 'lecturer', 'course'])
                ->where('semester_id', $timetable->semester_id)
                ->where('day', $timetable->day)
                ->where('id', '!=', $timetable->id)
                ->where(function ($q) use ($timetable) {
                    $q->where('time_start', '<', $timetable->time_end)
                        ->where('time_end', '>', $timetable->time_start);
                })
                ->get();

            $lecturerRows = $overlappingRows->where('lecturer_id', $timetable->lecturer_id);
            $collisions['lecturer'] = $lecturerRows->map(function ($row) {
                $venueIds = $this->extractVenueIds($row->venue_id);
                $venueNames = Venue::whereIn('id', $venueIds)->pluck('name')->values();

                return [
                    'id' => $row->id,
                    'course_code' => $row->course_code,
                    'course_name' => $row->course?->name ?? 'N/A',
                    'faculty_id' => $row->faculty_id,
                    'faculty_name' => $row->faculty?->name ?? 'N/A',
                    'assigned_user' => $row->lecturer?->name ?? 'N/A',
                    'groups' => $row->group_selection,
                    'venue_ids' => $venueIds,
                    'venue_names' => $venueNames,
                    'time_start' => date('H:i', strtotime($row->time_start)),
                    'time_end' => date('H:i', strtotime($row->time_end)),
                    'activity' => $row->activity,
                ];
            })->values();

            $facultyRows = $overlappingRows->where('faculty_id', $timetable->faculty_id);
            $collisions['faculty'] = $facultyRows->map(function ($row) {
                $venueIds = $this->extractVenueIds($row->venue_id);
                $venueNames = Venue::whereIn('id', $venueIds)->pluck('name')->values();

                return [
                    'id' => $row->id,
                    'course_code' => $row->course_code,
                    'course_name' => $row->course?->name ?? 'N/A',
                    'faculty_id' => $row->faculty_id,
                    'faculty_name' => $row->faculty?->name ?? 'N/A',
                    'assigned_user' => $row->lecturer?->name ?? 'N/A',
                    'groups' => $row->group_selection,
                    'venue_ids' => $venueIds,
                    'venue_names' => $venueNames,
                    'time_start' => date('H:i', strtotime($row->time_start)),
                    'time_end' => date('H:i', strtotime($row->time_end)),
                    'activity' => $row->activity,
                ];
            })->values();

            $currentGroups = $timetable->group_selection === 'All Groups'
                ? FacultyGroup::where('faculty_id', $timetable->faculty_id)->pluck('group_name')->toArray()
                : array_map('trim', explode(',', $timetable->group_selection));

            $groupRows = $overlappingRows
                ->where('faculty_id', $timetable->faculty_id)
                ->filter(function ($row) use ($currentGroups) {
                    $rowGroups = $row->group_selection === 'All Groups'
                        ? FacultyGroup::where('faculty_id', $row->faculty_id)->pluck('group_name')->toArray()
                        : array_map('trim', explode(',', $row->group_selection));

                    return count(array_intersect($currentGroups, $rowGroups)) > 0;
                });

            $collisions['group'] = $groupRows->map(function ($row) {
                $venueIds = $this->extractVenueIds($row->venue_id);
                $venueNames = Venue::whereIn('id', $venueIds)->pluck('name')->values();

                return [
                    'id' => $row->id,
                    'course_code' => $row->course_code,
                    'course_name' => $row->course?->name ?? 'N/A',
                    'faculty_id' => $row->faculty_id,
                    'faculty_name' => $row->faculty?->name ?? 'N/A',
                    'assigned_user' => $row->lecturer?->name ?? 'N/A',
                    'groups' => $row->group_selection,
                    'venue_ids' => $venueIds,
                    'venue_names' => $venueNames,
                    'time_start' => date('H:i', strtotime($row->time_start)),
                    'time_end' => date('H:i', strtotime($row->time_end)),
                    'activity' => $row->activity,
                ];
            })->values();

            $venueRows = $overlappingRows->filter(function ($row) use ($mainVenueIds) {
                $rowVenueIds = $this->extractVenueIds($row->venue_id);
                return count(array_intersect($mainVenueIds, $rowVenueIds)) > 0;
            });

            $collisions['venue'] = $venueRows->map(function ($row) {
                $venueIds = $this->extractVenueIds($row->venue_id);
                $venueNames = Venue::whereIn('id', $venueIds)->pluck('name')->values();

                return [
                    'id' => $row->id,
                    'course_code' => $row->course_code,
                    'course_name' => $row->course?->name ?? 'N/A',
                    'faculty_id' => $row->faculty_id,
                    'faculty_name' => $row->faculty?->name ?? 'N/A',
                    'assigned_user' => $row->lecturer?->name ?? 'N/A',
                    'groups' => $row->group_selection,
                    'venue_ids' => $venueIds,
                    'venue_names' => $venueNames,
                    'time_start' => date('H:i', strtotime($row->time_start)),
                    'time_end' => date('H:i', strtotime($row->time_end)),
                    'activity' => $row->activity,
                ];
            })->values();
        }

        return response()->json([
            'id' => $timetable->id,
            'day' => $timetable->day,
            'time_start' => date('H:i', strtotime($timetable->time_start)),
            'time_end' => date('H:i', strtotime($timetable->time_end)),
            'course_code' => $timetable->course_code,
            'course_name' => $timetable->course?->name ?? 'N/A',
            'activity' => $timetable->activity,
            'venue_id' => $timetable->venue_id,
            'venue_ids' => $mainVenueIds,
            'venues' => $mainVenues,
            'venue_names' => $mainVenues->pluck('name')->values(),
            'lecturer_id' => $timetable->lecturer_id,
            'lecturer_name' => $timetable->lecturer?->name,
            'group_selection' => $timetable->group_selection,
            'group_selection_array' => array_map('trim', explode(',', $timetable->group_selection)),
            'faculty_id' => $timetable->faculty_id,
            'faculty_name' => $timetable->faculty?->name,
            'semester_id' => $timetable->semester_id,
            'setup_id' => $timetable->semester?->id,
            'semester_name' => $timetable->semester?->semester?->name ?? 'N/A',
            'academic_year' => $timetable->semester?->academic_year ?? 'N/A',
            'group_details' => $groupDetails,
            'is_cross_catering' => $isCross,
            'cross_related_count' => $isCross ? $this->getCrossSessionRows($timetable)->count() : 1,
            'cross_faculties' => $crossFaculties,
            'collisions' => $collisions,
        ]);
    }

    public function destroy(Request $request, Timetable $timetable)
{
    try {
        DB::beginTransaction();

        $setup = $this->resolveRequestedSetup($request->input('setup_id'));

        if ($setup && (int) $timetable->semester_id !== (int) $setup->id) {
            throw new \Exception('The selected timetable entry does not belong to the chosen setup.');
        }

        $course = Course::where('course_code', $timetable->course_code)->firstOrFail();
        $isCross = (bool) $course->cross_catering;
        $isWorkshop = (bool) $course->is_workshop;
        $isSharedCross = $this->isSharedCrossNonWorkshopCourse($timetable->course_code);

        /*
        |--------------------------------------------------------------------------
        | Shared cross non-workshop delete:
        | delete all linked rows
        |--------------------------------------------------------------------------
        */
        if ($isSharedCross) {
            $relatedRows = $this->getCrossSessionRows($timetable);
            $deletedIds = $relatedRows->pluck('id')->map(fn($id) => (int) $id)->values()->all();

            if (empty($deletedIds)) {
                throw new \Exception('No related cross-catering timetable rows found to delete.');
            }

            Timetable::whereIn('id', $deletedIds)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Cross-catering non-workshop timetable deleted successfully for all linked faculties.',
                'deleted_ids' => $deletedIds,
                'deleted_count' => count($deletedIds),
                'is_cross_catering' => true,
                'is_workshop' => false,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normal single-row delete:
        | - non cross-catering
        | - cross-catering workshop
        |--------------------------------------------------------------------------
        */
        $deletedId = (int) $timetable->id;
        $timetable->delete();

        DB::commit();

        return response()->json([
            'message' => ($isCross && $isWorkshop)
                ? 'Cross-catering workshop row deleted successfully.'
                : 'Timetable deleted successfully.',
            'deleted_ids' => [$deletedId],
            'deleted_count' => 1,
            'is_cross_catering' => $isCross,
            'is_workshop' => $isWorkshop,
        ]);
    } catch (\Exception $e) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        return response()->json([
            'errors' => [
                'error' => $e->getMessage(),
            ],
        ], 422);
    }
}

private function isSharedCrossNonWorkshopCourse(string $courseCode): bool
{
    $course = Course::where('course_code', $courseCode)->first();

    if (!$course) {
        throw new \Exception("Course {$courseCode} was deleted. Please refresh and select again.");
    }

    return (bool) $course->cross_catering && !(bool) $course->is_workshop;
}
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $import = new TimetableImport();
            Excel::import($import, $request->file('file'));

            if (!empty($import->errors)) {
                return redirect()->route('timetable.index')
                    ->withErrors(['import_errors' => $import->errors]);
            }

            return redirect()->route('timetable.index')
                ->with('success', 'Timetable imported successfully.');
        } catch (\Exception $e) {
            return redirect()->route('timetable.index')
                ->withErrors(['import_errors' => 'Failed to import timetable.']);
        }
    }

    public function export(Request $request)
    {
        $setup = $this->resolveRequestedSetup($request->query('setup_id')) ?? $this->requireActiveSemesterSetup();
        $draft = $request->query('draft', 'Final Draft');

        $faculties = Faculty::whereHas('timetables', function ($q) use ($setup) {
            $q->where('semester_id', $setup->id);
        })
            ->with([
                'timetables' => fn($q) => $q
                    ->where('semester_id', $setup->id)
                    ->orderBy('day')
                    ->orderBy('time_start'),
            ])
            ->orderBy('name')
            ->get();

        $days = $this->defaultDays;
        $timeSlots = $this->defaultTimeSlots;

        $safeAcademicYear = str_replace(['/', '\\'], '-', $setup->academic_year);
        $safeSemesterName = str_replace(['/', '\\'], '-', $setup->semester->name);
        $safeDraft = str_replace([' ', '/'], '-', $draft);
        $filename = "timetable_{$safeAcademicYear}_{$safeSemesterName}_{$safeDraft}.pdf";

        $timetableSemester = $setup;

        if ($faculties->isEmpty()) {
            return back()->withErrors([
                'export' => 'No timetable entries found for the selected setup.'
            ]);
        }

        $pdf = Pdf::loadView('timetable.pdf', compact(
            'faculties',
            'days',
            'timeSlots',
            'timetableSemester',
            'draft'
        ));

        return $pdf->download($filename);
    }

    private function resolveRequestedSetup($setupId): ?TimetableSemester
    {
        if ($setupId) {
            return TimetableSemester::with('semester')->find($setupId);
        }

        return TimetableSemester::getCurrent();
    }

    private function requireActiveSemesterSetup(): TimetableSemester
    {
        $setup = TimetableSemester::getCurrent();

        if (!$setup) {
            throw new \Exception('No active timetable setup configured.');
        }

        return $setup;
    }

    private function validateManualTimetableRequest(Request $request): array
    {
        return $request->validate([
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'faculty_id' => 'required|exists:faculties,id',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
            'course_code' => 'required|string|exists:courses,course_code',
            'activity' => 'nullable|in:Practical,Workshop,Lecture',
            'venue_id' => 'required|string|regex:/^(\d+)(,\d+)*$/',
            'lecturer_id' => 'required|exists:users,id',
            'group_selection' => 'required|array|min:1',
            'group_selection.*' => 'string',
        ]);
    }

    private function normalizeManualPayload(array $validated, int $setupId): array
    {
        $course = Course::where('course_code', $validated['course_code'])->firstOrFail();

        return [
            'day' => $validated['day'],
            'faculty_id' => (int) $validated['faculty_id'],
            'time_start' => $this->normalizeTime($validated['time_start']),
            'time_end' => $this->normalizeTime($validated['time_end']),
            'course_code' => $validated['course_code'],
            'activity' => $validated['activity'] ?? 'Lecture',
            'venue_id' => $this->normalizeVenueIdString($validated['venue_id']),
            'lecturer_id' => (int) $validated['lecturer_id'],
            'group_selection' => implode(',', $validated['group_selection']),
            'semester_id' => $setupId,
        ];
    }

    private function normalizeTime(string $time): string
    {
        return date('H:i:s', strtotime($time));
    }

    private function normalizeVenueIdString(string $venueId): string
    {
        return implode(',', $this->extractVenueIds($venueId));
    }

    private function extractVenueIds(string|int|null $venueValue): array
    {
        if ($venueValue === null || $venueValue === '') {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            array_filter(array_map('trim', explode(',', (string) $venueValue)))
        )));
    }

    private function isCrossCateringCourse(string $courseCode): bool
    {
        $course = Course::where('course_code', $courseCode)->first();
        if (!$course) {
            throw new \Exception("Course {$courseCode} was deleted. Please refresh and select again.");
        }

        return (bool) $course->cross_catering;
    }

    private function getFacultiesForCourse(string $courseCode): Collection
    {
        return Course::where('course_code', $courseCode)
            ->firstOrFail()
            ->faculties()
            ->select('faculties.id', 'faculties.name', 'faculties.program_id', 'faculties.total_students_no')
            ->orderBy('faculties.name')
            ->get();
    }

    private function getCrossSessionRows(Timetable $timetable): Collection
    {
        return Timetable::where('semester_id', $timetable->semester_id)
            ->where('course_code', $timetable->course_code)
            ->where('day', $timetable->day)
            ->where('time_start', $timetable->time_start)
            ->where('time_end', $timetable->time_end)
            ->where('activity', $timetable->activity)
            ->orderBy('faculty_id')
            ->get();
    }


    public function autoResolve(Request $request, Timetable $timetable)
    {
        try {
            DB::beginTransaction();

            $setup = $this->resolveRequestedSetup($request->input('setup_id'));

            if ($setup && (int) $timetable->semester_id !== (int) $setup->id) {
                throw new \Exception('The selected timetable entry does not belong to the chosen setup.');
            }

            $setupId = (int) ($setup?->id ?? $timetable->semester_id);

            $linkedRows = $this->getAutoResolveLinkedRows($timetable);
            if ($linkedRows->isEmpty()) {
                throw new \Exception('No linked timetable rows found for auto-resolve.');
            }

            $durationHours = max(
                1,
                (int) ceil((strtotime($timetable->time_end) - strtotime($timetable->time_start)) / 3600)
            );

            $excludeIds = $linkedRows->pluck('id')->map(fn($id) => (int) $id)->values()->all();
            $candidateSlots = $this->getCandidateSlots($this->defaultDays, $this->defaultTimeSlots, $durationHours);

            $bestOption = null;

            foreach ($candidateSlots as $slot) {
                if (
                    $slot['day'] === $timetable->day &&
                    $this->normalizeTime($slot['start_time']) === $timetable->time_start &&
                    $this->normalizeTime($slot['end_time']) === $timetable->time_end
                ) {
                    continue;
                }

                $session = [
                    'course_code' => $timetable->course_code,
                    'activity' => $timetable->activity ?? 'Lecture',
                    'lecturer_id' => (int) $timetable->lecturer_id,
                    'faculty_rows' => $linkedRows->map(fn($row) => [
                        'faculty_id' => (int) $row->faculty_id,
                        'group_selection' => $row->group_selection,
                        'student_count' => $this->calculateStudentCount(
                            Faculty::findOrFail($row->faculty_id),
                            $row->group_selection
                        ),
                    ])->values()->all(),
                ];

                $evaluated = $this->scoreAutoResolveCandidateSlot(
                    session: $session,
                    candidate: $slot,
                    setupId: $setupId,
                    excludeIds: $excludeIds
                );

                if (!$evaluated) {
                    continue;
                }

                if (!$bestOption || $evaluated['score'] < $bestOption['score']) {
                    $bestOption = $evaluated;
                }
            }

            if (!$bestOption) {
                throw new \Exception('No valid alternative slot was found for this timetable entry or linked group.');
            }

            $selectedVenueId = (int) ($bestOption['venue_ids'][0] ?? 0);

            if ($selectedVenueId <= 0) {
                throw new \Exception('No valid venue was selected for the scheduled session.');
            }

            foreach ($linkedRows as $row) {
                $row->update([
                    'day' => $bestOption['day'],
                    'time_start' => $this->normalizeTime($bestOption['start_time']),
                    'time_end' => $this->normalizeTime($bestOption['end_time']),
                    'venue_id' => $selectedVenueId,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Timetable auto-resolved successfully.',
                'moved_ids' => $linkedRows->pluck('id')->values(),
                'new_day' => $bestOption['day'],
                'new_time_start' => $bestOption['start_time'],
                'new_time_end' => $bestOption['end_time'],
                'new_venue_ids' => $bestOption['venue_ids'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'errors' => [
                    'error' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    private function scoreAutoResolveCandidateSlot(
        array $session,
        array $candidate,
        int $setupId,
        array $excludeIds = []
    ): ?array {
        $day = $candidate['day'];
        $startTime = $candidate['start_time'];
        $endTime = $candidate['end_time'];

        $payloadBase = [
            'day' => $day,
            'time_start' => $this->normalizeTime($startTime),
            'time_end' => $this->normalizeTime($endTime),
            'course_code' => $session['course_code'],
            'activity' => $session['activity'],
            'lecturer_id' => $session['lecturer_id'],
        ];

        foreach ($session['faculty_rows'] as $row) {
            $payload = array_merge($payloadBase, [
                'faculty_id' => (int) $row['faculty_id'],
                'group_selection' => $row['group_selection'],
                'venue_id' => '0',
            ]);

            $conflicts = $this->checkConflictsWithoutVenue($payload, $setupId, $excludeIds);
            if (!empty($conflicts)) {
                return null;
            }
        }

        $totalStudents = array_sum(array_map(fn($row) => (int) $row['student_count'], $session['faculty_rows']));
        $venues = Venue::select('id', 'name', 'capacity')->get();

        $pickedVenues = $this->pickBestVenuesForStudents(
            requiredStudents: $totalStudents,
            venues: $venues,
            day: $day,
            startTime: $startTime,
            reservedVenueSlots: [],
            setupId: $setupId,
            endTime: $endTime
        );

        if (empty($pickedVenues)) {
            return null;
        }

        $score = ($pickedVenues['fit_score'] ?? 0);
        if (strtotime($startTime) >= strtotime('17:00')) {
            $score += 40;
        }

        return [
            'score' => $score,
            'day' => $day,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'venue_ids' => $pickedVenues['venue_ids'],
        ];
    }

    private function checkConflictsWithoutVenue(array $payload, int $setupId, array $excludeIds = []): array
    {
        $conflicts = [];

        $lecturerConflict = Timetable::where('day', $payload['day'])
            ->where('lecturer_id', $payload['lecturer_id'])
            ->where('semester_id', $setupId)
            ->where(function ($q) use ($payload) {
                $q->where('time_start', '<', $payload['time_end'])
                    ->where('time_end', '>', $payload['time_start']);
            })
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->first();

        if ($lecturerConflict) {
            $conflicts[] = 'Lecturer conflict';
        }

        $groups = $this->expandGroupsForConflictCheck(
            (int) $payload['faculty_id'],
            $payload['group_selection']
        );

        foreach ($groups as $group) {
            $groupConflict = Timetable::where('day', $payload['day'])
                ->where('faculty_id', $payload['faculty_id'])
                ->where('semester_id', $setupId)
                ->where(function ($q) use ($payload) {
                    $q->where('time_start', '<', $payload['time_end'])
                        ->where('time_end', '>', $payload['time_start']);
                })
                ->where(function ($q) use ($group) {
                    $q->where('group_selection', 'All Groups')
                        ->orWhereRaw("FIND_IN_SET(?, REPLACE(group_selection, ', ', ',')) > 0", [$group]);
                })
                ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
                ->first();

            if ($groupConflict) {
                $conflicts[] = "Group {$group} conflict";
                break;
            }
        }

        return $conflicts;
    }



    private function sortSessionsForSmartScheduling(array $sessions): array
{
    usort($sessions, function ($a, $b) {
        $aRows = !empty($a['parallel_groups']) ? $a['parallel_groups'] : ($a['faculty_rows'] ?? []);
        $bRows = !empty($b['parallel_groups']) ? $b['parallel_groups'] : ($b['faculty_rows'] ?? []);

        $scoreA =
            (($a['cross_catering'] ?? false) ? 1000 : 0)
            + (count($aRows) * 250)
            + (array_sum(array_column($aRows, 'student_count')) * 2)
            + (max($a['hours_per_session'] ?? [1]) * 100)
            + (($a['sessions_per_week'] ?? 1) * 50);

        $scoreB =
            (($b['cross_catering'] ?? false) ? 1000 : 0)
            + (count($bRows) * 250)
            + (array_sum(array_column($bRows, 'student_count')) * 2)
            + (max($b['hours_per_session'] ?? [1]) * 100)
            + (($b['sessions_per_week'] ?? 1) * 50);

        return $scoreB <=> $scoreA;
    });

    return $sessions;
}

private function validateWorkshopSchedulingCompleteness(array $sessions, array $timetables): void
{
    $expectedWorkshopRows = 0;

    foreach ($sessions as $session) {
        if (($session['strategy'] ?? null) !== 'cross_workshop_round') {
            continue;
        }

        $expectedWorkshopRows += count($session['parallel_groups'] ?? []) * (int) ($session['sessions_per_week'] ?? 1);
    }

    if ($expectedWorkshopRows === 0) {
        return;
    }

    $actualWorkshopRows = collect($timetables)
        ->where('activity', 'Workshop')
        ->count();

    if ($actualWorkshopRows < $expectedWorkshopRows) {
        throw new \Exception(
            "Workshop scheduling incomplete. Expected {$expectedWorkshopRows} workshop timetable rows but only {$actualWorkshopRows} were scheduled. No partial workshop timetable should be saved."
        );
    }
}


    private function pickBestVenuesForStudents(
        int $requiredStudents,
        Collection $venues,
        string $day,
        string $startTime,
        array $reservedVenueSlots,
        int $setupId,
        string $endTime
    ): array {
        $available = $venues->filter(function ($venue) use ($day, $startTime, $endTime, $reservedVenueSlots, $setupId) {
            if ($this->hasReservedVenueConflict((int) $venue->id, $day, $startTime, $endTime, $reservedVenueSlots)) {
                return false;
            }

            $dbBusy = Timetable::where('semester_id', $setupId)
                ->where('day', $day)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('time_start', '<', $this->normalizeTime($endTime))
                        ->where('time_end', '>', $this->normalizeTime($startTime));
                })
                ->get()
                ->contains(function ($row) use ($venue) {
                    return in_array((int) $venue->id, $this->extractVenueIds($row->venue_id), true);
                });

            return !$dbBusy;
        })->values();

        if ($available->isEmpty()) {
            return [];
        }

        $singleBest = $available
            ->filter(fn($v) => (int) $v->capacity >= $requiredStudents)
            ->sortBy(fn($v) => ((int) $v->capacity - $requiredStudents))
            ->first();

        if (!$singleBest) {
            return [];
        }

        return [
            'venue_ids' => [(int) $singleBest->id],
            'warnings' => [],
            'fit_score' => (int) $singleBest->capacity - $requiredStudents,
        ];
    }





    private function scoreCandidateSlot(
        array $session,
        array $candidate,
        Collection $venues,
        array $reservedLecturerSlots,
        array $reservedFacultySlots,
        array $reservedVenueSlots,
        array $reservedGroupSlots,
        int $setupId
    ): ?array {
        if (($session['strategy'] ?? null) === 'cross_workshop_round') {
            return $this->scoreWorkshopRoundCandidateSlot(
                session: $session,
                candidate: $candidate,
                venues: $venues,
                reservedLecturerSlots: $reservedLecturerSlots,
                reservedFacultySlots: $reservedFacultySlots,
                reservedVenueSlots: $reservedVenueSlots,
                reservedGroupSlots: $reservedGroupSlots,
                setupId: $setupId
            );
        }

        $day = $candidate['day'];
        $startTime = $candidate['start_time'];
        $endTime = $candidate['end_time'];

        $score = 0;
        $courseSameDayCount = Timetable::where('semester_id', $setupId)
            ->where('course_code', $session['course_code'])
            ->where('day', $day)
            ->count();

        $score += ($courseSameDayCount * 120);

        if ($this->hasReservedLecturerConflict(
            (int) $session['lecturer_id'],
            $day,
            $startTime,
            $endTime,
            $reservedLecturerSlots
        )) {
            return null;
        }

        $lecturerConflict = Timetable::where('semester_id', $setupId)
            ->where('day', $day)
            ->where('lecturer_id', $session['lecturer_id'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('time_start', '<', $this->normalizeTime($endTime))
                    ->where('time_end', '>', $this->normalizeTime($startTime));
            })
            ->exists();

        if ($lecturerConflict) {
            return null;
        }

        foreach ($session['faculty_rows'] as $row) {
            $fid = (int) $row['faculty_id'];
            $groupSelection = (string) ($row['group_selection'] ?? 'All Groups');
            $groups = $this->expandGroupsForConflictCheck($fid, $groupSelection);

            if ($this->hasReservedFacultyConflict($fid, $day, $startTime, $endTime, $reservedFacultySlots)) {
                return null;
            }

            if ($this->hasReservedGroupConflict($fid, $groups, $day, $startTime, $endTime, $reservedGroupSlots)) {
                return null;
            }

            $facultyConflict = Timetable::where('semester_id', $setupId)
                ->where('day', $day)
                ->where('faculty_id', $fid)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('time_start', '<', $this->normalizeTime($endTime))
                        ->where('time_end', '>', $this->normalizeTime($startTime));
                })
                ->exists();

            if ($facultyConflict) {
                return null;
            }

            foreach ($groups as $group) {
                $groupConflict = Timetable::where('semester_id', $setupId)
                    ->where('day', $day)
                    ->where('faculty_id', $fid)
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->where('time_start', '<', $this->normalizeTime($endTime))
                            ->where('time_end', '>', $this->normalizeTime($startTime));
                    })
                    ->where(function ($q) use ($group) {
                        $q->where('group_selection', 'All Groups')
                            ->orWhereRaw("FIND_IN_SET(?, REPLACE(group_selection, ', ', ',')) > 0", [$group]);
                    })
                    ->exists();

                if ($groupConflict) {
                    return null;
                }
            }

            $dayLoad = Timetable::where('semester_id', $setupId)
                ->where('day', $day)
                ->where('faculty_id', $fid)
                ->count();

            $score += ($dayLoad * 15);
        }

        $lecturerDayLoad = Timetable::where('semester_id', $setupId)
            ->where('day', $day)
            ->where('lecturer_id', $session['lecturer_id'])
            ->count();

        $score += ($lecturerDayLoad * 20);

        if (strtotime($startTime) >= strtotime('17:00')) {
            $score += 40;
        }

        $totalStudents = array_sum(array_map(
            fn ($row) => (int) $row['student_count'],
            $session['faculty_rows']
        ));

        $pickedVenues = $this->pickBestVenuesForStudents(
            requiredStudents: $totalStudents,
            venues: $venues,
            day: $day,
            startTime: $startTime,
            reservedVenueSlots: $reservedVenueSlots,
            setupId: $setupId,
            endTime: $endTime
        );

        if (empty($pickedVenues)) {
            return null;
        }

        $score += ($pickedVenues['fit_score'] ?? 0);

        return [
            'score' => $score,
            'day' => $day,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'venue_ids' => $pickedVenues['venue_ids'],
            'warnings' => $pickedVenues['warnings'] ?? [],
        ];
    }


    public function availableVenues(Request $request)
    {
        $request->validate([
            'day' => 'required|string',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
            'faculty_id' => 'required|exists:faculties,id',
            'setup_id' => 'nullable|exists:timetable_semesters,id',
            'exclude_id' => 'nullable|integer',
        ]);

        $setup = $this->resolveRequestedSetup($request->input('setup_id'))
            ?? $this->requireActiveSemesterSetup();

        $venueIds = $this->findAvailableVenueIds(
            setupId: (int) $setup->id,
            day: $request->day,
            startTime: $this->normalizeTime($request->time_start),
            endTime: $this->normalizeTime($request->time_end),
            excludeIds: $request->filled('exclude_id') ? [(int) $request->exclude_id] : []
        );

        $venues = Venue::whereIn('id', $venueIds)
            ->select('id', 'name', 'capacity')
            ->orderBy('name')
            ->get();

        return response()->json([
            'setup_id' => $setup->id,
            'venues' => $venues->map(fn($v) => [
                'id' => $v->id,
                'text' => "{$v->name} (Capacity: {$v->capacity})",
            ])->values(),
        ]);
    }


    private function assertCourseSessionQuotaAvailable(
        TimetableSemester $setup,
        string $courseCode,
        int $facultyId,
        bool $isCrossCatering,
        string $activity
    ): void {
        // ✅ Only Lecture should respect course session quota
        if (strtolower(trim($activity)) !== 'lecture') {
            return;
        }

        $course = Course::where('course_code', $courseCode)
            ->where('semester_id', $setup->semester_id)
            ->firstOrFail();

        if ($isCrossCatering) {
            $scheduledCount = Timetable::where('semester_id', $setup->id)
                ->where('course_code', $courseCode)
                ->where('activity', 'Lecture')
                ->select('day', 'time_start', 'time_end')
                ->distinct()
                ->count();
        } else {
            $scheduledCount = Timetable::where('semester_id', $setup->id)
                ->where('course_code', $courseCode)
                ->where('faculty_id', $facultyId)
                ->where('activity', 'Lecture')
                ->select('day', 'time_start', 'time_end')
                ->distinct()
                ->count();
        }

        if ($scheduledCount >= (int) $course->session) {
            throw new \Exception("Lecture sessions for course {$courseCode} are already complete in the selected setup.");
        }
    }
    private function assertVenueAvailability(
        int $setupId,
        string $day,
        string $startTime,
        string $endTime,
        array $requestedVenueIds,
        array $excludeIds = []
    ): void {
        $availableIds = $this->findAvailableVenueIds($setupId, $day, $startTime, $endTime, $excludeIds);

        foreach ($requestedVenueIds as $venueId) {
            if (!in_array((int) $venueId, $availableIds, true)) {
                $venue = Venue::find($venueId);
                $name = $venue?->name ?? "ID {$venueId}";
                throw new \Exception("Venue {$name} is already booked for the selected time.");
            }
        }
    }

    private function findAvailableVenueIds(
        int $setupId,
        string $day,
        string $startTime,
        string $endTime,
        array $excludeIds = []
    ): array {
        $allVenues = Venue::pluck('id')->map(fn($id) => (int) $id)->all();

        $rows = Timetable::where('semester_id', $setupId)
            ->where('day', $day)
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('time_start', '<', $endTime)
                    ->where('time_end', '>', $startTime);
            })
            ->get(['id', 'venue_id']);

        $busy = [];
        foreach ($rows as $row) {
            foreach ($this->extractVenueIds($row->venue_id) as $venueId) {
                $busy[$venueId] = true;
            }
        }

        return array_values(array_filter($allVenues, fn($id) => !isset($busy[$id])));
    }

    private function assertGeneralConflicts(array $payload, int $setupId, array $excludeIds = []): void
    {
        $conflicts = $this->checkConflicts($payload, $setupId, $excludeIds);
        if (!empty($conflicts)) {
            throw new \Exception(implode(' ', array_unique($conflicts)));
        }
    }


    private function checkConflicts(array $payload, int $setupId, array $excludeIds = []): array
    {
        $conflicts = [];

        $lecturerConflict = Timetable::where('day', $payload['day'])
            ->where('lecturer_id', $payload['lecturer_id'])
            ->where('semester_id', $setupId)
            ->where(function ($q) use ($payload) {
                $q->where('time_start', '<', $payload['time_end'])
                    ->where('time_end', '>', $payload['time_start']);
            })
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->first();

        if ($lecturerConflict) {
            $conflicts[] = "Assigned user is already scheduled for {$lecturerConflict->course_code} on {$lecturerConflict->day} {$lecturerConflict->time_start}-{$lecturerConflict->time_end}.";
        }

        $requestedVenueIds = $this->extractVenueIds($payload['venue_id']);
        $venueRows = Timetable::where('day', $payload['day'])
            ->where('semester_id', $setupId)
            ->where(function ($q) use ($payload) {
                $q->where('time_start', '<', $payload['time_end'])
                    ->where('time_end', '>', $payload['time_start']);
            })
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->get();

        foreach ($venueRows as $row) {
            if (count(array_intersect($requestedVenueIds, $this->extractVenueIds($row->venue_id))) > 0) {
                $conflicts[] = "Venue conflict detected with {$row->course_code} on {$row->day} {$row->time_start}-{$row->time_end}.";
                break;
            }
        }

        $groups = array_map('trim', explode(',', $payload['group_selection']));
        if (($groups[0] ?? null) === 'All Groups') {
            $groups = FacultyGroup::where('faculty_id', $payload['faculty_id'])->pluck('group_name')->toArray();
        }

        foreach ($groups as $group) {
            $groupConflict = Timetable::where('day', $payload['day'])
                ->where('faculty_id', $payload['faculty_id'])
                ->where('semester_id', $setupId)
                ->where(function ($q) use ($payload) {
                    $q->where('time_start', '<', $payload['time_end'])
                        ->where('time_end', '>', $payload['time_start']);
                })
                ->where(function ($q) use ($group) {
                    $q->where('group_selection', 'All Groups')
                        ->orWhereRaw("FIND_IN_SET(?, REPLACE(group_selection, ', ', ',')) > 0", [$group]);
                })
                ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
                ->first();

            if ($groupConflict) {
                $conflicts[] = "Group {$group} is already assigned to {$groupConflict->course_code} on {$groupConflict->day} {$groupConflict->time_start}-{$groupConflict->time_end}.";
            }
        }

        return $conflicts;
    }


    private function buildSessionsForGeneration(array $validated, TimetableSemester $setup, Collection $venues): array
{
    $sessions = [];
    $seenCrossCourses = [];

    foreach ($validated['courses'] as $index => $courseCode) {
        $course = Course::where('course_code', $courseCode)
            ->where('semester_id', $setup->semester_id)
            ->firstOrFail();

        $activity = $validated['activities'][$index] ?? 'Lecture';
        $lecturerId = (int) $validated['lecturers'][$index];
        $groupSelection = isset($validated['groups'][$index])
            ? implode(',', $validated['groups'][$index])
            : 'All Groups';

        if ((bool) $course->cross_catering) {
            if (isset($seenCrossCourses[$courseCode])) {
                continue;
            }

            $seenCrossCourses[$courseCode] = true;

            if ((bool) $course->is_workshop) {
                $sessions = array_merge($sessions, $this->buildCrossWorkshopSessions(
                    course: $course,
                    setup: $setup,
                    defaultLecturerId: $lecturerId,
                    activity: 'Workshop'
                ));
            } else {
                $sessions = array_merge($sessions, $this->buildCrossLectureSessions(
                    course: $course,
                    setup: $setup,
                    defaultLecturerId: $lecturerId,
                    activity: 'Lecture',
                    venues: $venues
                ));
            }

            continue;
        }

        $sessions = array_merge($sessions, $this->buildNormalSessions(
            course: $course,
            setup: $setup,
            facultyId: (int) $validated['faculty_id'],
            lecturerId: $lecturerId,
            activity: $activity ?: 'Lecture',
            groupSelection: $groupSelection
        ));
    }

    return $sessions;
}


    private function buildNormalSessions(
        Course $course,
        TimetableSemester $setup,
        int $facultyId,
        int $lecturerId,
        string $activity,
        string $groupSelection
    ): array {
        $faculty = Faculty::findOrFail($facultyId);

        $existingCount = Timetable::where('semester_id', $setup->id)
            ->where('course_code', $course->course_code)
            ->where('faculty_id', $faculty->id)
            ->when(strtolower(trim($activity)) === 'lecture', fn($q) => $q->where('activity', 'Lecture'))
            ->count();

        $requiredSessions = max(0, (int) $course->session - $existingCount);

        if ($requiredSessions <= 0) {
            return [];
        }

        return [[
            'strategy' => 'normal',
            'course_code' => $course->course_code,
            'activity' => $activity,
            'lecturer_id' => $lecturerId,
            'cross_catering' => false,
            'is_workshop' => false,
            'faculty_rows' => [[
                'faculty_id' => $faculty->id,
                'group_selection' => $groupSelection,
                'student_count' => $this->calculateStudentCount($faculty, $groupSelection),
            ]],
            'sessions_per_week' => $requiredSessions,
            'hours_per_session' => $this->calculateSessionDurations((int) $course->hours, (int) $requiredSessions),
        ]];
    }


 private function buildCrossLectureSessions(
    Course $course,
    TimetableSemester $setup,
    int $defaultLecturerId,
    string $activity,
    Collection $venues
): array {
    $existingCount = Timetable::where('semester_id', $setup->id)
        ->where('course_code', $course->course_code)
        ->where('activity', 'Lecture')
        ->select('day', 'time_start', 'time_end')
        ->distinct()
        ->count();

    $requiredSessions = max(0, (int) $course->session - $existingCount);

    if ($requiredSessions <= 0) {
        return [];
    }

    $facultiesData = $course->faculties()
        ->select('faculties.id', 'faculties.name', 'faculties.total_students_no')
        ->get()
        ->map(function ($faculty) {
            return [
                'id' => (int) $faculty->id,
                'name' => $faculty->name ?? 'Unknown',
                'student_count' => (int) ($faculty->total_students_no ?? 0),
            ];
        })
        ->sortByDesc('student_count')
        ->values();

    if ($facultiesData->isEmpty()) {
        return [];
    }

    $facultyGroups = $this->groupFacultiesForCrossLecture(
        faculties: $facultiesData->toArray(),
        venues: $venues
    );

    if (empty($facultyGroups)) {
        return [];
    }

    $sessions = [];

    foreach ($facultyGroups as $group) {
        $sessions[] = [
            'strategy' => 'cross_non_workshop',
            'course_code' => $course->course_code,
            'activity' => 'Lecture',
            'lecturer_id' => $defaultLecturerId,
            'cross_catering' => true,
            'is_workshop' => false,
            'faculty_rows' => collect($group['faculties'])->map(fn ($faculty) => [
                'faculty_id' => (int) $faculty['id'],
                'group_selection' => 'All Groups',
                'student_count' => (int) $faculty['student_count'],
            ])->values()->all(),
            'sessions_per_week' => $requiredSessions,
            'hours_per_session' => $this->calculateSessionDurations((int) $course->hours, (int) $requiredSessions),
        ];
    }

    return $sessions;
}

    private function groupFacultiesForCrossLecture(array $faculties, Collection $venues): array
{
    if ($venues->isEmpty()) {
        throw new \Exception('No selected venues available for lecture batching.');
    }

    $maxVenueCapacity = ((int) $venues->max('capacity')) + 15;

    usort($faculties, fn ($a, $b) => $b['student_count'] <=> $a['student_count']);

    foreach ($faculties as $faculty) {
        if ((int) $faculty['student_count'] > $maxVenueCapacity) {
            throw new \Exception(
                "Faculty {$faculty['name']} has {$faculty['student_count']} students, which exceeds the largest selected venue capacity allowance ({$maxVenueCapacity})."
            );
        }
    }

    $groups = [];
    $remaining = $faculties;

    while (!empty($remaining)) {
        $current = [
            'faculties' => [],
            'student_count' => 0,
        ];
        $unassigned = [];

        foreach ($remaining as $faculty) {
            if ($current['student_count'] + (int) $faculty['student_count'] <= $maxVenueCapacity) {
                $current['faculties'][] = $faculty;
                $current['student_count'] += (int) $faculty['student_count'];
            } else {
                $unassigned[] = $faculty;
            }
        }

        if (empty($current['faculties'])) {
            throw new \Exception('Unable to form any valid lecture batch using the selected venues.');
        }

        $fitsSelectedVenue = $venues->contains(
            fn ($venue) => ((int) $venue->capacity + 15) >= (int) $current['student_count']
        );

        if (!$fitsSelectedVenue) {
            throw new \Exception(
                "No selected venue can hold a lecture batch of {$current['student_count']} students, even with the 15-student buffer."
            );
        }

        $groups[] = $current;
        $remaining = $unassigned;
    }

    return $groups;
}

  private function buildCrossWorkshopSessions(
    Course $course,
    TimetableSemester $setup,
    int $defaultLecturerId,
    string $activity
): array {
    $existingCount = Timetable::where('semester_id', $setup->id)
        ->where('course_code', $course->course_code)
        ->where('activity', 'Workshop')
        ->select('day', 'time_start', 'time_end')
        ->distinct()
        ->count();

    $requiredSessions = max(0, (int) $course->session - $existingCount);

    if ($requiredSessions <= 0) {
        return [];
    }

    $lecturerIds = $course->lecturers()
        ->pluck('users.id')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    if (empty($lecturerIds)) {
        $lecturerIds = [$defaultLecturerId];
    }

    shuffle($lecturerIds);
    $lecturerPointer = 0;
    $sessions = [];

    foreach ($course->faculties as $faculty) {
        $groups = FacultyGroup::where('faculty_id', $faculty->id)
            ->select('id', 'faculty_id', 'group_name', 'student_count')
            ->orderBy('group_name')
            ->get()
            ->map(fn ($group) => [
                'group_id' => (int) $group->id,
                'faculty_id' => (int) $group->faculty_id,
                'group_selection' => $group->group_name,
                'student_count' => (int) $group->student_count,
            ])
            ->values()
            ->all();

        if (empty($groups)) {
            continue;
        }

        $groupChunks = array_chunk($groups, 2);

        foreach ($groupChunks as $chunk) {
            $parallelGroups = [];

            foreach ($chunk as $group) {
                $parallelGroups[] = [
                    'faculty_id' => $group['faculty_id'],
                    'group_selection' => $group['group_selection'],
                    'student_count' => $group['student_count'],
                    'lecturer_id' => $lecturerIds[$lecturerPointer % count($lecturerIds)],
                ];
                $lecturerPointer++;
            }

            $sessions[] = [
                'strategy' => 'cross_workshop_round',
                'course_code' => $course->course_code,
                'activity' => 'Workshop',
                'cross_catering' => true,
                'is_workshop' => true,
                'parallel_groups' => $parallelGroups,
                'sessions_per_week' => $requiredSessions,
                'hours_per_session' => $this->calculateSessionDurations((int) $course->hours, (int) $requiredSessions),
            ];
        }
    }

    return $sessions;
}




private function scheduleNonWorkshopSessions(
    array $sessions,
    array $days,
    array $timeSlots,
    Collection $venues,
    int $setupId
): array {
    $timetables = [];
    $warnings = [];
    $errors = [];

    $reservedLecturerSlots = [];
    $reservedFacultySlots = [];
    $reservedVenueSlots = [];
    $reservedGroupSlots = [];

    $sessions = $this->sortSessionsForSmartScheduling($sessions);

    foreach ($sessions as $session) {
        $durations = $session['hours_per_session'];
        $sessionsNeeded = (int) $session['sessions_per_week'];
        $scheduledCount = 0;

        for ($i = 0; $i < $sessionsNeeded; $i++) {
            $duration = (int) ($durations[$i] ?? end($durations));
            $candidates = $this->getCandidateSlots($days, $timeSlots, $duration);

            $bestOption = null;

            foreach ($candidates as $candidate) {
                $evaluated = $this->scoreCandidateSlot(
                    session: $session,
                    candidate: $candidate,
                    venues: $venues,
                    reservedLecturerSlots: $reservedLecturerSlots,
                    reservedFacultySlots: $reservedFacultySlots,
                    reservedVenueSlots: $reservedVenueSlots,
                    reservedGroupSlots: $reservedGroupSlots,
                    setupId: $setupId
                );

                if (!$evaluated) {
                    continue;
                }

                if (!$bestOption || $evaluated['score'] < $bestOption['score']) {
                    $bestOption = $evaluated;
                }
            }

            if (!$bestOption) {
    Log::warning('Failed to schedule non-workshop batch', [
        'course_code' => $session['course_code'],
        'strategy' => $session['strategy'] ?? null,
        'faculty_rows' => $session['faculty_rows'] ?? [],
        'lecturer_id' => $session['lecturer_id'] ?? null,
        'session_index' => $i + 1,
        'sessions_per_week' => $session['sessions_per_week'] ?? null,
        'hours_per_session' => $session['hours_per_session'] ?? [],
    ]);

    $errors[] = "Could not schedule {$session['course_code']} session " . ($i + 1) . '.';
    continue;
}

            $day = $bestOption['day'];
            $startTime = $bestOption['start_time'];
            $endTime = $bestOption['end_time'];

            foreach (($bestOption['warnings'] ?? []) as $warning) {
                $warnings[] = $warning;
            }

            $selectedVenueId = (int) ($bestOption['venue_ids'][0] ?? 0);

            if ($selectedVenueId <= 0) {
                $errors[] = "No valid venue found for {$session['course_code']}.";
                continue;
            }

            foreach ($session['faculty_rows'] as $row) {
                $facultyId = (int) $row['faculty_id'];
                $groupSelection = (string) ($row['group_selection'] ?? 'All Groups');
                $groups = $this->expandGroupsForConflictCheck($facultyId, $groupSelection);

                $timetables[] = [
                    'day' => $day,
                    'time_start' => $this->normalizeTime($startTime),
                    'time_end' => $this->normalizeTime($endTime),
                    'course_code' => $session['course_code'],
                    'activity' => $session['activity'],
                    'venue_id' => $selectedVenueId,
                    'lecturer_id' => $session['lecturer_id'],
                    'group_selection' => $groupSelection,
                    'faculty_id' => $facultyId,
                ];

                $reservedFacultySlots[$facultyId][$day][] = [
                    'start' => $startTime,
                    'end' => $endTime,
                ];

                foreach ($groups as $group) {
                    $reservedGroupSlots[$facultyId][$group][$day][] = [
                        'start' => $startTime,
                        'end' => $endTime,
                    ];
                }
            }

            $reservedLecturerSlots[(int) $session['lecturer_id']][$day][] = [
                'start' => $startTime,
                'end' => $endTime,
            ];

            $reservedVenueSlots[$selectedVenueId][$day][] = [
                'start' => $startTime,
                'end' => $endTime,
            ];

            $scheduledCount++;
        }

        if ($scheduledCount === 0) {
            Log::warning('No session could be scheduled for non-workshop session', [
                'course_code' => $session['course_code'],
                'strategy' => $session['strategy'] ?? null,
                'faculty_rows' => $session['faculty_rows'] ?? [],
                'lecturer_id' => $session['lecturer_id'] ?? null,
            ]);

            $errors[] = "No session could be scheduled for {$session['course_code']}.";
        }
    }

    return [
        'timetables' => array_values($timetables),
        'warnings' => array_values(array_unique($warnings)),
        'errors' => array_values(array_unique($errors)),
    ];
}



private function scheduleGeneratedSessions(
    array $sessions,
    array $days,
    array $timeSlots,
    Collection $venues,
    int $setupId
): array {
    $workshopSessions = array_values(array_filter(
        $sessions,
        fn($s) => ($s['strategy'] ?? null) === 'cross_workshop_round'
    ));

    $otherSessions = array_values(array_filter(
        $sessions,
        fn($s) => ($s['strategy'] ?? null) !== 'cross_workshop_round'
    ));

    $workshopResult = [
        'timetables' => [],
        'warnings' => [],
        'errors' => [],
    ];

    if (!empty($workshopSessions)) {
        $workshopResult = $this->scheduleWorkshopRoundsOldStyle(
            sessions: $workshopSessions,
            days: $days,
            timeSlots: $timeSlots,
            venues: $venues,
            setupId: $setupId
        );
    }

    $normalResult = [
        'timetables' => [],
        'warnings' => [],
        'errors' => [],
    ];

    if (!empty($otherSessions)) {
        $normalResult = $this->scheduleNonWorkshopSessions(
            sessions: $otherSessions,
            days: $days,
            timeSlots: $timeSlots,
            venues: $venues,
            setupId: $setupId
        );
    }

    $mergedTimetables = array_values(array_merge(
        $normalResult['timetables'],
        $workshopResult['timetables']
    ));

    $mergedWarnings = array_values(array_unique(array_merge(
        $normalResult['warnings'],
        $workshopResult['warnings']
    )));

    $mergedErrors = array_values(array_unique(array_merge(
        $normalResult['errors'],
        $workshopResult['errors']
    )));

    $this->validateWorkshopSchedulingCompleteness($sessions, $mergedTimetables);
    $this->validateCrossLectureSchedulingCompleteness($sessions, $mergedTimetables);

    return [
        'timetables' => $mergedTimetables,
        'warnings' => $mergedWarnings,
        'errors' => $mergedErrors,
    ];
}



   private function getCandidateSlots(array $days, array $timeSlots, int $duration): array
{
    $candidates = [];

    foreach ($days as $day) {
        foreach ($timeSlots as $startTime) {
            $endTime = date('H:i', strtotime($startTime) + ($duration * 3600));

            if (strtotime($endTime) > strtotime('20:00')) {
                continue;
            }

            if ($this->isForbiddenTime($day, $startTime, $endTime)) {
                continue;
            }

            $candidates[] = [
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }
    }

    shuffle($candidates);

    return $candidates;
}


    private function isForbiddenTime(string $day, string $startTime, string $endTime): bool
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        foreach ($this->forbiddenSlots as $forbidden) {
            if ($forbidden['day'] !== $day) {
                continue;
            }

            $fStart = strtotime($forbidden['start_time']);
            $fEnd = strtotime($forbidden['end_time']);

            if ($start < $fEnd && $end > $fStart) {
                return true;
            }
        }

        return false;
    }

    private function pickVenuesForStudents(
        int $requiredStudents,
        Collection $venues,
        string $day,
        string $startTime,
        array $reservedVenueSlots,
        int $setupId,
        string $endTime
    ): array {
        $available = $venues->filter(function ($venue) use ($day, $startTime, $endTime, $reservedVenueSlots, $setupId) {
            if (isset($reservedVenueSlots[$day][$startTime][$venue->id])) {
                return false;
            }

            $dbBusy = Timetable::where('semester_id', $setupId)
                ->where('day', $day)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('time_start', '<', $this->normalizeTime($endTime))
                        ->where('time_end', '>', $this->normalizeTime($startTime));
                })
                ->get()
                ->contains(function ($row) use ($venue) {
                    return in_array((int) $venue->id, $this->extractVenueIds($row->venue_id), true);
                });

            return !$dbBusy;
        })->values();

        if ($available->isEmpty()) {
            return [];
        }

        $available = $available->sortBy('capacity')->values();
        $selected = [];
        $remaining = $requiredStudents;
        $warnings = [];

        foreach ($available as $venue) {
            if ($remaining <= 0) {
                break;
            }

            $selected[] = $venue;
            $remaining -= (int) $venue->capacity;
        }

        if ($remaining > 15) {
            return [];
        }

        if ($remaining > 0) {
            $warnings[] = "Selected venue set is within allowed overflow buffer for {$requiredStudents} students.";
        }

        return [
            'venue_ids' => collect($selected)->pluck('id')->map(fn($id) => (int) $id)->all(),
            'warnings' => $warnings,
        ];
    }

    private function calculateSessionDurations(int $totalHours, int $totalSessions): array
    {
        if ($totalHours < $totalSessions || $totalSessions <= 0) {
            throw new \Exception("Invalid combination of total hours ({$totalHours}) and sessions ({$totalSessions}).");
        }

        $baseHours = intdiv($totalHours, $totalSessions);
        $remainder = $totalHours % $totalSessions;
        $durations = array_fill(0, $totalSessions, max(1, $baseHours));

        for ($i = 0; $i < $remainder; $i++) {
            $durations[$i]++;
        }

        return $durations;
    }

    private function calculateStudentCount(Faculty $faculty, string $groupSelection): int
    {
        if ($groupSelection === 'All Groups') {
            return (int) ($faculty->total_students_no
                ?? FacultyGroup::where('faculty_id', $faculty->id)->sum('student_count'));
        }

        $groups = array_map('trim', explode(',', $groupSelection));

        return (int) FacultyGroup::where('faculty_id', $faculty->id)
            ->whereIn('group_name', $groups)
            ->sum('student_count');
    }


    private function getAutoResolveLinkedRows(Timetable $timetable): Collection
    {
        if (!$this->isCrossCateringCourse($timetable->course_code)) {
            return collect([$timetable]);
        }

        $baseQuery = Timetable::where('semester_id', $timetable->semester_id)
            ->where('course_code', $timetable->course_code)
            ->where('day', $timetable->day)
            ->where('time_start', $timetable->time_start)
            ->where('time_end', $timetable->time_end)
            ->where('activity', $timetable->activity);

        if ($timetable->group_selection === 'All Groups') {
            return $baseQuery
                ->where('group_selection', 'All Groups')
                ->orderBy('faculty_id')
                ->get();
        }

        return $baseQuery
            ->where('group_selection', $timetable->group_selection)
            ->orderBy('faculty_id')
            ->get();
    }


    private function scoreWorkshopRoundCandidateSlot(
        array $session,
        array $candidate,
        Collection $venues,
        array $reservedLecturerSlots,
        array $reservedFacultySlots,
        array $reservedVenueSlots,
        array $reservedGroupSlots,
        int $setupId
    ): ?array {
        $day = $candidate['day'];
        $startTime = $candidate['start_time'];
        $endTime = $candidate['end_time'];

        $parallelGroups = $session['parallel_groups'] ?? [];
        if (empty($parallelGroups)) {
            return null;
        }

        $facultyId = (int) $parallelGroups[0]['faculty_id'];

        if ($this->hasReservedFacultyConflict($facultyId, $day, $startTime, $endTime, $reservedFacultySlots)) {
            return null;
        }

        $existingFacultyRows = Timetable::where('semester_id', $setupId)
            ->where('day', $day)
            ->where('faculty_id', $facultyId)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('time_start', '<', $this->normalizeTime($endTime))
                    ->where('time_end', '>', $this->normalizeTime($startTime));
            })
            ->count();

        if ($existingFacultyRows >= 2) {
            return null;
        }

        foreach ($parallelGroups as $group) {
            $lecturerId = (int) $group['lecturer_id'];

            if ($this->hasReservedLecturerConflict($lecturerId, $day, $startTime, $endTime, $reservedLecturerSlots)) {
                return null;
            }

            $lecturerConflict = Timetable::where('semester_id', $setupId)
                ->where('day', $day)
                ->where('lecturer_id', $lecturerId)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('time_start', '<', $this->normalizeTime($endTime))
                        ->where('time_end', '>', $this->normalizeTime($startTime));
                })
                ->exists();

            if ($lecturerConflict) {
                return null;
            }

            if ($this->hasReservedGroupConflict(
                (int) $group['faculty_id'],
                [(string) $group['group_selection']],
                $day,
                $startTime,
                $endTime,
                $reservedGroupSlots
            )) {
                return null;
            }

            $dbGroupConflict = Timetable::where('semester_id', $setupId)
                ->where('day', $day)
                ->where('faculty_id', (int) $group['faculty_id'])
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('time_start', '<', $this->normalizeTime($endTime))
                        ->where('time_end', '>', $this->normalizeTime($startTime));
                })
                ->where(function ($q) use ($group) {
                    $q->where('group_selection', 'All Groups')
                        ->orWhereRaw("FIND_IN_SET(?, REPLACE(group_selection, ', ', ',')) > 0", [$group['group_selection']]);
                })
                ->exists();

            if ($dbGroupConflict) {
                return null;
            }
        }

        $venueAssignments = $this->pickWorkshopRoundVenues(
            parallelGroups: $parallelGroups,
            venues: $venues,
            day: $day,
            startTime: $startTime,
            reservedVenueSlots: $reservedVenueSlots,
            setupId: $setupId,
            endTime: $endTime
        );

        if (empty($venueAssignments)) {
            return null;
        }

        $score = 0;
        if (strtotime($startTime) >= strtotime('17:00')) {
            $score += 40;
        }

        return [
            'score' => $score,
            'day' => $day,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'parallel_assignments' => $venueAssignments,
            'warnings' => [],
        ];
    }


private function scheduleWorkshopRoundsOldStyle(
    array $sessions,
    array $days,
    array $timeSlots,
    Collection $venues,
    int $setupId
): array {
    $timetables = [];
    $warnings = [];
    $errors = [];

    $reservedLecturerSlots = [];
    $reservedVenueSlots = [];
    $reservedGroupSlots = [];
    $reservedFacultySlots = [];

    foreach ($sessions as $session) {
        if (($session['strategy'] ?? null) !== 'cross_workshop_round') {
            continue;
        }

        $durations = $session['hours_per_session'];
        $sessionsNeeded = (int) $session['sessions_per_week'];
        $parallelGroups = $session['parallel_groups'] ?? [];

        for ($i = 0; $i < $sessionsNeeded; $i++) {
            $duration = (int) ($durations[$i] ?? end($durations));
            $combinations = $this->getCandidateSlots($days, $timeSlots, $duration);
            $scheduled = false;

            foreach ($combinations as $combo) {
                $day = $combo['day'];
                $startTime = $combo['start_time'];
                $endTime = $combo['end_time'];

                $facultyId = (int) ($parallelGroups[0]['faculty_id'] ?? 0);

                // only block same faculty if same faculty already has an overlapping round in memory
                if ($facultyId > 0 && $this->hasReservedFacultyConflict($facultyId, $day, $startTime, $endTime, $reservedFacultySlots)) {
                    continue;
                }

                $assignments = [];
                $usedVenueIds = [];
                $failed = false;

                foreach ($parallelGroups as $group) {
                    $lecturerId = (int) $group['lecturer_id'];
                    $groupName = (string) $group['group_selection'];
                    $groupFacultyId = (int) $group['faculty_id'];
                    $studentCount = (int) $group['student_count'];

                    if ($this->hasReservedLecturerConflict($lecturerId, $day, $startTime, $endTime, $reservedLecturerSlots)) {
                        $failed = true;
                        break;
                    }

                    $dbLecturerConflict = Timetable::where('semester_id', $setupId)
                        ->where('day', $day)
                        ->where('lecturer_id', $lecturerId)
                        ->where(function ($q) use ($startTime, $endTime) {
                            $q->where('time_start', '<', $this->normalizeTime($endTime))
                              ->where('time_end', '>', $this->normalizeTime($startTime));
                        })
                        ->exists();

                    if ($dbLecturerConflict) {
                        $failed = true;
                        break;
                    }

                    if ($this->hasReservedGroupConflict($groupFacultyId, [$groupName], $day, $startTime, $endTime, $reservedGroupSlots)) {
                        $failed = true;
                        break;
                    }

                    $dbGroupConflict = Timetable::where('semester_id', $setupId)
                        ->where('day', $day)
                        ->where('faculty_id', $groupFacultyId)
                        ->where(function ($q) use ($startTime, $endTime) {
                            $q->where('time_start', '<', $this->normalizeTime($endTime))
                              ->where('time_end', '>', $this->normalizeTime($startTime));
                        })
                        ->where(function ($q) use ($groupName) {
                            $q->where('group_selection', 'All Groups')
                              ->orWhereRaw("FIND_IN_SET(?, REPLACE(group_selection, ', ', ',')) > 0", [$groupName]);
                        })
                        ->exists();

                    if ($dbGroupConflict) {
                        $failed = true;
                        break;
                    }

                    $venue = $venues
                        ->filter(fn ($v) => !in_array((int) $v->id, $usedVenueIds, true))
                        ->filter(function ($v) use ($day, $startTime, $endTime, $reservedVenueSlots, $setupId) {
                            if ($this->hasReservedVenueConflict((int) $v->id, $day, $startTime, $endTime, $reservedVenueSlots)) {
                                return false;
                            }

                            $dbBusy = Timetable::where('semester_id', $setupId)
                                ->where('day', $day)
                                ->where(function ($q) use ($startTime, $endTime) {
                                    $q->where('time_start', '<', $this->normalizeTime($endTime))
                                      ->where('time_end', '>', $this->normalizeTime($startTime));
                                })
                                ->get()
                                ->contains(function ($row) use ($v) {
                                    return in_array((int) $v->id, $this->extractVenueIds($row->venue_id), true);
                                });

                            return !$dbBusy;
                        })
                        ->filter(fn ($v) => (int) $v->capacity >= $studentCount)
                        ->sortBy(fn ($v) => ((int) $v->capacity - $studentCount))
                        ->first();

                    if (!$venue) {
                        $failed = true;
                        break;
                    }

                    $usedVenueIds[] = (int) $venue->id;

                    $assignments[] = [
                        'faculty_id' => $groupFacultyId,
                        'group_selection' => $groupName,
                        'student_count' => $studentCount,
                        'lecturer_id' => $lecturerId,
                        'venue_id' => (int) $venue->id,
                    ];
                }

                if ($failed) {
                    continue;
                }

                foreach ($assignments as $assign) {
                    $timetables[] = [
                        'day' => $day,
                        'time_start' => $this->normalizeTime($startTime),
                        'time_end' => $this->normalizeTime($endTime),
                        'course_code' => $session['course_code'],
                        'activity' => 'Workshop',
                        'venue_id' => $assign['venue_id'],
                        'lecturer_id' => $assign['lecturer_id'],
                        'group_selection' => $assign['group_selection'],
                        'faculty_id' => $assign['faculty_id'],
                    ];

                    $reservedLecturerSlots[$assign['lecturer_id']][$day][] = [
                        'start' => $startTime,
                        'end' => $endTime,
                    ];

                    $reservedVenueSlots[$assign['venue_id']][$day][] = [
                        'start' => $startTime,
                        'end' => $endTime,
                    ];

                    $reservedGroupSlots[$assign['faculty_id']][$assign['group_selection']][$day][] = [
                        'start' => $startTime,
                        'end' => $endTime,
                    ];
                }

                if ($facultyId > 0) {
                    $reservedFacultySlots[$facultyId][$day][] = [
                        'start' => $startTime,
                        'end' => $endTime,
                    ];
                }

                $scheduled = true;
                break;
            }

            if (!$scheduled) {
                $errors[] = "Could not schedule workshop round for {$session['course_code']} (" .
                    implode(', ', array_map(fn ($g) => $g['group_selection'], $parallelGroups)) . ").";
            }
        }
    }

    return [
        'timetables' => array_values($timetables),
        'warnings' => array_values(array_unique($warnings)),
        'errors' => array_values(array_unique($errors)),
    ];
}
    private function pickWorkshopRoundVenues(
    array $parallelGroups,
    Collection $venues,
    string $day,
    string $startTime,
    array $reservedVenueSlots,
    int $setupId,
    string $endTime
): array {
    $available = $venues->filter(function ($venue) use ($day, $startTime, $endTime, $reservedVenueSlots, $setupId) {
        if ($this->hasReservedVenueConflict((int) $venue->id, $day, $startTime, $endTime, $reservedVenueSlots)) {
            return false;
        }

        $dbBusy = Timetable::where('semester_id', $setupId)
            ->where('day', $day)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('time_start', '<', $this->normalizeTime($endTime))
                    ->where('time_end', '>', $this->normalizeTime($startTime));
            })
            ->get()
            ->contains(function ($row) use ($venue) {
                return in_array((int) $venue->id, $this->extractVenueIds($row->venue_id), true);
            });

        return !$dbBusy;
    })->values();

    if ($available->isEmpty()) {
        return [];
    }

    $usedVenueIds = [];
    $assignments = [];

    foreach ($parallelGroups as $group) {
        $preferred = $available
            ->filter(fn ($v) => !in_array((int) $v->id, $usedVenueIds, true))
            ->filter(fn ($v) => (int) $v->capacity >= (int) $group['student_count'] && (int) $v->capacity <= 50)
            ->sortBy(fn ($v) => ((int) $v->capacity - (int) $group['student_count']))
            ->first();

        $chosen = $preferred;

        if (!$chosen) {
            $chosen = $available
                ->filter(fn ($v) => !in_array((int) $v->id, $usedVenueIds, true))
                ->filter(fn ($v) => (int) $v->capacity >= (int) $group['student_count'])
                ->sortBy(fn ($v) => ((int) $v->capacity - (int) $group['student_count']))
                ->first();
        }

        if (!$chosen) {
            return [];
        }

        $usedVenueIds[] = (int) $chosen->id;

        $assignments[] = [
            'faculty_id' => (int) $group['faculty_id'],
            'group_selection' => (string) $group['group_selection'],
            'student_count' => (int) $group['student_count'],
            'lecturer_id' => (int) $group['lecturer_id'],
            'venue_id' => (int) $chosen->id,
        ];
    }

    return $assignments;
}
}

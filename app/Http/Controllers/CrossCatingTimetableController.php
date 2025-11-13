<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Faculty;
use App\Models\FacultyGroup;
use App\Models\Timetable;
use App\Models\Venue;
use App\Models\TimetableSemester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrossCatingTimetableController extends Controller
{
    /**
     * Display a listing of all cross-catering courses.
     */
    public function index()
    {
        if (!TimetableSemester::exists()) {
            return view('timetable.crosscating', [
                'crossCateringCourses' => collect(),
                'venues' => Venue::all(),
                'error' => 'No timetable semester configured. Please add a timetable semester.'
            ]);
        }

        $timetableSemester = TimetableSemester::getFirstSemester();
        $crossCateringCourses = Course::with(['faculties', 'lecturers'])
            ->where('cross_catering', true)
            ->where('semester_id', $timetableSemester->semester_id)
            ->get();

        $venues = Venue::all();

        Log::info('Fetched cross-catering courses and venues for index', [
            'course_count' => $crossCateringCourses->count(),
            'venue_count' => $venues->count(),
            'semester_id' => $timetableSemester->semester_id
        ]);

        return view('timetable.crosscating', compact('crossCateringCourses', 'venues'));
    }

    /**
     * Generate timetable for a specific cross-catering course.
     */
    public function generateForCourse(Request $request, $courseId)
    {
        Log::info('Starting timetable generation for course', [
            'course_id' => $courseId,
            'request' => $request->all(),
            'timestamp' => now(),
        ]);

        try {
            if (!TimetableSemester::exists()) {
                return $request->ajax()
                    ? response()->json(['errors' => ['semester' => 'No timetable semester configured.']], 422)
                    : back()->withInput()->withErrors(['semester' => 'No timetable semester configured.']);
            }

            $timetableSemester = TimetableSemester::getFirstSemester();

            $request->validate([
                'venues' => 'required|array|min:1',
                'venues.*' => 'exists:venues,id',
            ]);

            $course = Course::with(['faculties', 'lecturers'])
                ->where('id', $courseId)
                ->where('cross_catering', true)
                ->where('semester_id', $timetableSemester->semester_id)
                ->firstOrFail();

            Log::info('Course details fetched', [
                'course_code' => $course->course_code,
                'is_workshop' => $course->is_workshop,
                'faculties_count' => $course->faculties->count(),
                'lecturers_count' => $course->lecturers->count(),
                'semester_id' => $timetableSemester->semester_id
            ]);

            $venues = Venue::whereIn('id', $request->venues)->select('id', 'name', 'capacity')->get();
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $timetables = [];
            $warnings = [];
            $errors = [];

            Log::info('Venues fetched for scheduling', [
                'venue_ids' => $request->venues,
                'venue_count' => $venues->count(),
            ]);

            $lecturers = $course->lecturers->pluck('id')->toArray();

            if (empty($lecturers)) {
                Log::warning('No lecturers available', ['course_id' => $courseId]);
                return $request->ajax()
                    ? response()->json(['errors' => ['lecturers' => 'No lecturers available for the course.']], 422)
                    : back()->withInput()->withErrors(['lecturers' => 'No lecturers available for the course.']);
            }

            if ($course->is_workshop) {
                $this->generateWorkshopTimetable($course, $venues, $days, $timetables, $warnings, $errors, $timetableSemester->semester_id);
            } else {
                $this->generateNonWorkshopTimetable($request, $course, $venues, $days, $lecturers, $timetables, $warnings, $errors, $timetableSemester->semester_id);
            }

            Log::critical('ABOUT TO CHECK IF TIMETABLES IS EMPTY', [
                'timetables_count' => count($timetables),
                'errors_so_far' => $errors,
                'warnings_so_far' => $warnings,
                'course_code' => $course->course_code,
            ]);

            if (empty($timetables)) {
                $errors[] = 'No sessions could be scheduled at all. Possible reasons: no free slots, venues too small, or all times blocked.';
                Log::error('TOTAL FAILURE - ZERO TIMETABLE ENTRIES', [
                    'course_code' => $course->course_code,
                    'errors' => $errors
                ]);

                return $request->ajax()
                    ? response()->json(['errors' => $errors], 422)
                    : back()->withInput()->withErrors($errors);
            }

            // -----  AUTO-SAVE WHEN ONLY BUFFER-WARNINGS EXIST  -----
            if (!empty($warnings) && !$request->has('force_proceed')) {
                // Allow auto-proceed **only** if every warning is a capacity-buffer warning.
                $onlyBufferWarnings = true;
                foreach ($warnings as $w) {
                    if (!str_contains($w, 'exceeding capacity but within 15-student buffer')) {
                        $onlyBufferWarnings = false;
                        break;
                    }
                }

                if ($onlyBufferWarnings) {
                    // Pretend the user clicked “Proceed” → jump straight to DB save
                    Log::info('Auto-proceeding because only capacity-buffer warnings exist', [
                        'course_code' => $course->course_code,
                        'warnings'   => $warnings,
                    ]);
                    // fall-through to the DB block below
                } else {
                    // Real warnings that need user confirmation → return to UI
                    return $request->ajax()
                        ? response()->json([
                            'success'   => true,
                            'message'   => 'Timetable generated with warnings.',
                            'timetables' => $timetables,
                            'warnings'  => $warnings,
                            'proceed'   => true
                        ], 200)
                        : back()->withInput()->with([
                            'warnings'   => $warnings,
                            'timetables' => $timetables,
                            'proceed'   => true
                        ]);
                }
            }

            Log::info('FINAL TIMETABLE READY TO SAVE', [
                'course_code' => $course->course_code,
                'timetables_generated' => count($timetables),
                'warnings' => $warnings,
                'errors' => $errors,
                'semester_id' => $timetableSemester->semester_id
            ]);

            DB::beginTransaction();
            try {
                Timetable::where('course_code', $course->course_code)
                    ->where('semester_id', $timetableSemester->semester_id)
                    ->delete();
                Log::info('Cleared existing timetable entries', [
                    'course_code' => $course->course_code,
                    'semester_id' => $timetableSemester->semester_id
                ]);

                $createdIds = [];
                foreach ($timetables as $timetable) {
                    $timetable['semester_id'] = $timetableSemester->semester_id;
                    $created = Timetable::create($timetable);
                    $createdIds[] = $created->id;
                }
                DB::commit();
                Log::info('Timetable generated and saved successfully', [
                    'course_id' => $courseId,
                    'timetable_count' => count($timetables),
                    'created_ids' => $createdIds,
                    'semester_id' => $timetableSemester->semester_id
                ]);

                return $request->ajax()
                    ? response()->json(['message' => 'Timetable generated successfully for course ' . $course->course_code, 'ids' => $createdIds])
                    : redirect()->route('timetable.index')->with('success', 'Timetable generated successfully for course ' . $course->course_code);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to save timetable', [
                    'course_id' => $courseId,
                    'error' => $e->getMessage(),
                    'semester_id' => $timetableSemester->semester_id
                ]);
                return $request->ajax()
                    ? response()->json(['errors' => ['database' => 'Failed to save timetable: ' . $e->getMessage()]], 422)
                    : back()->withInput()->withErrors(['database' => 'Failed to save timetable: ' . $e->getMessage()]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for timetable generation', [
                'course_id' => $courseId,
                'errors' => $e->errors(),
            ]);
            return $request->ajax()
                ? response()->json(['errors' => $e->errors()], 422)
                : back()->withInput()->withErrors($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Course not found', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            return $request->ajax()
                ? response()->json(['errors' => ['course' => 'Course not found or not a cross-catering course for the active semester.']], 422)
                : back()->withInput()->withErrors(['course' => 'Course not found or not a cross-catering course for the active semester.']);
        } catch (\Exception $e) {
            Log::error('Unexpected error during timetable generation', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            return $request->ajax()
                ? response()->json(['errors' => ['error' => 'Unexpected error: ' . $e->getMessage()]], 422)
                : back()->withInput()->withErrors(['error' => 'Unexpected error: ' . $e->getMessage()]);
        }
    }

    /**
     * Check if scheduling a session violates break requirements for a faculty or lecturer.
     */
    private function checkBreakRequirement($facultyId, $lecturerId, $day, $startTime, $duration, $timetables, $semesterId)
    {
        // === FACULTY BREAK CHECK REMOVED ===
        // No more session count, total hours, or gap checks for faculty
        // Faculty can now have as many sessions as venues/lecturers allow

        // === LECTURER BREAK CHECK (KEPT & IMPROVED) ===
        $lecturerSessionsOnDay = array_filter(
            $timetables,
            fn($t) =>
            $t['day'] === $day &&
                $t['lecturer_id'] === $lecturerId &&
                $t['semester_id'] === $semesterId
        );

        $lecturerSessionCount = count($lecturerSessionsOnDay);

        if ($lecturerSessionCount >= 2) {
            // Sort sessions by start time
            usort(
                $lecturerSessionsOnDay,
                fn($a, $b) =>
                strtotime($a['time_start']) <=> strtotime($b['time_start'])
            );

            $consecutiveCount = 1; // Start counting from the proposed session
            $lastEndTime = null;

            foreach ($lecturerSessionsOnDay as $session) {
                $sessionStartTime = strtotime("{$session['day']} {$session['time_start']}");
                $sessionEndTime = strtotime("{$session['day']} {$session['time_end']}");

                if ($lastEndTime !== null) {
                    $gap = ($sessionStartTime - $lastEndTime) / 3600;
                    if ($gap < 1) {
                        $consecutiveCount++;
                        if ($consecutiveCount > 7) {
                            Log::warning('Lecturer consecutive session violation detected', [
                                'lecturer_id' => $lecturerId,
                                'day' => $day,
                                'start_time' => $startTime,
                                'consecutive_count' => $consecutiveCount,
                                'semester_id' => $semesterId
                            ]);
                            return false;
                        }
                    } else {
                        $consecutiveCount = 1; // Reset on proper break
                    }
                }
                $lastEndTime = $sessionEndTime;
            }

            // Check proposed session against last existing session
            if ($lastEndTime !== null) {
                $proposedStartTime = strtotime("$day $startTime");
                $gap = ($proposedStartTime - $lastEndTime) / 3600;

                // Fix cross-day negative gap
                if ($gap < 0) {
                    $gap += 24;
                }

                if ($gap < 1) {
                    $consecutiveCount++;
                    if ($consecutiveCount > 7) {
                        Log::warning('Lecturer consecutive session violation (proposed)', [
                            'lecturer_id' => $lecturerId,
                            'day' => $day,
                            'start_time' => $startTime,
                            'consecutive_count' => $consecutiveCount,
                            'gap_hours' => $gap,
                            'semester_id' => $semesterId
                        ]);
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Generate randomized day-time combinations, excluding church times.
     */
    private function getRandomizedDayTimeCombinations($days, $timeSlots, $duration)
    {
        $forbiddenSlots = [
            ['day' => 'Tuesday', 'start_time' => '10:00', 'end_time' => '11:00'],
            ['day' => 'Friday', 'start_time' => '12:00', 'end_time' => '14:00'],
        ];
        $combinations = [];
        foreach ($days as $day) {
            foreach ($timeSlots as $startTime) {
                $endTime = date('H:i', strtotime($startTime) + ($duration * 3600));
                if (strtotime($endTime) <= strtotime('20:00')) {
                    $isForbidden = false;
                    foreach ($forbiddenSlots as $forbidden) {
                        if ($day === $forbidden['day'] && $startTime === $forbidden['start_time']) {
                            $isForbidden = true;
                            break;
                        }
                    }
                    if (!$isForbidden) {
                        $combinations[] = ['day' => $day, 'start_time' => $startTime, 'end_time' => $endTime];
                    }
                }
            }
        }
        shuffle($combinations);
        Log::info('Generated randomized day-time combinations, excluding church times', [
            'total_combinations' => count($combinations),
            'sample' => array_slice($combinations, 0, 5),
            'forbidden_slots' => $forbiddenSlots,
        ]);
        return $combinations;
    }

    private function getPrioritizedDayTimeCombinations($days, $timeSlots, $duration, $isCL111)
    {
        $forbiddenSlots = [
            ['day' => 'Tuesday', 'start_time' => '10:00', 'end_time' => '11:00'],
            ['day' => 'Friday', 'start_time' => '12:00', 'end_time' => '14:00'],
        ];

        $combinations = [];

        $priorityDays = ['Monday', 'Tuesday', 'Wednesday'];
        $fallbackDays = ['Thursday', 'Friday'];

        $dayGroups = $isCL111 ? [$priorityDays, $fallbackDays] : [$days];

        foreach ($dayGroups as $dayList) {
            $daySlots = [];

            foreach ($dayList as $day) {
                foreach ($timeSlots as $startTime) {
                    $endTime = date('H:i', strtotime($startTime) + ($duration * 3600));
                    if (strtotime($endTime) > strtotime('20:00')) {
                        continue;
                    }

                    $isForbidden = false;
                    foreach ($forbiddenSlots as $forbidden) {
                        if ($day === $forbidden['day'] && $startTime === $forbidden['start_time']) {
                            $isForbidden = true;
                            break;
                        }
                    }

                    if (!$isForbidden) {
                        $daySlots[] = [
                            'day' => $day,
                            'start_time' => $startTime,
                            'end_time' => $endTime
                        ];
                    }
                }
            }

            shuffle($daySlots);
            $combinations = array_merge($combinations, $daySlots);

            // Optional: Block fallback entirely (uncomment to force Mon-Wed only)
            // if ($isCL111 && $dayList === $priorityDays) {
            //     break;
            // }
        }

        Log::info('Generated PRIORITIZED day-time combinations', [
            'is_CL111' => $isCL111,
            'total_combinations' => count($combinations),
            'sample' => array_slice($combinations, 0, 5),
            'priority_days' => $priorityDays,
        ]);

        return $combinations;
    }
    /**
     * Generate timetable for workshop courses (is_workshop = true).
     */
    private function generateWorkshopTimetable($course, $venues, $days, &$timetables, &$warnings, &$errors, $semesterId)
    {
        $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];
        $sessionDurations = $this->calculateSessionDurations($course->hours, $course->session);

        Log::info('Starting workshop timetable generation with lecturer-to-group assignment', [
            'course_code' => $course->course_code,
            'hours' => $course->hours,
            'sessions' => $course->session,
            'session_durations' => $sessionDurations,
            'semester_id' => $semesterId
        ]);

        // Fetch all groups for the course's faculties
        $allGroups = [];
        $facultyGroups = [];
        foreach ($course->faculties as $faculty) {
            $groups = FacultyGroup::where('faculty_id', $faculty->id)->get(['id', 'faculty_id', 'group_name', 'student_count']);
            $facultyGroups[$faculty->id] = [];
            foreach ($groups as $group) {
                $group->faculty_name = $faculty->name ?? 'Unknown';
                $allGroups[] = $group;
                $facultyGroups[$faculty->id][] = $group;
            }
        }

        if (empty($allGroups)) {
            $errors[] = 'No groups found for the course.';
            Log::warning('No groups found for workshop course', [
                'course_code' => $course->course_code,
                'faculty_ids' => $course->faculties->pluck('id')->toArray(),
                'semester_id' => $semesterId
            ]);
            return;
        }

        Log::info('Fetched groups for workshop course', [
            'course_code' => $course->course_code,
            'total_groups' => count($allGroups),
            'groups' => array_map(fn($g) => ['group_name' => $g->group_name, 'student_count' => $g->student_count, 'faculty_id' => $g->faculty_id], $allGroups),
        ]);

        // Assign lecturers to groups
        $numGroups = count($allGroups);
        $lecturers = $course->lecturers->pluck('id')->toArray();
        $numLecturers = count($lecturers);
        if ($numLecturers == 0) {
            $errors[] = 'No lecturers available for the course.';
            Log::warning('No lecturers available for workshop course', [
                'course_code' => $course->course_code,
                'semester_id' => $semesterId
            ]);
            return;
        }

        // Distribute groups to lecturers evenly
        $groupLecturerAssignments = [];
        shuffle($lecturers); // Randomize lecturer order
        foreach ($allGroups as $index => $group) {
            $lecturerId = $lecturers[$index % $numLecturers];
            $groupLecturerAssignments[$group->id] = $lecturerId;
            Log::info('Assigned lecturer to group', [
                'course_code' => $course->course_code,
                'group_id' => $group->id,
                'group_name' => $group->group_name,
                'lecturer_id' => $lecturerId,
                'semester_id' => $semesterId
            ]);
        }

        // Track scheduled groups to ensure all are covered
        $scheduledGroups = [];

        // Track groups per faculty per time slot per session
        $facultyTimeSlotGroups = [];

        // Schedule each session
        foreach (range(0, $course->session - 1) as $sessionIndex) {
            $duration = $sessionDurations[$sessionIndex % count($sessionDurations)];
            Log::info('Scheduling session for workshop course', [
                'course_code' => $course->course_code,
                'session_index' => $sessionIndex + 1,
                'duration' => $duration,
            ]);

            // Initialize time slot tracking for this session
            $facultyTimeSlotGroups[$sessionIndex] = [];

            // Group faculties and schedule up to two groups per round for each faculty
            foreach ($course->faculties as $faculty) {
                $facultyId = $faculty->id;
                $groups = $facultyGroups[$facultyId] ?? [];
                if (empty($groups)) {
                    Log::info('No groups for faculty, skipping', [
                        'course_code' => $course->course_code,
                        'faculty_id' => $facultyId,
                        'semester_id' => $semesterId
                    ]);
                    continue;
                }

                // Shuffle groups for randomization within the faculty
                shuffle($groups);
                $roundsPerSession = ceil(count($groups) / 2); // Up to 2 groups per round

                Log::info('Calculated rounds for faculty', [
                    'course_code' => $course->course_code,
                    'faculty_id' => $facultyId,
                    'total_groups' => count($groups),
                    'rounds_per_session' => $roundsPerSession,
                ]);

                // Schedule rounds for this faculty
                for ($round = 0; $round < $roundsPerSession; $round++) {
                    // Select up to 2 groups for this round
                    $roundGroups = array_slice($groups, $round * 2, 2);
                    if (empty($roundGroups)) {
                        Log::info('No groups left to schedule in round', [
                            'course_code' => $course->course_code,
                            'faculty_id' => $facultyId,
                            'session_index' => $sessionIndex + 1,
                            'round' => $round + 1,
                        ]);
                        break;
                    }

                    // Prepare sessions for the round
                    $roundSessions = [];
                    foreach ($roundGroups as $group) {
                        $lecturerId = $groupLecturerAssignments[$group->id] ?? null;
                        if (!$lecturerId) {
                            $errors[] = "No lecturer assigned to group {$group->group_name}.";
                            Log::warning('No lecturer assigned to group', [
                                'course_code' => $course->course_code,
                                'group_id' => $group->id,
                                'group_name' => $group->group_name,
                                'session_index' => $sessionIndex + 1,
                                'round' => $round + 1,
                                'semester_id' => $semesterId
                            ]);
                            return;
                        }
                        $roundSessions[] = [
                            'lecturer_id' => $lecturerId,
                            'student_count' => $group->student_count,
                            'group_selection' => $group->group_name,
                            'faculty_id' => $group->faculty_id,
                            'group' => $group,
                        ];
                    }

                    Log::info('Prepared round sessions for faculty', [
                        'course_code' => $course->course_code,
                        'faculty_id' => $facultyId,
                        'session_index' => $sessionIndex + 1,
                        'round' => $round + 1,
                        'sessions' => array_map(fn($s) => [
                            'lecturer_id' => $s['lecturer_id'],
                            'group' => $s['group_selection'],
                            'student_count' => $s['student_count'],
                        ], $roundSessions),
                    ]);

                    // Try to schedule the round with retries
                    $maxRetries = 3;
                    $retryCount = 0;
                    $scheduled = false;

                    while ($retryCount < $maxRetries && !$scheduled) {
                        $dayTimeCombinations = $this->getRandomizedDayTimeCombinations($days, $timeSlots, $duration);
                        foreach ($dayTimeCombinations as $index => $combo) {
                            $day = $combo['day'];
                            $startTime = $combo['start_time'];
                            $endTime = $combo['end_time'];

                            // Check if adding this round exceeds 2 groups per faculty in this time slot
                            $timeSlotKey = "$day-$startTime";
                            $facultyTimeSlotGroups[$sessionIndex][$facultyId] = $facultyTimeSlotGroups[$sessionIndex][$facultyId] ?? [];
                            $currentGroupsInSlot = $facultyTimeSlotGroups[$sessionIndex][$facultyId][$timeSlotKey] ?? [];
                            if (count($currentGroupsInSlot) + count($roundGroups) > 2) {
                                Log::warning('Too many groups for faculty in time slot', [
                                    'course_code' => $course->course_code,
                                    'faculty_id' => $facultyId,
                                    'session_index' => $sessionIndex + 1,
                                    'round' => $round + 1,
                                    'day' => $day,
                                    'start_time' => $startTime,
                                    'current_groups' => $currentGroupsInSlot,
                                    'new_groups' => array_map(fn($g) => $g->group_name, $roundGroups),
                                    'semester_id' => $semesterId
                                ]);
                                continue;
                            }

                            $availableVenues = $venues->toArray();
                            shuffle($availableVenues); // Randomize venue selection
                            $assignment = [];
                            $conflicts = false;

                            // Check faculty and venue conflicts
                            foreach ($roundSessions as $session) {
                                $facultyConflict = Timetable::where('day', $day)
                                    ->where('time_start', $startTime)
                                    ->where('faculty_id', $session['faculty_id'])
                                    ->where('semester_id', $semesterId)
                                    ->exists();
                                if ($facultyConflict) {
                                    Log::warning('Faculty conflict detected', [
                                        'course_code' => $course->course_code,
                                        'faculty_id' => $session['faculty_id'],
                                        'day' => $day,
                                        'time_start' => $startTime,
                                        'semester_id' => $semesterId
                                    ]);
                                    $conflicts = true;
                                    break;
                                }
                            }
                            if ($conflicts) {
                                continue;
                            }

                            // Assign venues and check lecturer conflicts
                            foreach ($roundSessions as $session) {
                                $assignedVenue = null;
                                $lecturerId = $session['lecturer_id'];

                                // Find a suitable venue
                                for ($v = 0; $v < count($availableVenues); $v++) {
                                    $venue = $availableVenues[$v];
                                    if ($venue['capacity'] + 15 >= $session['student_count']) {
                                        $venueConflict = Timetable::where('day', $day)
                                            ->where('time_start', $startTime)
                                            ->where('venue_id', $venue['id'])
                                            ->where('semester_id', $semesterId)
                                            ->exists();
                                        $lecturerConflict = Timetable::where('day', $day)
                                            ->where('time_start', $startTime)
                                            ->where('lecturer_id', $lecturerId)
                                            ->where('semester_id', $semesterId)
                                            ->exists();

                                        if (!$venueConflict && !$lecturerConflict) {
                                            $assignedVenue = $venue;
                                            array_splice($availableVenues, $v, 1);
                                            break;
                                        } else {
                                            Log::warning('Venue or lecturer conflict for venue', [
                                                'course_code' => $course->course_code,
                                                'venue_id' => $venue['id'],
                                                'day' => $day,
                                                'time_start' => $startTime,
                                                'venue_conflict' => $venueConflict,
                                                'lecturer_conflict' => $lecturerConflict,
                                                'semester_id' => $semesterId
                                            ]);
                                        }
                                    }
                                }
                                if ($assignedVenue === null) {
                                    $conflicts = true;
                                    Log::warning('Venue assignment failed for session', [
                                        'course_code' => $course->course_code,
                                        'faculty_id' => $facultyId,
                                        'session_index' => $sessionIndex + 1,
                                        'round' => $round + 1,
                                        'lecturer_id' => $session['lecturer_id'],
                                        'group' => $session['group_selection'],
                                        'student_count' => $session['student_count'],
                                        'semester_id' => $semesterId
                                    ]);
                                    break;
                                }
                                $assignment[] = ['session' => $session, 'venue' => $assignedVenue];
                            }

                            if (!$conflicts) {
                                // Schedule the assignments
                                foreach ($assignment as $assign) {
                                    $session = $assign['session'];
                                    $venue = $assign['venue'];
                                    $timetableData = [
                                        'day' => $day,
                                        'time_start' => $startTime,
                                        'time_end' => $endTime,
                                        'course_code' => $course->course_code,
                                        'course_name' => $course->name,
                                        'activity' => 'Workshop',
                                        'venue_id' => $venue['id'],
                                        'lecturer_id' => $session['lecturer_id'],
                                        'group_selection' => $session['group_selection'],
                                        'faculty_id' => $session['faculty_id'],
                                        'semester_id' => $semesterId
                                    ];
                                    $timetables[] = $timetableData;
                                    $scheduledGroups[$session['group_selection']] = ($scheduledGroups[$session['group_selection']] ?? 0) + 1;

                                    // Track groups in this time slot for the faculty
                                    $facultyTimeSlotGroups[$sessionIndex][$facultyId][$timeSlotKey][] = $session['group_selection'];

                                    Log::info('Scheduled timetable entry', [
                                        'course_code' => $course->course_code,
                                        'faculty_id' => $facultyId,
                                        'session_index' => $sessionIndex + 1,
                                        'round' => $round + 1,
                                        'timetable' => $timetableData,
                                    ]);
                                }
                                $scheduled = true;
                                // Remove used combination
                                unset($dayTimeCombinations[$index]);
                                $dayTimeCombinations = array_values($dayTimeCombinations);
                                break;
                            }
                        }
                        $retryCount++;
                    }

                    if (!$scheduled) {
                        $errors[] = "Could not schedule workshop round " . ($round + 1) . " for session " . ($sessionIndex + 1) . " for faculty {$faculty->name} after $maxRetries retries.";
                        Log::warning('Failed to schedule round after retries', [
                            'course_code' => $course->course_code,
                            'faculty_id' => $facultyId,
                            'session_index' => $sessionIndex + 1,
                            'round' => $round + 1,
                            'retry_count' => $retryCount,
                            'semester_id' => $semesterId
                        ]);
                        return;
                    }
                }
            }
        }

        // Verify all groups have been scheduled for all sessions
        foreach ($allGroups as $group) {
            $groupName = $group->group_name;
            if (($scheduledGroups[$groupName] ?? 0) < $course->session) {
                $errors[] = "Group {$groupName} was not scheduled for all required sessions.";
                Log::error('Incomplete scheduling for group', [
                    'course_code' => $course->course_code,
                    'group_name' => $groupName,
                    'scheduled_sessions' => $scheduledGroups[$groupName] ?? 0,
                    'required_sessions' => $course->session,
                    'semester_id' => $semesterId
                ]);
                $timetables = []; // Clear timetables to prevent partial save
                return;
            }
        }
    }

    /**
     * Generate timetable for non-workshop courses (is_workshop = false).
     */
    private function generateNonWorkshopTimetable($request, $course, $venues, $days, $lecturers, &$timetables, &$warnings, &$errors, $semesterId)
    {
        // === 1. Determine if this is a CL111 course (morning + priority days) ===
        $isCL111 = str_starts_with($course->course_code, 'CL111');

        // === 2. Time slots: restrict to morning for CL111 ===
        $timeSlots = $isCL111
            ? ['08:00', '10:00']
            : ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

        // === 3. Days: prioritize Mon-Tue-Wed for CL111 ===
        $allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        if ($isCL111) {
            $priorityDays = ['Monday', 'Tuesday', 'Wednesday'];
            $otherDays = ['Thursday', 'Friday'];
            $days = array_merge($priorityDays, $otherDays);
        } else {
            $days = $allDays;
        }

        $sessionDurations = $this->calculateSessionDurations($course->hours, $course->session);
        Log::info('Starting non-workshop timetable generation', [
            'course_code' => $course->course_code,
            'hours' => $course->hours,
            'sessions' => $course->session,
            'session_durations' => $sessionDurations,
            'is_CL111' => $isCL111,
            'time_slots' => $timeSlots,
            'days_order' => $days,
            'semester_id' => $semesterId
        ]);

        $facultiesData = $course->faculties->map(function ($faculty) {
            return [
                'id' => $faculty->id,
                'name' => $faculty->name ?? 'Unknown',
                'student_count' => $this->calculateStudentCount($faculty, 'All Groups')
            ];
        })->sortByDesc('student_count')->values();

        if ($facultiesData->isEmpty()) {
            $errors[] = 'No faculties found for the course.';
            Log::warning('No faculties found for non-workshop course', [
                'course_code' => $course->course_code,
                'semester_id' => $semesterId
            ]);
            return;
        }

        Log::info('Fetched faculties for non-workshop course', [
            'course_code' => $course->course_code,
            'total_faculties' => $facultiesData->count(),
            'faculties' => $facultiesData->toArray(),
        ]);

        $totalStudentCount = $facultiesData->sum('student_count');
        $maxVenueCapacity = $venues->max('capacity') + 15;
        $facultyGroups = $this->groupFaculties($facultiesData, $venues, $totalStudentCount, $maxVenueCapacity);

        if (empty($facultyGroups)) {
            $errors[] = 'Unable to group faculties to fit available venue capacities.';
            Log::warning('Failed to group faculties', [
                'course_code' => $course->course_code,
                'total_student_count' => $totalStudentCount,
                'max_venue_capacity' => $maxVenueCapacity,
                'semester_id' => $semesterId
            ]);
            return;
        }

        Log::info('Grouped faculties for scheduling', [
            'course_code' => $course->course_code,
            'group_count' => count($facultyGroups),
            'groups' => array_map(fn($g) => [
                'student_count' => $g['student_count'],
                'faculties' => array_column($g['faculties'], 'name'),
            ], $facultyGroups),
        ]);

        shuffle($facultyGroups);

        foreach ($facultyGroups as $groupIndex => $group) {
            $groupStudentCount = $group['student_count'];
            $suitableVenues = $venues->where('capacity', '>=', $groupStudentCount - 15)->sortBy('capacity')->values();

            if ($suitableVenues->isEmpty()) {
                $errors[] = "No suitable venue found for group with {$groupStudentCount} students.";
                Log::warning('No suitable venue for faculty group', [
                    'course_code' => $course->course_code,
                    'group_index' => $groupIndex + 1,
                    'student_count' => $groupStudentCount,
                    'semester_id' => $semesterId
                ]);
                continue;
            }

            foreach (range(0, $course->session - 1) as $sessionIndex) {
                $duration = $sessionDurations[$sessionIndex % count($sessionDurations)];
                $dayTimeCombinations = $this->getPrioritizedDayTimeCombinations($days, $timeSlots, $duration, $isCL111);
                $lecturerId = $lecturers[$sessionIndex % count($lecturers)];
                $scheduled = false;

                foreach ($dayTimeCombinations as $index => $combo) {
                    $day = $combo['day'];
                    $startTime = $combo['start_time'];
                    $endTime = $combo['end_time'];

                    $facultyConflict = false;
                    foreach ($group['faculties'] as $faculty) {
                        $existing = Timetable::where('day', $day)
                            ->where('time_start', $startTime)
                            ->where('faculty_id', $faculty['id'])
                            ->where('semester_id', $semesterId)
                            ->exists();

                        if ($existing) {
                            Log::warning('Faculty conflict detected', [
                                'course_code' => $course->course_code,
                                'faculty_id' => $faculty['id'],
                                'day' => $day,
                                'time_start' => $startTime,
                                'semester_id' => $semesterId
                            ]);
                            $facultyConflict = true;
                            break;
                        }

                        if (!$this->checkBreakRequirement($faculty['id'], $lecturerId, $day, $startTime, $duration, $timetables, $semesterId)) {
                            $facultyConflict = true;
                            break;
                        }
                    }

                    if ($facultyConflict) {
                        continue;
                    }

                    foreach ($suitableVenues as $venue) {
                        $venueConflict = Timetable::where('day', $day)
                            ->where('time_start', $startTime)
                            ->where('venue_id', $venue->id)
                            ->where('semester_id', $semesterId)
                            ->exists();

                        $lecturerConflict = Timetable::where('day', $day)
                            ->where('time_start', $startTime)
                            ->where('lecturer_id', $lecturerId)
                            ->where('semester_id', $semesterId)
                            ->exists();

                        if ($venueConflict || $lecturerConflict) {
                            Log::warning('Conflict detected, trying next time slot', [
                                'course_code' => $course->course_code,
                                'group_index' => $groupIndex + 1,
                                'venue_id' => $venue->id,
                                'venue_name' => $venue->name,
                                'day' => $day,
                                'time_start' => $startTime,
                                'venue_conflict' => $venueConflict,
                                'lecturer_conflict' => $lecturerConflict,
                                'semester_id' => $semesterId
                            ]);
                            continue;
                        }

                        if ($venue->capacity < $groupStudentCount && !$request->has('force_proceed')) {
                            $warnings[] = "Venue {$venue->name} (capacity: {$venue->capacity}) is assigned {$groupStudentCount} students for {$course->course_code}, exceeding capacity but within 15-student buffer.";
                        }

                        foreach ($group['faculties'] as $faculty) {
                            $timetableData = [
                                'day' => $day,
                                'time_start' => $startTime,
                                'time_end' => $endTime,
                                'course_code' => $course->course_code,
                                'course_name' => $course->name,
                                'activity' => 'Lecture',
                                'venue_id' => $venue->id,
                                'lecturer_id' => $lecturerId,
                                'group_selection' => 'All Groups',
                                'faculty_id' => $faculty['id'],
                                'semester_id' => $semesterId
                            ];
                            $timetables[] = $timetableData;
                            // Log::info('Scheduled non-workshop timetable entry' [
                            //     'course_code' => $course->course_code,
                            //     'group_index' => $groupIndex + 1,
                            //     'faculty_id' => $faculty['id'],
                            //     'day' => $day,
                            //     'time_start' => $startTime,
                            //     'venue_id' => $venue->id,
                            //     'lecturer_id' => $lecturerId,
                            //     'group_selection' => 'All Groups',
                            //     'semester_id' => $semesterId
                            // ]);
                        }

                        $scheduled = true;
                        unset($dayTimeCombinations[$index]);
                        $dayTimeCombinations = array_values($dayTimeCombinations);
                        break;
                    }

                    if ($scheduled) {
                        break;
                    }
                }

                if (!$scheduled) {
                    $errors[] = "Could not schedule session " . ($sessionIndex + 1) . " for group " . ($groupIndex + 1) . " due to venue or lecturer conflicts.";
                    Log::warning('Failed to schedule non-workshop session', [
                        'course_code' => $course->course_code,
                        'group_index' => $groupIndex + 1,
                        'session_index' => $sessionIndex + 1,
                        'semester_id' => $semesterId
                    ]);
                    continue;
                }
            }
        }
    }

    /**
     * Group faculties to fit venue capacities using First Fit Decreasing.
     */
    private function groupFaculties($facultiesData, $venues, $totalStudentCount, $maxVenueCapacity)
    {
        $groups = [];
        $remainingFaculties = $facultiesData->toArray();
        usort($remainingFaculties, fn($a, $b) => $b['student_count'] <=> $a['student_count']);

        while (!empty($remainingFaculties)) {
            $currentGroup = ['faculties' => [], 'student_count' => 0];
            $unassigned = [];

            foreach ($remainingFaculties as $faculty) {
                if ($currentGroup['student_count'] + $faculty['student_count'] <= $maxVenueCapacity) {
                    $currentGroup['faculties'][] = $faculty;
                    $currentGroup['student_count'] += $faculty['student_count'];
                } else {
                    $unassigned[] = $faculty;
                }
            }

            if (!empty($currentGroup['faculties'])) {
                $groups[] = $currentGroup;
            }
            $remainingFaculties = $unassigned;

            if (empty($currentGroup['faculties']) && !empty($unassigned)) {
                Log::warning('Failed to group some faculties due to capacity constraints', [
                    'unassigned_faculties' => array_column($unassigned, 'id'),
                    'total_student_count' => array_sum(array_column($unassigned, 'student_count')),
                ]);
                break;
            }
        }

        foreach ($groups as $groupIndex => $group) {
            $suitableVenue = $venues->firstWhere('capacity', '>=', $group['student_count'] - 15);
            if (!$suitableVenue) {
                $errors[] = "No venue large enough for group " . ($groupIndex + 1) . " ({$group['student_count']} students). Need capacity ≥ " . ($group['student_count'] - 15);
                Log::critical('NO VENUE BIG ENOUGH - SCHEDULING IMPOSSIBLE', [
                    'group_index' => $groupIndex + 1,
                    'student_count' => $group['student_count'],
                    'required_capacity' => $group['student_count'] - 15,
                    'available_venues' => $venues->pluck('name', 'capacity')->toArray(),

                ]);
                // Do NOT return [] — let it continue so we see the error
            }
        }

        return $groups;
    }

    /**
     * Calculate student count for a faculty or specific groups.
     */
    private function calculateStudentCount(Faculty $faculty, string $groupSelection): int
    {
        if ($groupSelection === 'All Groups') {
            $count = $faculty->total_students_no;
            Log::info('Calculated student count for faculty (All Groups)', [
                'faculty_id' => $faculty->id,
                'student_count' => $count,
            ]);
            return $count;
        }

        $groups = explode(',', $groupSelection);
        $studentCount = FacultyGroup::where('faculty_id', $faculty->id)
            ->whereIn('group_name', array_map('trim', $groups))
            ->sum('student_count');

        Log::info('Calculated student count for specific groups', [
            'faculty_id' => $faculty->id,
            'groups' => $groups,
            'student_count' => $studentCount,
        ]);

        return $studentCount;
    }

    /**
     * Calculate session durations.
     */
    private function calculateSessionDurations($totalHours, $totalSessions)
    {
        if ($totalHours < $totalSessions || $totalSessions <= 0) {
            $error = "Invalid combination of total hours ($totalHours) and sessions ($totalSessions).";
            Log::error('Invalid session duration parameters', [
                'total_hours' => $totalHours,
                'total_sessions' => $totalSessions,
            ]);
            throw new \Exception($error);
        }

        $baseHours = floor($totalHours / $totalSessions);
        $remainder = $totalHours % $totalSessions;
        $durations = array_fill(0, $totalSessions, $baseHours);

        for ($i = 0; $i < $remainder && $durations[$i] < 2; $i++) {
            $durations[$i]++;
        }

        $sum = array_sum($durations);
        if ($sum < $totalHours) {
            $error = "Cannot distribute $totalHours hours across $totalSessions sessions with max 2 hours per session.";
            Log::error('Failed to distribute session durations', [
                'total_hours' => $totalHours,
                'total_sessions' => $totalSessions,
                'durations' => $durations,
            ]);
            throw new \Exception($error);
        }

        Log::info('Calculated session durations', [
            'total_hours' => $totalHours,
            'total_sessions' => $totalSessions,
            'durations' => $durations,
        ]);

        return $durations;
    }

    /**
     * Get student count for a faculty or groups.
     */
    public function getStudentCount(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'groups' => 'required|array'
        ]);

        $faculty = Faculty::findOrFail($request->faculty_id);
        $groups = $request->groups;

        if (in_array('All Groups', $groups)) {
            $count = $faculty->total_students_no;
            Log::info('Fetched student count for All Groups', [
                'faculty_id' => $faculty->id,
                'student_count' => $count,
            ]);
            return response()->json(['student_count' => $count]);
        }

        $studentCount = FacultyGroup::where('faculty_id', $faculty->id)
            ->whereIn('group_name', $groups)
            ->sum('student_count');

        Log::info('Fetched student count for specific groups', [
            'faculty_id' => $faculty->id,
            'groups' => $groups,
            'student_count' => $studentCount,
        ]);

        return response()->json(['student_count' => $studentCount]);
    }

    /**
     * Get courses for a faculty.
     */
    public function getFacultyCourses(Request $request)
    {
        if (!TimetableSemester::exists()) {
            return response()->json(['errors' => ['semester' => 'No timetable semester configured.']], 422);
        }

        $timetableSemester = TimetableSemester::getFirstSemester();
        $facultyId = $request->query('faculty_id');
        $courses = Course::whereHas('faculties', fn($q) => $q->where('faculties.id', $facultyId))
            ->where('semester_id', $timetableSemester->semester_id)
            ->select('course_code', 'name')
            ->get()
            ->map(function ($course) {
                return ['course_code' => $course->course_code, 'name' => $course->name];
            })
            ->toArray();

        Log::info('Fetched courses for faculty', [
            'faculty_id' => $facultyId,
            'course_count' => count($courses),
            'courses' => $courses,
            'semester_id' => $timetableSemester->semester_id
        ]);

        return response()->json(['course_codes' => $courses]);
    }

    /**
     * Get groups for a faculty.
     */
    public function getFacultyGroups(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $groups = FacultyGroup::where('faculty_id', $facultyId)
            ->select('group_name', 'student_count')
            ->get()
            ->toArray();

        Log::info('Fetched groups for faculty', [
            'faculty_id' => $facultyId,
            'group_count' => count($groups),
            'groups' => $groups,
        ]);

        return response()->json(['groups' => $groups]);
    }
}

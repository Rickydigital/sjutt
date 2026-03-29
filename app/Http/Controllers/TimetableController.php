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
        '08:00', '09:00', '10:00', '11:00', '12:00', '13:00',
        '14:00', '15:00', '16:00', '17:00', '18:00', '19:00',
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

        $courses = Course::whereHas('faculties', fn ($q) => $q->where('faculties.id', $request->faculty_id))
            ->where('semester_id', $setup->semester_id)
            ->select('course_code', 'name', 'practical_hrs', 'cross_catering', 'hours', 'session')
            ->orderBy('course_code')
            ->get()
            ->map(fn ($course) => [
                'course_code' => $course->course_code,
                'name' => $course->name,
                'practical_hrs' => $course->practical_hrs,
                'cross_catering' => (bool) $course->cross_catering,
                'hours' => $course->hours,
                'session' => $course->session,
            ])
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

        $targetCourses = Course::whereHas('faculties', fn ($q) => $q->where('faculties.id', $request->faculty_id))
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

        $currentCourses = Course::whereHas('faculties', fn ($q) => $q->where('faculties.id', $request->faculty_id))
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

            if ($currentSetup && (int) $currentSetup->semester_id !== (int) $setup->semester_id && empty($validated['generation_mode'])) {
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
                return response()->json(['errors' => ['venues' => 'No venues selected.']], 422);
            }

            $sessions = $this->buildSessionsForGeneration(
                validated: $validated,
                setup: $setup
            );

            Log::info('Generated sessions', $sessions);

            if (empty($sessions)) {
                return response()->json([
                    'success' => true,
                    'message' => 'All requested sessions are already scheduled for the selected setup.',
                    'timetables' => [],
                ]);
            }

            $result = $this->scheduleGeneratedSessions(
                sessions: $sessions,
                days: $this->defaultDays,
                timeSlots: $this->defaultTimeSlots,
                venues: $venues,
                setupId: (int) $setup->id
            );

            if (empty($result['timetables'])) {
                return response()->json([
                    'errors' => [
                        'scheduling' => !empty($result['errors'])
                            ? implode(' ', array_unique($result['errors']))
                            : 'Unable to generate a conflict-free timetable.',
                    ],
                ], 422);
            }

            if (!empty($result['warnings']) && !$request->boolean('force_proceed')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Timetable generated with warnings.',
                    'timetables' => $result['timetables'],
                    'warnings' => array_values(array_unique($result['warnings'])),
                    'proceed' => true,
                ]);
            }

            DB::beginTransaction();

            $created = [];
            foreach ($result['timetables'] as $row) {
                $row['semester_id'] = (int) $setup->id;
                $created[] = Timetable::create($row);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Timetable generated successfully for selected setup.',
                'timetables' => $created,
                'setup' => $setup->only(['id', 'semester_id', 'academic_year']),
                'warnings' => array_values(array_unique($result['warnings'])),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unexpected error in generateTimetable', ['error' => $e->getMessage()]);
            return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
        }
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
    public function store(Request $request)
    {
        try {
            $validated = $this->validateManualTimetableRequest($request);
            $setup = $this->resolveRequestedSetup($request->input('setup_id')) ?? $this->requireActiveSemesterSetup();
            $payload = $this->normalizeManualPayload($validated, (int) $setup->id);
            $isCross = $this->isCrossCateringCourse($payload['course_code']);

            DB::beginTransaction();

            if ($isCross) {
                $faculties = $this->getFacultiesForCourse($payload['course_code']);

                $this->assertVenueAvailability(
                    setupId: (int) $setup->id,
                    day: $payload['day'],
                    startTime: $payload['time_start'],
                    endTime: $payload['time_end'],
                    requestedVenueIds: $this->extractVenueIds($payload['venue_id']),
                    excludeIds: []
                );

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
                    'message' => 'Cross-catering timetable created successfully for selected setup.',
                    'ids' => $createdIds,
                    'setup_id' => $setup->id,
                ]);
            }

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
                'message' => 'Timetable entry created successfully.',
                'id' => $created->id,
                'setup_id' => $setup->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
        }
    }

    public function update(Request $request, Timetable $timetable)
    {
        try {
            $validated = $this->validateManualTimetableRequest($request);
            $setup = $this->resolveRequestedSetup($request->input('setup_id')) ?? $this->requireActiveSemesterSetup();
            $payload = $this->normalizeManualPayload($validated, (int) $setup->id);

            $oldIsCross = $this->isCrossCateringCourse($timetable->course_code);
            $newIsCross = $this->isCrossCateringCourse($payload['course_code']);

            DB::beginTransaction();

            if ($oldIsCross || $newIsCross) {
                if ($timetable->course_code !== $payload['course_code']) {
                    throw new \Exception('Changing course code on a cross-catering session is not allowed. Delete and recreate it instead.');
                }

                $relatedRows = $this->getCrossSessionRows($timetable);
                $excludeIds = $relatedRows->pluck('id')->map(fn ($id) => (int) $id)->all();

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
                    'message' => 'Cross-catering timetable updated for all related faculties.',
                    'ids' => $createdIds,
                ]);
            }

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
                'message' => 'Timetable updated successfully.',
                'id' => $timetable->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
            ->map(fn ($venue) => [
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

        $faculties = Faculty::orderBy('name')
            ->with([
                'timetables' => fn ($q) => $q
                    ->where('semester_id', $setup->id)
                    ->orderBy('day')
                    ->orderBy('time_start'),
            ])
            ->get();

        $days = $this->defaultDays;
        $timeSlots = $this->defaultTimeSlots;

        $safeAcademicYear = str_replace(['/', '\\'], '-', $setup->academic_year);
        $safeSemesterName = str_replace(['/', '\\'], '-', $setup->semester->name);
        $safeDraft = str_replace([' ', '/'], '-', $draft);
        $filename = "timetable_{$safeAcademicYear}_{$safeSemesterName}_{$safeDraft}.pdf";

        $timetableSemester = $setup;

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
            'venues' => $venues->map(fn ($v) => [
                'id' => $v->id,
                'text' => "{$v->name} (Capacity: {$v->capacity})",
            ])->values(),
        ]);
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
        $allVenues = Venue::pluck('id')->map(fn ($id) => (int) $id)->all();

        $rows = Timetable::where('semester_id', $setupId)
            ->where('day', $day)
            ->when(!empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
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

        return array_values(array_filter($allVenues, fn ($id) => !isset($busy[$id])));
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
            ->when(!empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
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
            ->when(!empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
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
                ->where('group_selection', 'like', "%{$group}%")
                ->when(!empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
                ->first();

            if ($groupConflict) {
                $conflicts[] = "Group {$group} is already assigned to {$groupConflict->course_code} on {$groupConflict->day} {$groupConflict->time_start}-{$groupConflict->time_end}.";
            }
        }

        return $conflicts;
    }

    private function buildSessionsForGeneration(array $validated, TimetableSemester $setup): array
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

                $existingCount = Timetable::where('semester_id', $setup->id)
                    ->where('course_code', $courseCode)
                    ->select('day', 'time_start', 'time_end')
                    ->distinct()
                    ->count();

                $requiredSessions = max(0, (int) $course->session - $existingCount);
                if ($requiredSessions <= 0) {
                    continue;
                }

                $faculties = $this->getFacultiesForCourse($courseCode);
                if ($faculties->isEmpty()) {
                    continue;
                }

                $sessions[] = [
                    'course_code' => $course->course_code,
                    'activity' => $activity,
                    'lecturer_id' => $lecturerId,
                    'cross_catering' => true,
                    'faculty_rows' => $faculties->map(fn ($f) => [
                        'faculty_id' => (int) $f->id,
                        'group_selection' => 'All Groups',
                        'student_count' => (int) ($f->total_students_no ?? 0),
                    ])->values()->all(),
                    'sessions_per_week' => $requiredSessions,
                    'hours_per_session' => $this->calculateSessionDurations((int) $course->hours, (int) $requiredSessions),
                ];

                continue;
            }

            $faculty = Faculty::findOrFail((int) $validated['faculty_id']);

            $existingCount = Timetable::where('semester_id', $setup->id)
                ->where('course_code', $courseCode)
                ->where('faculty_id', $faculty->id)
                ->count();

            $requiredSessions = max(0, (int) $course->session - $existingCount);
            if ($requiredSessions <= 0) {
                continue;
            }

            $sessions[] = [
                'course_code' => $course->course_code,
                'activity' => $activity,
                'lecturer_id' => $lecturerId,
                'cross_catering' => false,
                'faculty_rows' => [[
                    'faculty_id' => (int) $faculty->id,
                    'group_selection' => $groupSelection,
                    'student_count' => $this->calculateStudentCount($faculty, $groupSelection),
                ]],
                'sessions_per_week' => $requiredSessions,
                'hours_per_session' => $this->calculateSessionDurations((int) $course->hours, (int) $requiredSessions),
            ];
        }

        return $sessions;
    }

    private function scheduleGeneratedSessions(
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

        shuffle($sessions);

        foreach ($sessions as $session) {
            $durations = $session['hours_per_session'];
            $sessionsNeeded = (int) $session['sessions_per_week'];
            $scheduledCount = 0;

            for ($i = 0; $i < $sessionsNeeded; $i++) {
                $duration = (int) ($durations[$i] ?? end($durations));
                $candidates = $this->getCandidateSlots($days, $timeSlots, $duration);
                $scheduled = false;

                foreach ($candidates as $candidate) {
                    $day = $candidate['day'];
                    $startTime = $candidate['start_time'];
                    $endTime = $candidate['end_time'];

                    if (isset($reservedLecturerSlots[$session['lecturer_id']][$day][$startTime])) {
                        continue;
                    }

                    $facultyBlocked = false;
                    foreach ($session['faculty_rows'] as $row) {
                        $fid = (int) $row['faculty_id'];

                        if (isset($reservedFacultySlots[$fid][$day][$startTime])) {
                            $facultyBlocked = true;
                            break;
                        }

                        $dbFacultyConflict = Timetable::where('semester_id', $setupId)
                            ->where('day', $day)
                            ->where('faculty_id', $fid)
                            ->where(function ($q) use ($startTime, $endTime) {
                                $q->where('time_start', '<', $this->normalizeTime($endTime))
                                    ->where('time_end', '>', $this->normalizeTime($startTime));
                            })
                            ->exists();

                        if ($dbFacultyConflict) {
                            $facultyBlocked = true;
                            break;
                        }
                    }

                    if ($facultyBlocked) {
                        continue;
                    }

                    $totalStudents = array_sum(array_map(fn ($row) => (int) $row['student_count'], $session['faculty_rows']));

                    $pickedVenues = $this->pickVenuesForStudents(
                        requiredStudents: $totalStudents,
                        venues: $venues,
                        day: $day,
                        startTime: $startTime,
                        reservedVenueSlots: $reservedVenueSlots,
                        setupId: $setupId,
                        endTime: $endTime
                    );

                    if (empty($pickedVenues)) {
                        continue;
                    }

                    $dbLecturerConflict = Timetable::where('semester_id', $setupId)
                        ->where('day', $day)
                        ->where('lecturer_id', $session['lecturer_id'])
                        ->where(function ($q) use ($startTime, $endTime) {
                            $q->where('time_start', '<', $this->normalizeTime($endTime))
                                ->where('time_end', '>', $this->normalizeTime($startTime));
                        })
                        ->exists();

                    if ($dbLecturerConflict) {
                        continue;
                    }

                    $venueString = implode(',', $pickedVenues['venue_ids']);
                    foreach ($pickedVenues['warnings'] as $warning) {
                        $warnings[] = $warning;
                    }

                    foreach ($session['faculty_rows'] as $row) {
                       $timetables[] = [
                            'day' => $day,
                            'time_start' => $this->normalizeTime($startTime),
                            'time_end' => $this->normalizeTime($endTime),
                            'course_code' => $session['course_code'],
                            'activity' => $session['activity'],
                            'venue_id' => $venueString,
                            'lecturer_id' => $session['lecturer_id'],
                            'group_selection' => $row['group_selection'],
                            'faculty_id' => (int) $row['faculty_id'],
                        ];

                        $reservedFacultySlots[(int) $row['faculty_id']][$day][$startTime] = true;
                    }

                    $reservedLecturerSlots[$session['lecturer_id']][$day][$startTime] = true;
                    foreach ($pickedVenues['venue_ids'] as $venueId) {
                        $reservedVenueSlots[$day][$startTime][$venueId] = true;
                    }

                    $scheduled = true;
                    $scheduledCount++;
                    break;
                }

                if (!$scheduled) {
                    $errors[] = "Could not schedule {$session['course_code']} session " . ($i + 1) . '.';
                }
            }

            if ($scheduledCount === 0) {
                $errors[] = "No session could be scheduled for {$session['course_code']}.";
            }
        }

        return [
            'timetables' => $timetables,
            'warnings' => $warnings,
            'errors' => $errors,
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
            'venue_ids' => collect($selected)->pluck('id')->map(fn ($id) => (int) $id)->all(),
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
}
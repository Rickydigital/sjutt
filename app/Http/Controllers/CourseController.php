<?php

namespace App\Http\Controllers;

use App\Exports\CoursesExport;
use App\Imports\CoursesImport;
use App\Models\Program;
use App\Models\Course;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\Timetable;
use App\Services\TimetableScheduler;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    /**
     * Display a listing of courses based on user role.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Course::with(['lecturers', 'faculties', 'semester']);

        // Role-based filtering
        if ($user->hasRole('Lecturer')) {
            $query->whereHas('lecturers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $query->whereHas('faculties', function ($q) use ($programIds) {
                $q->whereHas('programs', function ($q) use ($programIds) {
                    $q->whereIn('programs.id', $programIds);
                });
            });
        } elseif (!$user->hasAnyRole(['Admin', 'Timetable Officer'])) {
            $query->whereRaw('1 = 0');
        }

        // Search by course code or name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('course_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filter by faculty
        if ($request->has('faculty_id') && !empty($request->faculty_id)) {
            $query->whereHas('faculties', function ($q) use ($request) {
                $q->where('faculties.id', $request->faculty_id);
            });
        }

        // Filter by semester
        if ($request->has('semester_id') && !empty($request->semester_id)) {
            $query->where('semester_id', $request->semester_id);
        }

        $courses = $query->paginate(98);
        $faculties = Faculty::all();
        $semesters = Semester::all();

        return view('admin.courses.index', compact('courses', 'faculties', 'semesters'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action');
        }

        $lecturers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Lecturer');
        })->get();
        $faculties = Faculty::all();
        $semesters = Semester::all();

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $faculties = Faculty::whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->get();
        }

        return view('admin.courses.create', compact('lecturers', 'faculties', 'semesters'));
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'course_code' => 'required|string|max:255|unique:courses',
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (str_word_count($value) > 5) {
                        $fail('The course name must not exceed 5 words.');
                    }
                },
            ],
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'hours' => 'required|integer|min:1',
            'practical_hrs' => [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if (!is_null($value) && $value > $request->input('hours')) {
                        $fail('Practical hours cannot exceed total hours.');
                    }
                },
            ],
            'session' => 'required|integer|min:1',
            'semester_id' => 'required|exists:semesters,id',
            'cross_catering' => 'boolean',
            'is_workshop' => 'boolean',
            'lecturer_ids' => 'nullable|array',
            'lecturer_ids.*' => 'exists:users,id',
            'faculty_ids' => 'required|array',
            'faculty_ids.*' => 'exists:faculties,id',
        ]);

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $validFacultyIds = Faculty::whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->pluck('id')->toArray();

            if (array_diff($request->faculty_ids, $validFacultyIds)) {
                return redirect()->back()->withErrors(['faculty_ids' => 'Selected faculties are not part of your programs.']);
            }
        }

        $courseData = $request->only([
            'course_code',
            'name',
            'description',
            'credits',
            'hours',
            'practical_hrs',
            'session',
            'semester_id',
        ]);
        $courseData['cross_catering'] = $request->has('cross_catering');
        $courseData['is_workshop'] = $request->has('is_workshop');

        $course = Course::create($courseData);
        if ($request->lecturer_ids) {
            $course->lecturers()->sync($request->lecturer_ids);
        }
        if ($request->faculty_ids) {
            $course->faculties()->sync($request->faculty_ids);
        }

        return redirect()->route('courses.index')->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        $user = Auth::user();
        if ($user->hasRole('Lecturer')) {
            $hasAccess = $course->lecturers()->where('users.id', $user->id)->exists();
            if (!$hasAccess) {
                abort(403, 'Unauthorized action.');
            }
        } elseif ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $hasAccess = $course->faculties()->whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->exists();
            if (!$hasAccess) {
                abort(403, 'Unauthorized action.');
            }
        } elseif (!$user->hasAnyRole(['Admin', 'Timetable Officer'])) {
            abort(403, 'Unauthorized action.');
        }

        return view('admin.courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $hasAccess = $course->faculties()->whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->exists();

            if (!$hasAccess) {
                abort(403, 'You are not authorized to edit this course.');
            }
        }

        $lecturers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Lecturer');
        })->get();
        $faculties = Faculty::all();
        $semesters = Semester::all();

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $faculties = Faculty::whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->get();
        }

        return view('admin.courses.edit', compact('course', 'lecturers', 'faculties', 'semesters'));
    }

    /**
     * Update the specified course in storage.
     */
    /**
     * Update the specified course in storage.
     */
    /**
     * Update the specified course in storage.
     */
    public function update(Request $request, Course $course)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'course_code' => 'required|string|max:255|unique:courses,course_code,' . $course->id,
            'name' => [
                'required',
                'string',
                'max:255',
                fn($a, $v, $f) => str_word_count($v) > 5 ? $f('The course name must not exceed 5 words.') : null,
            ],
            'description'   => 'nullable|string',
            'credits'       => 'required|integer|min:1',
            'hours'         => 'required|integer|min:1',
            'practical_hrs' => [
                'nullable',
                'integer',
                'min:0',
                fn($a, $v, $f) => !is_null($v) && $v > $request->hours ? $f('Practical hours cannot exceed total hours.') : null,
            ],
            'session'       => 'required|integer|min:1',
            'semester_id'   => 'required|exists:semesters,id',
            'cross_catering' => 'boolean',
            'is_workshop'   => 'boolean',
            'lecturer_ids'  => 'nullable|array',
            'lecturer_ids.*' => 'exists:users,id',
            'faculty_ids'   => 'required|array',
            'faculty_ids.*' => 'exists:faculties,id',
        ]);

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $validFacultyIds = Faculty::whereHas('programs', fn($q) => $q->whereIn('programs.id', $programIds))
                ->pluck('id')->toArray();

            if (array_diff($request->faculty_ids, $validFacultyIds)) {
                return back()->withErrors(['faculty_ids' => 'Selected faculties are not part of your programs.']);
            }
        }

        $old = $course->only(['course_code', 'session']);
        $oldLecturerIds = $course->lecturers()->pluck('users.id')->toArray();

        $new = $request->only([
            'course_code',
            'name',
            'description',
            'credits',
            'hours',
            'practical_hrs',
            'session',
            'semester_id'
        ]);
        $new['cross_catering'] = $request->has('cross_catering');
        $new['is_workshop']    = $request->has('is_workshop');

        // Update course
        $course->update($new);
        $course->lecturers()->sync($request->lecturer_ids ?: []);
        $course->faculties()->sync($request->faculty_ids ?: []);

        $newLecturerIds = $request->lecturer_ids ?? [];

        // ------------------------------------------------------------
        // TIMETABLE SYNC
        // ------------------------------------------------------------
        DB::transaction(function () use ($course, $old, $new, $oldLecturerIds, $newLecturerIds) {
            Log::info('CourseController@update: TIMETABLE SYNC STARTED', [
                'course_id' => $course->id,
                'old' => $old,
                'new' => $new,
                'old_lecturers' => $oldLecturerIds,
                'new_lecturers' => $newLecturerIds,
            ]);

            $tt = $course->timetables()->with(['faculty', 'venue'])->get();
            Log::info('Found timetable rows', ['count' => $tt->count()]);

            // 1. Update course_code
            if ($new['course_code'] !== $old['course_code']) {
                $updated = $course->timetables()->update(['course_code' => $new['course_code']]);
                Log::info('Updated course_code in timetable', ['rows' => $updated]);
            }

            // 2. Update lecturer_id in ALL timetable rows
            if ($oldLecturerIds != $newLecturerIds) {
                if (empty($newLecturerIds)) {
                    Log::warning('No lecturers assigned — clearing lecturer_id from timetable');
                    $course->timetables()->update(['lecturer_id' => null]);
                } elseif (count($newLecturerIds) === 1) {
                    // Single lecturer → assign to all rows
                    $newLecturerId = $newLecturerIds[0];
                    $updated = $course->timetables()->update(['lecturer_id' => $newLecturerId]);
                    Log::info('Assigned single lecturer to all timetable rows', [
                        'lecturer_id' => $newLecturerId,
                        'rows' => $updated
                    ]);
                } else {
                    // Multiple lecturers → distribute evenly
                    $rows = $course->timetables()->get();
                    $chunks = $rows->chunk(ceil($rows->count() / count($newLecturerIds)));

                    foreach ($chunks as $index => $chunk) {
                        $lecturerId = $newLecturerIds[$index % count($newLecturerIds)];
                        $ids = $chunk->pluck('id')->toArray();
                        Timetable::whereIn('id', $ids)->update(['lecturer_id' => $lecturerId]);
                        Log::info('Assigned lecturer to chunk', ['lecturer_id' => $lecturerId, 'rows' => count($ids)]);
                    }
                }
            }

            // 3. Session count change
            if ($new['session'] != $old['session']) {
                $required = (int)$new['session'];
                $grouped = $tt->groupBy(fn($t) => "{$t->lecturer_id}|{$t->group_selection}|{$t->activity}");

                foreach ($grouped as $entries) {
                    $current = $entries->count();

                    // DELETE excess
                    if ($current > $required) {
                        $toDelete = $entries->skip($required)->pluck('id');
                        $deleted = Timetable::whereIn('id', $toDelete)->delete();
                        Log::info('Deleted excess rows', ['ids' => $toDelete->toArray(), 'deleted' => $deleted]);
                    }

                    // ADD missing
                    if ($current < $required) {
                        $missing = $required - $current;
                        $sample  = $entries->first();
                        $venueIds = $entries->pluck('venue_id')->unique()->values()->toArray();

                        Log::info('Adding missing sessions', ['missing' => $missing, 'venue_ids' => $venueIds]);

                        $this->addMissingSessions($course, $missing, $sample, $venueIds, $newLecturerIds);
                    }
                }
            }
        });

        return redirect()->route('courses.index')
            ->with('success', 'Course and timetable updated successfully.');
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy(Course $course)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        if ($user->hasRole('Administrator')) {
            $programIds = Program::where('administrator_id', $user->id)->pluck('id');
            $hasAccess = $course->faculties()->whereHas('programs', function ($q) use ($programIds) {
                $q->whereIn('programs.id', $programIds);
            })->exists();

            if (!$hasAccess) {
                abort(403, 'You are not authorized to delete this course.');
            }
        }

        $course->delete();
        return redirect()->route('courses.index')->with('success', 'Course deleted successfully.');
    }


    // Inside CourseController (add this method)
    /**
     * Add missing timetable sessions with correct lecturer assignment.
     *
     * @param Course $course
     * @param int $missing
     * @param mixed $sample          A sample timetable row (for venue, group, etc.)
     * @param array $venueIds
     * @param array $newLecturerIds  Array of current lecturer IDs from the form
     */
    private function addMissingSessions(Course $course, int $missing, $sample, array $venueIds, array $newLecturerIds = [])
    {
        Log::info('addMissingSessions called', [
            'course_id' => $course->id,
            'missing' => $missing,
            'venue_ids' => $venueIds,
            'new_lecturer_ids' => $newLecturerIds,
            'sample_lecturer_id' => $sample->lecturer_id ?? null,
        ]);

        $days      = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];
        $venues    = \App\Models\Venue::whereIn('id', $venueIds)->get();

        if ($venues->isEmpty()) {
            Log::warning('No venues available for adding timetable sessions', ['venue_ids' => $venueIds]);
            return;
        }

        // Load all existing timetable entries in this semester to avoid conflicts
        $existing = Timetable::where('semester_id', $course->semester_id)->get();
        $used = $lecturer = $group = $courseSlot = [];

        foreach ($existing as $e) {
            $k  = "{$e->day}|{$e->start_time}|{$e->venue_id}";
            $lk = "{$e->day}|{$e->start_time}|{$e->lecturer_id}";
            $gk = "{$e->day}|{$e->start_time}|{$e->group_selection}";
            $ck = "{$e->day}|{$e->start_time}|{$e->course_code}";

            $used[$k] = $lecturer[$lk] = $group[$gk] = $courseSlot[$ck] = true;
        }

        $hours = $course->hours;
        $added = 0;
        $insert = [];

        // Choose lecturer for new rows: prefer new list, fallback to sample
        $availableLecturers = !empty($newLecturerIds) ? $newLecturerIds : ($sample->lecturer_id ? [$sample->lecturer_id] : []);

        if (empty($availableLecturers)) {
            Log::warning('No lecturer available to assign to new timetable sessions');
            return;
        }

        foreach ($days as $day) {
            foreach ($timeSlots as $start) {
                if ($added >= $missing) break 2;

                $end = date('H:i', strtotime("$start +{$hours} hours"));

                foreach ($venues as $venue) {
                    $slotKey   = "$day|$start|{$venue->id}";
                    $courseKey = "$day|$start|{$course->course_code}";

                    // Skip if venue/time is already used
                    if (isset($used[$slotKey]) || isset($courseSlot[$courseKey])) {
                        continue;
                    }

                    // Pick a lecturer (round-robin if multiple)
                    $lecturerId = $availableLecturers[$added % count($availableLecturers)];

                    $lecKey = "$day|$start|{$lecturerId}";
                    $grpKey = "$day|$start|{$sample->group_selection}";

                    if (isset($lecturer[$lecKey]) || isset($group[$grpKey])) {
                        continue;
                    }

                    $insert[] = [
                        'course_code'     => $course->course_code,
                        'lecturer_id'     => $lecturerId,
                        'faculty_id'      => $sample->faculty_id,
                        'venue_id'        => $venue->id,
                        'day'             => $day,
                        'start_time'      => $start,
                        'end_time'        => $end,
                        'activity'        => $sample->activity,
                        'group_selection' => $sample->group_selection,
                        'semester_id'     => $course->semester_id,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];

                    // Mark as used
                    $used[$slotKey]   = true;
                    $lecturer[$lecKey] = true;
                    $group[$grpKey]   = true;
                    $courseSlot[$courseKey] = true;

                    $added++;
                    break;
                }
            }
        }

        if ($insert) {
            Timetable::insert($insert);
            Log::info('Inserted new timetable sessions', [
                'count' => count($insert),
                'lecturer_distribution' => array_count_values(array_column($insert, 'lecturer_id')),
                'rows' => $insert
            ]);
        } else {
            Log::warning('Could not find any free slots to add missing sessions', [
                'missing' => $missing,
                'venues' => $venueIds
            ]);
        }
    }

    /**
     * Import courses from an Excel file.
     */
    public function import(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'Administrator'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new CoursesImport, $request->file('file'));

        return back()->with('success', 'Courses imported successfully!');
    }

    /**
     * Export courses to an Excel file.
     */
    public function export()
    {
        return Excel::download(new CoursesExport, 'courses_template.xlsx');
    }
}

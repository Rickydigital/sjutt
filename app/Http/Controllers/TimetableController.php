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

        $faculty = Faculty::findOrFail($validated['faculty_id']);
        $venue = Venue::findOrFail($validated['venue_id']);
        $course = Course::where('course_code', $validated['course_code'])->first();

        // Convert group_selection array to comma-separated string
        $validated['group_selection'] = implode(',', $validated['group_selection']);

        // Add course_name if available
        $validated['course_name'] = $course->name ?? null;

        // Validate venue capacity
        $studentCount = $this->calculateStudentCount($faculty, $validated['group_selection']);
        if ($venue->capacity < $studentCount) {
            return back()->withInput()->withErrors([
                'venue_id' => "Venue capacity ({$venue->capacity}) is less than required students ({$studentCount})."
            ]);
        }

        // Check conflicts
        $conflicts = $this->checkConflicts($request, $validated);
        if (!empty($conflicts)) {
            return back()->withInput()->withErrors([
                'conflict' => implode(' ', $conflicts)
            ]);
        }

        try {
            Timetable::create($validated);
        } catch (\Exception $e) {
            Log::error('Failed to create timetable: ' . $e->getMessage(), $validated);
            return back()->withInput()->withErrors(['error' => 'Failed to create timetable.']);
        }

        return redirect()->route('timetable.index')->with('success', 'Timetable entry created successfully.');
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

        $timetable->group_details = $groupDetails;
        return response()->json($timetable);
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
        $conflicts = [];

        // 1. Lecturer Conflict Check
        if ($validated['lecturer_id']) {
            $lecturerConflict = Timetable::where('day', $validated['day'])
                ->where('lecturer_id', $validated['lecturer_id'])
                ->where(function ($query) use ($validated) {
                    $query->where(function ($q) use ($validated) {
                        $q->where('time_start', '<', $validated['time_end'])
                            ->where('time_end', '>', $validated['time_start']);
                    });
                })
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();
            if ($lecturerConflict) {
                $conflicts[] = "Lecturer is assigned to session {$lecturerConflict->course_code} for {$lecturerConflict->faculty->name} from {$lecturerConflict->time_start} to {$lecturerConflict->time_end}.";
            }
        }

        // 2. Venue Conflict Check (Availability and Capacity already in store)
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
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();
            if ($groupConflict) {
                $conflicts[] = "Group $group has a session for {$groupConflict->course_code} from {$groupConflict->time_start} to {$groupConflict->time_end}.";
            }
        }

        // 4. Faculty "All Groups" Conflict Check
        if ($validated['group_selection'] === 'All Groups') {
            $facultyConflict = Timetable::where('day', $validated['day'])
                ->where('faculty_id', $validated['faculty_id'])
                ->where(function ($query) use ($validated) {
                    $query->where(function ($q) use ($validated) {
                        $q->where('time_start', '<', $validated['time_end'])
                            ->where('time_end', '>', $validated['time_start']);
                    });
                })
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->first();
            if ($facultyConflict) {
                $conflicts[] = "Faculty {$facultyConflict->faculty->name} has a session for groups ({$facultyConflict->group_selection}) with {$facultyConflict->course_code} from {$facultyConflict->time_start} to {$facultyConflict->time_end}.";
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
                $conflicts[] = "Faculty {$allGroupsConflict->faculty->name} has a session for all groups with {$allGroupsConflict->course_code} from {$allGroupsConflict->time_start} to {$allGroupsConflict->time_end}.";
            }
        }

        // 5. Additional Enhancement: Course Conflict Check
        $courseConflict = Timetable::where('day', $validated['day'])
            ->where('course_code', $validated['course_code'])
            ->where('faculty_id', $validated['faculty_id'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('time_start', '<', $validated['time_end'])
                        ->where('time_end', '>', $validated['time_start']);
                });
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();
        if ($courseConflict) {
            $conflicts[] = "Course {$validated['course_code']} is already scheduled for {$courseConflict->faculty->name} from {$courseConflict->time_start} to {$courseConflict->time_end}.";
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
}
<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Timetable;
use App\Models\ExaminationTimetable;
use App\Models\Student;
use App\Models\TimetableSemester;
use App\Models\User;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $timetables = Timetable::all();
        return response()->json(['data' => $timetables], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // This method is typically used for web views to return a form.
        // For an API, you might not need this, but it’s included for completeness.
        return response()->json(['message' => 'Create form not implemented for API'], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'year_id' => 'required|exists:years,id',
            'day' => 'required|string',
            'time_start' => 'required|date_format:H:i:s',
            'time_end' => 'required|date_format:H:i:s|after:time_start',
            'course_code' => 'required|string|max:255',
            'activity' => 'required|string|max:255',
            'venue_id' => 'required|exists:venues,id',
        ]);

        $timetable = Timetable::create($request->all());
        return response()->json(['data' => $timetable, 'message' => 'Timetable created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $timetable = Timetable::findOrFail($id);
        return response()->json(['data' => $timetable], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Similar to create, this is for web views. For API, you might skip this.
        return response()->json(['message' => 'Edit form not implemented for API'], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $timetable = Timetable::findOrFail($id);

        $request->validate([
            'faculty_id' => 'sometimes|exists:faculties,id',
            'year_id' => 'sometimes|exists:years,id',
            'day' => 'sometimes|string',
            'time_start' => 'sometimes|date_format:H:i:s',
            'time_end' => 'sometimes|date_format:H:i:s|after:time_start',
            'course_code' => 'sometimes|string|max:255',
            'activity' => 'sometimes|string|max:255',
            'venue_id' => 'sometimes|exists:venues,id',
        ]);

        $timetable->update($request->all());
        return response()->json(['data' => $timetable, 'message' => 'Timetable updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $timetable = Timetable::findOrFail($id);
        $timetable->delete();
        return response()->json(['message' => 'Timetable deleted successfully'], 200);
    }


/**
 * Fetch lecture timetables for a specific faculty.
 */
public function getLectureTimetables(Request $request)
{
    $facultyId = $request->query('faculty_id');

    
    if (!$facultyId) {
        return response()->json([
            'success' => false,
            'error'   => 'Missing faculty_id'
        ], 400);
    }

  
    if (!TimetableSemester::exists()) {
        return response()->json([
            'success' => false,
            'error'   => 'No timetable semester configured.'
        ], 422);
    }

    $timetableSemester = TimetableSemester::with('semester')->firstOrFail();
    $semesterId        = $timetableSemester->semester_id;
    $semesterName      = $timetableSemester->semester->name ?? 'N/A';

    
    $timetables = Timetable::with(['lecturer', 'venue', 'course'])
        ->where('faculty_id', $facultyId)
        ->where('semester_id', $semesterId)
        ->whereIn('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])
        ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")
        ->orderBy('time_start')
        ->get();

    
    $grouped = $timetables->groupBy('day')->map(function ($entries) {
        return $entries->values(); // re-index to 0,1,2…
    });

    // Ensure every day exists (even if empty)
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    foreach ($days as $day) {
        if (!isset($grouped[$day])) {
            $grouped[$day] = collect([]);
        }
    }

 
    return response()->json([
        'success'   => true,
        'semester'  => $semesterName,
        'data'      => $grouped
    ], 200);
}


/**
 * GET /api/venue-timetables?venue_id=5
 * Returns lecture timetable for a specific venue (like faculty timetable)
 */
public function getVenueTimetables(Request $request)
{
    $venueId = $request->query('venue_id');

    if (!$venueId) {
        return response()->json([
            'success' => false,
            'error'   => 'Missing venue_id'
        ], 400);
    }

    if (!\App\Models\Venue::find($venueId)) {
        return response()->json([
            'success' => false,
            'error'   => 'Venue not found'
        ], 404);
    }

    if (!TimetableSemester::exists()) {
        return response()->json([
            'success' => false,
            'error'   => 'No timetable semester configured.'
        ], 422);
    }

    $timetableSemester = TimetableSemester::with('semester')->firstOrFail();
    $semesterId        = $timetableSemester->semester_id;
    $semesterName      = $timetableSemester->semester->name ?? 'N/A';

    $timetables = Timetable::with(['lecturer', 'faculty', 'course'])
        ->where('venue_id', $venueId)
        ->where('semester_id', $semesterId)
        ->whereIn('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])
        ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")
        ->orderBy('time_start')
        ->get();

    $grouped = $timetables->groupBy('day')->map(function ($entries) {
        return $entries->map(function ($entry) {
            return [
                'id'           => $entry->id,
                'course_code'  => $entry->course_code,
                'activity'     => $entry->activity,
                'faculty'      => $entry->faculty?->name ?? '—',
                'lecturer'     => $entry->lecturer?->name ?? '—',
                'time_start'   => $entry->time_start,
                'time_end'     => $entry->time_end,
                'duration'     => (strtotime($entry->time_end) - strtotime($entry->time_start)) / 3600 . 'h',
            ];
        })->values();
    });

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    foreach ($days as $day) {
        if (!isset($grouped[$day])) {
            $grouped[$day] = collect([]);
        }
    }

    return response()->json([
        'success'   => true,
        'venue_id'  => (int) $venueId,
        'semester'  => $semesterName,
        'data'      => $grouped
    ], 200);
}

    public function getExaminationTimetables(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $yearId = $request->query('year_id');

        // Validate query parameters if needed
        if (!$facultyId || !$yearId) {
            return response()->json(['error' => 'Missing faculty_id or year_id'], 400);
        }

        // Fetch examination timetables
        $timetables = ExaminationTimetable::where('faculty_id', $facultyId)
            ->where('year_id', $yearId)
            ->get();

        return response()->json(['data' => $timetables], 200);
    }

 public function getLecturerTimetables(Request $request)
{
    $lecturerId = $request->query('lecturer_id');

    if (!$lecturerId) {
        return response()->json([
            'success' => false,
            'error'   => 'Missing lecturer_id'
        ], 400);
    }

    $lecturer = User::find($lecturerId);

    if (!$lecturer || !$lecturer->hasRole('Lecturer')) {
        return response()->json([
            'success' => false,
            'error'   => 'Lecturer not found'
        ], 404);
    }

    if (!TimetableSemester::exists()) {
        return response()->json([
            'success' => false,
            'error'   => 'No timetable semester configured.'
        ], 422);
    }

    $timetableSemester = TimetableSemester::with('semester')->firstOrFail();
    $semesterId        = $timetableSemester->semester_id;
    $semesterName      = $timetableSemester->semester->name ?? 'N/A';

    $timetables = Timetable::with(['faculty', 'venue', 'course'])
        ->where('lecturer_id', $lecturerId)
        ->where('semester_id', $semesterId)
        ->whereIn('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])
        ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")
        ->orderBy('time_start')
        ->get();

    // Group by day → then by time slot + venue + course
    $groupedByDay = $timetables->groupBy('day');

    $finalData = collect();

    foreach ($groupedByDay as $day => $dayEntries) {
        $slots = $dayEntries->groupBy(function ($item) {
            return $item->time_start . '|' . $item->time_end . '|' . $item->venue_id . '|' . $item->course_code . '|' . $item->activity;
        })->map(function ($group) {
            $first = $group->first();

            // Collect all unique faculties
            $faculties = $group->pluck('faculty.name')->filter()->unique()->values();

            return [
                'course_code'     => $first->course_code,
                'course_name'     => $first->course?->name ?? '—',
                'activity'        => $first->activity,
                'venue'           => $first->venue?->longform ?? $first->venue?->name ?? '—',
                'venue_code'      => $first->venue?->name ?? '—',
                'time_start'      => substr($first->time_start, 0, 5), // "08:00"
                'time_end'        => substr($first->time_end, 0, 5),   // "10:00"
                'duration'        => (strtotime($first->time_end) - strtotime($first->time_start)) / 3600 . 'h',
                'faculties'       => $faculties->implode(' / '),       // e.g., "FANAS 1 / BAEd 1 / BATh 1"
                'faculty_count'   => $faculties->count(),
                'group_selection' => $group->first()->group_selection ?? 'All Groups',
            ];
        })->values();

        $finalData[$day] = $slots;
    }

    // Ensure all days exist (even if empty)
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    foreach ($days as $day) {
        if (!isset($finalData[$day])) {
            $finalData[$day] = [];
        }
    }

    return response()->json([
        'success'       => true,
        'lecturer_id'   => (int) $lecturerId,
        'lecturer_name' => $lecturer->name,
        'semester'      => $semesterName,
        'data'          => $finalData
    ], 200);
}

public function getCourseStudents(Request $request)
{
    $courseCode = $request->query('course_code');

    if (!$courseCode) {
        return response()->json([
            'success' => false,
            'error'   => 'Missing course_code parameter'
        ], 400);
    }

    // Find the course
    $course = Course::with('faculties')->where('course_code', $courseCode)->first();

    if (!$course) {
        return response()->json([
            'success' => false,
            'error'   => 'Course not found'
        ], 404);
    }

    // Get all faculty IDs that offer this course
    $facultyIds = $course->faculties->pluck('id')->toArray();

    if (empty($facultyIds)) {
        return response()->json([
            'success' => true,
            'course_code' => $course->course_code,
            'course_name' => $course->name,
            'total_students' => 0,
            'students' => []
        ], 200);
    }

    // Fetch all students in those faculties
    $students = Student::with(['faculty', 'program'])
        ->whereIn('faculty_id', $facultyIds)
        ->select('id', 'first_name', 'last_name', 'reg_no', 'faculty_id', 'program_id', 'gender', 'phone')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get()
        ->map(function ($student) {
            return [
                'id'         => $student->id,
                'full_name'  => trim("{$student->first_name} {$student->last_name}"),
                'reg_no'     => $student->reg_no,
                'faculty'    => $student->faculty?->name ?? '—',
                'program'    => $student->program?->name ?? '—',
                'gender'     => $student->gender,
                'phone'      => $student->phone,
            ];
        });

    return response()->json([
        'success'         => true,
        'course_code'     => $course->course_code,
        'course_name'     => $course->name ?? '—',
        'total_students'  => $students->count(),
        'students'        => $students
    ], 200);
}
}
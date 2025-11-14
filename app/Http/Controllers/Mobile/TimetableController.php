<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\ExaminationTimetable;
use App\Models\TimetableSemester;
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
            $grouped[$day] = [];
        }
    }

 
    return response()->json([
        'success'   => true,
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
}
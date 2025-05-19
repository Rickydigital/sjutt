<?php

namespace App\Http\Controllers\Admin;

use App\Models\Query;
use App\Models\QueryProgress;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class QueryController extends Controller
{
    public function index()
    {
        // Fetch all queries
        $queries = Query::with('progress')->get();

        return view('admin.queries.index', compact('queries'));
    }

    public function addProgress(Request $request, $queryId)
    {
        // Validate the incoming request
        $request->validate([
            'admin_description' => 'required|string',
        ]);

        // Find the query
        $query = Query::findOrFail($queryId);

        // Update the status of the query
        $query->status = 'Investigation'; // Change as needed (to Processed, etc.)
        $query->save();

        // Create a progress record
        QueryProgress::create([
            'query_id' => $query->id,
            'admin_description' => $request->admin_description,
        ]);

        return redirect()->route('admin.queries.index')->with('message', 'Progress added successfully');
    }
}


public function store(Request $request)
{
    Log::info('Attempting to store new timetable', ['input' => $request->all()]);

    try {
        $validator = Validator::make($request->all(), [
            'faculty_id' => 'required|exists:faculties,id',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i,H:i:s',
            'end_time' => 'required|date_format:H:i,H:i:s|after:start_time',
            'course_code' => 'required|exists:courses,course_code',
            'venue_id' => 'required|exists:venues,id',
            'group_selection' => 'required|array',
            'group_selection.*' => 'string',
            'lecturer_ids' => 'required|array',
            'lecturer_ids.*' => 'exists:lecturers,id',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for timetable store', ['errors' => $validator->errors()]);
            return response()->json([
                'error' => 'Validation failed.',
                'details' => $validator->errors()
            ], 422);
        }

        $examSetupId = $request->exam_setup_id ?? ExaminationSetup::first()?->id;
        if (!$examSetupId) {
            Log::warning('No examination setup available');
            return response()->json(['error' => 'No examination setup available. Please create one first.'], 400);
        }

        $setup = ExaminationSetup::find($examSetupId);
        if (!$setup) {
            Log::warning('Examination setup not found', ['exam_setup_id' => $examSetupId]);
            return response()->json(['error' => 'Examination setup not found.'], 400);
        }

        // Normalize time formats to H:i:s
        $startTime = Carbon::createFromFormat('H:i:s|H:i', $request->start_time)->format('H:i:s');
        $endTime = Carbon::createFromFormat('H:i:s|H:i', $request->end_time)->format('H:i:s');

        // Check for time slot conflicts
        $conflict = ExaminationTimetable::where('exam_date', $request->exam_date)
            ->where('venue_id', $request->venue_id)
            ->where('start_time', $startTime)
            ->where('end_time', $endTime)
            ->exists();

        if ($conflict) {
            Log::warning('Time slot conflict detected', [
                'exam_date' => $request->exam_date,
                'venue_id' => $request->venue_id,
                'start_time' => $startTime,
                'end_time' => $endTime
            ]);
            return response()->json(['error' => 'This time slot is already booked for the selected venue.'], 409);
        }

        $timetable = ExaminationTimetable::create([
            'exam_setup_id' => $setup->id,
            'faculty_id' => $request->faculty_id,
            'course_code' => $request->course_code,
            'exam_date' => $request->exam_date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'venue_id' => $request->venue_id,
            'group_selection' => implode(',', $request->group_selection),
            'time_slot_name' => $request->time_slot ? json_decode($request->time_slot)->name : null,
        ]);

        $timetable->lecturers()->sync($request->lecturer_ids);

        Log::info('Timetable created successfully', [
            'timetable_id' => $timetable->id,
            'course_code' => $timetable->course_code,
            'exam_date' => $timetable->exam_date
        ]);

        return response()->json(['success' => 'Timetable created successfully.']);
    } catch (\Exception $e) {
        Log::error('Error storing timetable', [
            'error' => $e->getMessage(),
            'input' => $request->all()
        ]);
        return response()->json(['error' => 'Failed to create timetable.'], 500);
    }
}
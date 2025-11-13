<?php

namespace App\Http\Controllers;

use App\Models\TimetableSemester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimetableSemesterController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'semester_id' => 'required|exists:semesters,id',
            'academic_year' => 'nullable|string|max:255',
            'start_date' => 'nullable|date|before:end_date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        TimetableSemester::create($validator->validated());
        return response()->json(['message' => 'Timetable semester added successfully']);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'semester_id' => 'required|exists:semesters,id',
            'academic_year' => 'nullable|string|max:255',
            'start_date' => 'nullable|date|before:end_date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $semester = TimetableSemester::firstOrFail();
        $semester->update($validator->validated());
        return response()->json(['message' => 'Timetable semester updated successfully']);
    }

    public function show()
    {
        $timetableSemester = TimetableSemester::with('semester')->first();
        if (!$timetableSemester) {
            return response()->json(['errors' => ['semester' => 'No timetable semester found.']], 422);
        }
        return response()->json([
            'id' => $timetableSemester->id,
            'semester_id' => $timetableSemester->semester_id,
            'academic_year' => $timetableSemester->academic_year,
            'start_date' => $timetableSemester->start_date,
            'end_date' => $timetableSemester->end_date,
            'semester_name' => $timetableSemester->semester ? $timetableSemester->semester->name : 'N/A',
        ]);
    }
}
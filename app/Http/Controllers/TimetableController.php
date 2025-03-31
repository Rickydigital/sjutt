<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TimetableImport;

class TimetableController extends Controller
{
      
    public function index(Request $request)
    {
        $search = $request->query('search');
        $dayFilter = $request->query('day');
        $facultyFilter = $request->query('faculty');
        $yearFilter = $request->query('year');

        // Fetch all timetable entries with filters applied
        $query = Timetable::query();

        // Apply search filter (broad keyword search)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('faculty', 'like', "%{$search}%")
                  ->orWhere('year', 'like', "%{$search}%")
                  ->orWhere('course_code', 'like', "%{$search}%")
                  ->orWhere('activity', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%");
            });
        }

        // Apply specific filters
        if ($dayFilter) {
            $query->where('day', $dayFilter);
        }

        if ($facultyFilter) {
            $query->where('faculty', 'like', "%{$facultyFilter}%");
        }

        if ($yearFilter) {
            $query->where('year', 'like', "%{$yearFilter}%");
        }

        // Fetch all entries without pagination, grouped by day
        $timetables = $query->get()->groupBy('day');

        // Get unique values for filter dropdowns
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $faculties = Timetable::select('faculty')->distinct()->pluck('faculty')->sort();
        $years = Timetable::select('year')->distinct()->pluck('year')->sort();

        return view('timetable.index', compact('timetables', 'days', 'faculties', 'years', 'dayFilter', 'facultyFilter', 'yearFilter'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        Excel::import(new TimetableImport, $request->file('file'));

        return redirect()->route('timetable.index')->with('success', 'Timetable imported successfully.');
    }

    public function edit($id)
    {
        $timetable = Timetable::findOrFail($id);
        return view('admin.timetable.edit', compact('timetable'));
    }

    public function update(Request $request, $id)
    {
        $timetable = Timetable::findOrFail($id);

        $request->validate([
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'faculty' => 'required|string|max:255',
            'year' => 'required|string|max:255',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
            'course_code' => 'required|string|max:255',
            'activity' => 'required|string|max:255',
            'venue' => 'required|string|max:255',
        ]);

        $timetable->update($request->all());

        return redirect()->route('timetable.index')->with('success', 'Timetable updated successfully.');
    }

    public function destroy($id)
    {
        $timetable = Timetable::findOrFail($id);
        $timetable->delete();

        return redirect()->route('timetable.index')->with('success', 'Timetable deleted successfully.');
    }
}

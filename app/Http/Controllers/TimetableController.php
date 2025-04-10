<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Year;
use App\Models\Venue;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Imports\TimetableImport;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $dayFilter = $request->query('day');
        $facultyFilter = $request->query('faculty');
        $yearFilter = $request->query('year');
    
        $query = Timetable::with(['faculty', 'year', 'venue']);
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('faculty', fn($f) => $f->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('year', fn($y) => $y->where('year', 'like', "%{$search}%"))
                  ->orWhere('course_code', 'like', "%{$search}%")
                  ->orWhere('activity', 'like', "%{$search}%")
                  ->orWhereHas('venue', fn($v) => $v->where('name', 'like', "%{$search}%"));
            });
        }
    
        if ($dayFilter) {
            $query->where('day', $dayFilter);
        }
    
        if ($facultyFilter) {
            $query->where('faculty_id', $facultyFilter); // Use ID directly for exact match
        }
    
        if ($yearFilter) {
            $query->where('year_id', $yearFilter); // Use ID directly for exact match
        }
    
        $timetables = $query->get();
    
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $faculties = Faculty::pluck('name', 'id')->sort();
        $years = Year::pluck('year', 'id')->sort();
    
        return view('timetable.index', compact('timetables', 'days', 'faculties', 'years', 'dayFilter', 'facultyFilter', 'yearFilter'));
    }

    public function create()
    {
        $faculties = Faculty::all();
        $years = Year::all();
        $venues = Venue::all();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        return view('admin.timetable.create', compact('faculties', 'years', 'venues', 'days'));
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'faculty_id' => 'required|exists:faculties,id',
            'year_id' => 'required|exists:years,id',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
            'course_code' => 'required|string|max:255',
            'activity' => 'required|string|max:255',
            'venue_id' => 'required|exists:venues,id',
        ]);

        Timetable::create($request->all());

        return redirect()->route('timetable.index')->with('success', 'Timetable entry created successfully.');
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
        $faculties = Faculty::all();
        $years = Year::all();
        $venues = Venue::all();
        return view('admin.timetable.edit', compact('timetable', 'faculties', 'years', 'venues'));
    }

    public function update(Request $request, $id)
    {
        $timetable = Timetable::findOrFail($id);

        $request->validate([
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'faculty_id' => 'required|exists:faculties,id',
            'year_id' => 'required|exists:years,id',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
            'course_code' => 'required|string|max:255',
            'activity' => 'required|string|max:255',
            'venue_id' => 'required|exists:venues,id',
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

    public function pdf()
    {
        $groupedTimetables = Timetable::with(['faculty', 'year', 'venue'])
            ->get()
            ->groupBy('faculty_id')
            ->map(function ($facultyGroup) {
                return $facultyGroup->groupBy('year_id');
            });

            

        $timeSlots = [
            '08:00', '09:00', '10:00', '11:00', '12:00', '13:00',
            '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'
        ];

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $pdf = PDF::loadView('timetable.pdf', compact('groupedTimetables', 'timeSlots', 'days'));
        return $pdf->download('timetable.pdf');
    }
}

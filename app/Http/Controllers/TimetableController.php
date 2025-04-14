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
            $query->where('faculty_id', $facultyFilter);
        }
    
        if ($yearFilter) {
            $query->where('year_id', $yearFilter);
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
    
        $conflictMessages = $this->checkConflicts($request);
    
        if (!empty($conflictMessages)) {
            return redirect()->back()->withInput()->withErrors([
                'conflict' => implode(' ', $conflictMessages)
            ]);
        }
    
        Timetable::create($request->all());
    
        return redirect()->route('timetable.index')->with('success', 'Timetable entry created successfully.');
    }
    
    

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
    
        $import = new TimetableImport;
        Excel::import($import, $request->file('file'));
    
        if (!empty($import->errors)) {
            return redirect()->route('timetable.index')
                ->withErrors(['import_errors' => $import->errors]);
        }
    
        return redirect()->route('timetable.index')
            ->with('success', 'Timetable imported successfully.');
    }
    
    

    public function edit($id)
    {
        $timetable = Timetable::findOrFail($id);
        $faculties = Faculty::all();
        $years = Year::all();
        $venues = Venue::all();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        return view('admin.timetable.edit', compact('timetable', 'faculties', 'years', 'venues', 'days'));
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
    
        $conflictMessages = $this->checkConflicts($request, $id);
    
        if (!empty($conflictMessages)) {
            return redirect()->back()->withInput()->withErrors([
                'conflict' => implode(' ', $conflictMessages)
            ]);
        }
    
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

        $pdf = Pdf::loadView('timetable.pdf', compact('groupedTimetables', 'timeSlots', 'days'));
        return $pdf->download('timetable.pdf');
    }

    private function checkConflicts($request, $excludeId = null)
    {
        $conflicts = Timetable::where('day', $request->day)
            ->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('time_start', '<', $request->time_end)
                      ->where('time_end', '>', $request->time_start);
                });
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->get();
    
        $conflictDetails = [];
    
        foreach ($conflicts as $conflict) {
            if ($conflict->venue_id == $request->venue_id) {
                $conflictDetails[] = 'Venue is already booked.';
            }
            if ($conflict->faculty_id == $request->faculty_id && $conflict->year_id == $request->year_id) {
                $conflictDetails[] = 'This faculty and year already has a session at this time.';
            }
        }
    
        return $conflictDetails;
    }
    
    
}
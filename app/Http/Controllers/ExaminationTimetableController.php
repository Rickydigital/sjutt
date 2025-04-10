<?php

namespace App\Http\Controllers;

use App\Models\ExaminationTimetable;
use App\Models\Faculty;
use App\Models\Year;
use App\Models\Venue;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ExaminationTimetableImport;

class ExaminationTimetableController extends Controller
{
    public function index(Request $request)
    {
        $query = ExaminationTimetable::with(['faculty', 'year', 'venue']); // Eager load relationships
    
        // Your existing filters...
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('course_code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('faculty', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('year', function($q) use ($request) {
                      $q->where('year', 'like', '%' . $request->search . '%');
                  });
            });
        }
    
        // Other filters remain the same...
    
        $timetables = $query->get();
        $faculties = Faculty::orderBy('name')->get();
        $programs = ExaminationTimetable::distinct()->pluck('program');
        $years = Year::orderBy('year')->get();
    
        $groupedTimetables = $timetables->groupBy('program')->map(function ($programTimetables) {
            // Get faculty objects
            $facultyIds = $programTimetables->pluck('faculty_id')->unique();
            $faculties = Faculty::whereIn('id', $facultyIds)->orderBy('name')->get();
            
            // Get year objects
            $yearIds = $programTimetables->pluck('year_id')->unique();
            $years = Year::whereIn('id', $yearIds)->orderBy('year')->get()->keyBy('id');
            
            return [
                'timetables' => $programTimetables,
                'faculties' => $faculties,
                'years' => $years // Add years collection keyed by ID
            ];
        });
    
        // Define your yearsList as actual Year objects
        $yearsList = Year::whereIn('id', [1, 2, 3, 4])->orderBy('year')->get();
    
        return view('timetables.index', compact('timetables', 'faculties', 'programs', 'years', 'groupedTimetables', 'yearsList'));
    }
    public function create()
    {
        $faculties = Faculty::orderBy('name')->get();
        $years = Year::orderBy('year')->get();
        $venues = Venue::orderBy('name')->get();
        
        return view('admin.exams.create', compact('faculties', 'years', 'venues'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'timetable_type' => 'required|string|max:255',
            'program' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'course_code' => 'required|string|max:255',
            'faculty_id' => 'required|exists:faculties,id',
            'year_id' => 'required|exists:years,id',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'venue_id' => 'required|exists:venues,id',
        ]);

        ExaminationTimetable::create($request->all());

        return redirect()->route('timetables.index')->with('success', 'Timetable created successfully!');
    }

    public function edit(ExaminationTimetable $timetable)
    {
        $faculties = Faculty::orderBy('name')->get();
        $years = Year::orderBy('year')->get();
        $venues = Venue::orderBy('name')->get();
        
        return view('admin.exams.edit', 
            compact('timetable', 'faculties', 'years', 'venues'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'timetable_type' => 'required|string|max:255',
            'program' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'course_code' => 'required|string|max:255',
            'faculty_id' => 'required|exists:faculties,id',
            'year_id' => 'required|exists:years,id',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'venue_id' => 'required|exists:venues,id',
        ]);

        $timetable = ExaminationTimetable::findOrFail($id);
        $timetable->update($request->all());

        return redirect()->route('timetables.index')->with('success', 'Timetable updated successfully!');
    }

    public function destroy($id)
    {
        $timetable = ExaminationTimetable::findOrFail($id);
        $timetable->delete();

        return redirect()->route('timetables.index')->with('success', 'Timetable deleted successfully!');
    }

    public function importView()
    {
        return view('timetables.imports');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx', 
        ]);

        try {
            Excel::import(new ExaminationTimetableImport, $request->file('file'));
            return redirect()->route('timetables.index')->with('success', 'Timetable imported successfully!');
        } catch (\Exception $e) {
            return redirect()->route('timetables.index')->with('error', 'Error importing timetable: ' . $e->getMessage());
        }
    }

    public function exportAllPdf(Request $request)
    {
        $timetables = ExaminationTimetable::with(['faculty', 'year', 'venue'])
            ->when($request->search, fn($query) => $query->where('course_code', 'like', "%{$request->search}%"))
            ->when($request->day, fn($query) => $query->where('exam_date', $request->day))
            ->when($request->faculty_id, fn($query) => $query->where('faculty_id', $request->faculty_id))
            ->when($request->program, fn($query) => $query->where('program', $request->program))
            ->when($request->year_id, fn($query) => $query->where('year_id', $request->year_id))
            ->get();
    
        $groupedTimetables = $timetables->groupBy('program')->map(function ($group) {
            $facultyIds = $group->pluck('faculty_id')->unique();
            $faculties = Faculty::whereIn('id', $facultyIds)->orderBy('name')->get();
            
            return [
                'timetables' => $group,
                'faculties' => $faculties,
                'timetable_type' => $group->first()->timetable_type,
                'semester' => $group->first()->semester,
                'date_range' => \Carbon\Carbon::parse($group->min('exam_date'))->format('M d') . ' - ' . 
                               \Carbon\Carbon::parse($group->max('exam_date'))->format('M d, Y'),
            ];
        });
    
        $allDays = $timetables->pluck('exam_date')->unique()->sort()->take(10);
        $week1Days = $allDays->slice(0, 5);
        $week2Days = $allDays->slice(5, 5);
        $timeSlots = [
            'Morning' => '08:00:00-10:00:00',
            'Noon' => '12:00:00-14:00:00',
            'Evening' => '16:00:00-18:00:00'
        ];
        $yearsList = Year::all();
    
        $pdf = Pdf::loadView('timetables.pdf_all', compact(
            'groupedTimetables', 'week1Days', 'week2Days', 'timeSlots', 'yearsList'
        ))->setPaper('A4', 'landscape');
    
        return $pdf->download('all_timetables.pdf');
    }
}

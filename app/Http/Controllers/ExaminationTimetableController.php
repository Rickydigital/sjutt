<?php

namespace App\Http\Controllers;

use App\Models\ExaminationTimetable;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ExaminationTimetableImport;

class ExaminationTimetableController extends Controller
{
    public function index(Request $request)
    {
        $query = ExaminationTimetable::query();

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('course_code', 'like', '%' . $request->search . '%')
                  ->orWhere('faculty', 'like', '%' . $request->search . '%')
                  ->orWhere('program', 'like', '%' . $request->search . '%');
            });
        }

        // Filters
        if ($request->has('day') && $request->day) {
            $query->whereDate('exam_date', $request->day);
        }
        if ($request->has('faculty') && $request->faculty) {
            $query->where('faculty', $request->faculty);
        }
        if ($request->has('program') && $request->program) {
            $query->where('program', $request->program);
        }
        if ($request->has('year') && $request->year) {
            $query->where('year', $request->year);
        }

        $timetables = $query->get();
        $faculties = ExaminationTimetable::distinct()->pluck('faculty'); // For filter dropdown
        $programs = ExaminationTimetable::distinct()->pluck('program');
        $years = ExaminationTimetable::distinct()->pluck('year');

        // Group by program and get unique faculties for each
        $groupedTimetables = $timetables->groupBy('program')->map(function ($programTimetables) {
            $programFaculties = $programTimetables->pluck('faculty')->unique()->values();
            return [
                'timetables' => $programTimetables,
                'faculties' => $programFaculties
            ];
        });

        return view('timetables.index', compact('timetables', 'faculties', 'programs', 'years', 'groupedTimetables'));
    }

    public function create()
    {
        return view('admin.exams.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'timetable_type' => 'required|string|max:255',
            'program' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'course_code' => 'required|string|max:255',
            'faculty' => 'required|string|max:255',
            'year' => 'required|integer|min:1|max:4',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'venue' => 'required|string|max:255',
        ]);

        ExaminationTimetable::create($request->all());

        return redirect()->route('timetables.index')->with('success', 'Timetable created successfully!');
    }

    public function edit($id)
    {
        $timetable = ExaminationTimetable::findOrFail($id);
        return view('admin.exams.edit', compact('timetable'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'timetable_type' => 'required|string|max:255',
            'program' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'course_code' => 'required|string|max:255',
            'faculty' => 'required|string|max:255',
            'year' => 'required|integer|min:1|max:4',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'venue' => 'required|string|max:255',
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
        return view('timetables.import');
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
        $timetables = ExaminationTimetable::query()
            ->when($request->search, fn($query) => $query->where('course_code', 'like', "%{$request->search}%")
                ->orWhere('faculty', 'like', "%{$request->search}%"))
            ->when($request->day, fn($query) => $query->where('exam_date', $request->day))
            ->when($request->faculty, fn($query) => $query->where('faculty', $request->faculty))
            ->when($request->program, fn($query) => $query->where('program', $request->program))
            ->when($request->year, fn($query) => $query->where('year', $request->year))
            ->get();
    
        $groupedTimetables = $timetables->groupBy('program')->map(function ($group) {
            return [
                'timetables' => $group,
                'faculties' => $group->pluck('faculty')->unique()->sort(),
                'timetable_type' => $group->first()->timetable_type, // Fetch first timetable_type
                'semester' => $group->first()->semester, // Fetch first semester
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
        $yearsList = [1, 2, 3, 4];
    
        $pdf = Pdf::loadView('timetables.pdf_all', compact(
            'groupedTimetables', 'week1Days', 'week2Days', 'timeSlots', 'yearsList'
        ))->setPaper('A4', 'landscape');
    
        return $pdf->download('all_timetables.pdf');
    }
}
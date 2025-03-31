<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\WeekNumber;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CalendarImport;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $monthFilter = $request->query('month');
        $dateFilter = $request->query('date');
        $programCategoryFilter = $request->query('program_category');
    
        $query = Calendar::with('weekNumbers');
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('dates', 'like', "%{$search}%")
                  ->orWhere('academic_calendar', 'like', "%{$search}%")
                  ->orWhere('meeting_activities_calendar', 'like', "%{$search}%")
                  ->orWhere('academic_year', 'like', "%{$search}%");
            });
        }
    
        if ($monthFilter) {
            $monthFilter = (int) $monthFilter; // Cast to integer
            $monthName = \Carbon\Carbon::create()->month($monthFilter)->format('F');
            $query->where('month', $monthName);
        }
    
        if ($dateFilter) {
            $query->where('dates', 'like', "%{$dateFilter}%");
        }
    
        if ($programCategoryFilter) {
            $query->whereHas('weekNumbers', function ($q) use ($programCategoryFilter) {
                $q->where('program_category', $programCategoryFilter);
            });
        }
    
        $calendars = $query->get()->groupBy(function ($calendar) {
            return \Carbon\Carbon::parse($calendar->month . ' ' . $calendar->academic_year)->format('F Y');
        });
    
        $months = range(1, 12);
        $programCategories = WeekNumber::select('program_category')->distinct()->pluck('program_category')->sort();
    
        return view('admin.calendars.index', compact('calendars', 'months', 'programCategories', 'monthFilter', 'dateFilter', 'programCategoryFilter'));
    }

    public function create()
    {
        $programCategories = WeekNumber::select('program_category')->distinct()->pluck('program_category')->sort();
        return view('admin.calendars.create', compact('programCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'month'=> 'required|string|max:255',
            'dates' => 'required|string|max:255',
            'academic_calendar' => 'nullable|string|max:255',
            'meeting_activities_calendar' => 'nullable|string|max:255',
            'academic_year' => 'required|string|max:255',
            'week_numbers' => 'array',
            'week_numbers.*.week_number' => 'required_with:week_numbers|string|max:255',
            'week_numbers.*.program_category' => 'required_with:week_numbers|string|max:255',
        ]);

        $calendar = Calendar::create($request->only(['month', 'dates', 'academic_calendar', 'meeting_activities_calendar', 'academic_year']));

        if ($request->has('week_numbers')) {
            foreach ($request->input('week_numbers') as $weekNumberData) {
                $calendar->weekNumbers()->create([
                    'week_number' => $weekNumberData['week_number'],
                    'program_category' => $weekNumberData['program_category'],
                ]);
            }
        }

        return redirect()->route('admin.calendars.index')->with('success', 'Calendar created successfully.');
    }

    public function edit($id)
    {
        $calendar = Calendar::with('weekNumbers')->findOrFail($id);
        $programCategories = WeekNumber::select('program_category')->distinct()->pluck('program_category')->sort();
        return view('admin.calendars.edit', compact('calendar', 'programCategories'));
    }

    public function update(Request $request, $id)
    {
        $calendar = Calendar::findOrFail($id);

        $request->validate([
            'month'=> 'required|string|max:255',
            'dates' => 'required|string|max:255',
            'academic_calendar' => 'nullable|string|max:255',
            'meeting_activities_calendar' => 'nullable|string|max:255',
            'academic_year' => 'required|string|max:255',
            'week_numbers' => 'array',
            'week_numbers.*.week_number' => 'required_with:week_numbers|string|max:255',
            'week_numbers.*.program_category' => 'required_with:week_numbers|string|max:255',
        ]);

        $calendar->update($request->only(['month', 'dates', 'academic_calendar', 'meeting_activities_calendar', 'academic_year']));

        // Sync week numbers
        $calendar->weekNumbers()->delete(); // Remove old week numbers
        if ($request->has('week_numbers')) {
            foreach ($request->input('week_numbers') as $weekNumberData) {
                $calendar->weekNumbers()->create([
                    'week_number' => $weekNumberData['week_number'],
                    'program_category' => $weekNumberData['program_category'],
                ]);
            }
        }

        return redirect()->route('admin.calendars.index')->with('success', 'Calendar updated successfully.');
    }

    public function destroy($id)
    {
        $calendar = Calendar::findOrFail($id);
        $calendar->weekNumbers()->delete(); // Delete related week numbers
        $calendar->delete();

        return redirect()->route('admin.calendars.index')->with('success', 'Calendar deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls'
        ]);

        Excel::import(new CalendarImport, $request->file('file'));

        return redirect()->back()->with('success', 'Calendar imported successfully.');
    }
}
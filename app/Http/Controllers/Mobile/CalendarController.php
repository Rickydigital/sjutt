<?php


// app/Http/Controllers/CalendarController.php
namespace App\Http\Controllers\Mobile;



use App\Http\Controllers\Controller;
use App\Models\Calendar;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        $calendars = Calendar::with('weekNumbers')->get();
        
        $formattedCalendars = $calendars->map(function ($calendar) {
            $weekNumbers = [];
            foreach ($calendar->weekNumbers as $weekNumber) {
                $weekNumbers[$weekNumber->program_category] = $weekNumber->week_number;
            }
            
            return [
                'id' => $calendar->id,
                'month' => $calendar->month,
                'dates' => $calendar->dates,
                'academic_calendar' => $calendar->academic_calendar,
                'meeting_activities_calendar' => $calendar->meeting_activities_calendar,
                'academic_year' => $calendar->academic_year,
                'week_numbers' => $weekNumbers,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedCalendars,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CalendarController extends Controller
{
    /**
     * Retrieve all calendars with their week numbers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Fetch all calendars with their week numbers
            $calendars = CalendarEvent::with('programs')->get();

            if ($calendars->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No calendars found.',
                ], 200);
            }

            // // Format the calendars
            // $formattedCalendars = $calendars->map(function ($calendar) {
            //     $weekNumbers = [];
            //     if ($calendar->weekNumbers) {
            //         foreach ($calendar->weekNumbers as $weekNumber) {
            //             if (isset($weekNumber->program_category) && isset($weekNumber->week_number)) {
            //                 $weekNumbers[$weekNumber->program_category] = $weekNumber->week_number;
            //             }
            //         }
            //     }

            //     return [
            //         'id' => $calendar->id,
            //         'month' => $calendar->month,
            //         'dates' => $calendar->dates,
            //         'academic_calendar' => $calendar->academic_calendar,
            //         'meeting_activities_calendar' => $calendar->meeting_activities_calendar,
            //         'academic_year' => $calendar->academic_year,
            //         'week_numbers' => $weekNumbers,
            //     ];
            // })->all();

            return response()->json([
                'success' => true,
                'data' => $calendars,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Calendar data not found.',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching calendars.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
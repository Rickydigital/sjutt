<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AlmanacSetup;

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
            $setup = AlmanacSetup::getCurrent();

            if (!$setup) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No active almanac found.',
                ], 200);
            }

            $events = $setup->events()
                ->with('programGroups.programs')
                ->orderBy('start_date')
                ->orderBy('id')
                ->get();

            // Keep the legacy mobile calendar keys so the app does not need a
            // structural change while the source of truth moves to Almanac.
            $calendars = $events->map(function ($event) {
                $programs = $event->applies_to_all
                    ? collect()
                    : $event->programGroups
                        ->flatMap(fn ($group) => $group->programs)
                        ->unique('id')
                        ->values()
                        ->map(fn ($program) => [
                            'id' => $program->id,
                            'program' => $program->name,
                            'program_id' => $program->id,
                            'custom_week_number' => null,
                        ]);

                return [
                    'id' => $event->id,
                    'calendar_setup_id' => $event->almanac_setup_id,
                    'event_date' => $event->start_date?->toDateString(),
                    'category' => $event->category,
                    'event_description' => $event->title,
                    'week_number' => null,
                    'programs' => $programs,
                    // Extra almanac details are additive and do not break the
                    // existing mobile response structure.
                    'end_date' => $event->end_date?->toDateString(),
                    'description' => $event->description,
                    'event_column' => $event->event_column,
                    'applies_to_all' => $event->applies_to_all,
                    'is_no_classes' => $event->is_no_classes,
                    'is_tentative' => $event->is_tentative,
                    'background_color' => $event->background_color,
                    'text_color' => $event->text_color,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                ];
            });

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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching calendars.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

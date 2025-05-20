<?php
namespace App\Http\Controllers;

use App\Models\CalendarSetup;
use App\Models\CalendarEvent;
use App\Models\CalendarEventProgram;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function index()
    {
        $setup = CalendarSetup::first();
        $calendarData = [];

        if ($setup) {
            try {
                $calendarData = $this->generateCalendarData($setup);
            } catch (\Exception $e) {
                Log::error('Failed to generate calendar data: ' . $e->getMessage(), [
                    'exception' => $e,
                    'setup' => $setup->toArray(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return redirect()->back()->with('error', 'Failed to load calendar. Please try again later.');
            }
        }

        return view('calendar.index', compact('setup', 'calendarData'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required_if:event_date,null|date',
                'end_date' => 'required_if:event_date,null|date|after:start_date',
                'event_date' => 'nullable|date',
                'category' => 'nullable|string|in:Academic Calendar,Meeting/Activities Calendar',
                'event_description' => 'nullable|string|max:255',
                'custom_week_number' => 'nullable|integer|min:1',
                'programs' => 'nullable|array',
                'programs.*' => 'in:Degree Health,Degree Non-Health,Non-Degree Non-Health,Non-Degree Health,Masters,All Programs',
            ]);

            DB::beginTransaction();

            if ($request->has('start_date') && !$request->has('event_date')) {
                $setup = CalendarSetup::first();
                if ($setup) {
                    return response()->json(['error' => 'Setup already exists. Use update to modify.'], 400);
                }
                CalendarSetup::create([
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ]);
                DB::commit();
                return response()->json(['message' => 'Setup created successfully']);
            }

            $setup = CalendarSetup::first();
            if (!$setup) {
                throw new \Exception('No calendar setup found');
            }

            $event = CalendarEvent::create([
                'calendar_setup_id' => $setup->id,
                'event_date' => $request->event_date,
                'category' => $request->category,
                'event_description' => $request->event_description,
                'week_number' => $request->event_date ? Carbon::parse($request->event_date)->weekOfYear : null,
            ]);

            if ($request->custom_week_number && $request->programs) {
                $programs = $request->programs;
                if (in_array('All Programs', $programs)) {
                    $programs = [
                        'Degree Health',
                        'Degree Non-Health',
                        'Non-Degree Non-Health',
                        'Non-Degree Health',
                        'Masters',
                    ];
                }
                foreach ($programs as $program) {
                    if ($program !== 'All Programs') {
                        CalendarEventProgram::create([
                            'calendar_event_id' => $event->id,
                            'program' => $program,
                            'custom_week_number' => $request->custom_week_number,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Event created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store event: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to create event: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'event_date' => 'nullable|date',
                'category' => 'nullable|string|in:Academic Calendar,Meeting/Activities Calendar',
                'event_description' => 'nullable|string|max:255',
                'custom_week_number' => 'nullable|integer|min:1',
                'programs' => 'nullable|array',
                'programs.*' => 'in:Degree Health,Degree Non-Health,Non-Degree Non-Health,Non-Degree Health,Masters,All Programs',
            ]);

            DB::beginTransaction();

            if ($request->has('start_date')) {
                $setup = CalendarSetup::findOrFail($id);
                $setup->update([
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ]);
                DB::commit();
                return response()->json(['message' => 'Setup updated successfully']);
            }

            $event = CalendarEvent::findOrFail($id);
            $event->update([
                'event_date' => $request->event_date,
                'category' => $request->category,
                'event_description' => $request->event_description,
                'week_number' => $request->event_date ? Carbon::parse($request->event_date)->weekOfYear : null,
            ]);

            // Delete existing program assignments and recreate
            $event->programs()->delete();
            if ($request->custom_week_number && $request->programs) {
                $programs = $request->programs;
                if (in_array('All Programs', $programs)) {
                    $programs = [
                        'Degree Health',
                        'Degree Non-Health',
                        'Non-Degree Non-Health',
                        'Non-Degree Health',
                        'Masters',
                    ];
                }
                foreach ($programs as $program) {
                    if ($program !== 'All Programs') {
                        CalendarEventProgram::create([
                            'calendar_event_id' => $event->id,
                            'program' => $program,
                            'custom_week_number' => $request->custom_week_number,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Event updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update event: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to update event: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $event = CalendarEvent::with('programs')->findOrFail($id);
            return response()->json([
                'id' => $event->id,
                'event_date' => $event->event_date,
                'category' => $event->category,
                'event_description' => $event->event_description,
                'custom_week_number' => $event->programs->first()->custom_week_number ?? null,
                'programs' => $event->programs->pluck('program')->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch event: ' . $e->getMessage(), [
                'event_id' => $id,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch event'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $event = CalendarEvent::findOrFail($id);
            $event->delete();
            return response()->json(['message' => 'Event deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete event: ' . $e->getMessage(), [
                'event_id' => $id,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to delete event'], 500);
        }
    }

    public function export()
    {
        try {
            // Log start of export process
            Log::info('Starting PDF export process', [
                'timestamp' => now()->toDateTimeString(),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
                'php_version' => PHP_VERSION,
                
            ]);

            // Fetch calendar setup
            $setup = CalendarSetup::first();
            if (!$setup) {
                Log::warning('No calendar setup found', [
                    'setup' => null,
                ]);
                return redirect()->back()->with('error', 'No calendar setup found');
            }

            // Log setup details
            Log::info('Calendar setup retrieved', [
                'setup_id' => $setup->id,
                'start_date' => $setup->start_date,
                'end_date' => $setup->end_date,
            ]);

            // Generate calendar data
            Log::info('Generating calendar data');
            $calendarData = $this->generateCalendarData($setup);
            Log::info('Calendar data generated', [
                'month_count' => count($calendarData),
                'total_days' => array_sum(array_map(fn($month) => count($month['days']), $calendarData)),
            ]);

            // Configure dompdf options as an array
            $options = [
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true, // Enable if external images/CSS are needed
                'defaultFont' => 'Helvetica',
                'dpi' => 96,
                'isPhpEnabled' => true, // Required for Blade/Twig rendering
            ];
            Log::info('Dompdf options configured', [
                'options' => $options,
            ]);

            // Load view for PDF
            Log::info('Loading view for PDF rendering');
            $pdf = Pdf::loadView('calendar.pdf', compact('calendarData'));
            Log::info('View loaded successfully');

            // Apply options using setOptions
            $pdf->setOptions($options);
            Log::info('PDF options applied');

            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');
            Log::info('PDF paper size set to A4 portrait');

            // Attempt to render PDF
            Log::info('Rendering PDF');
            $pdf->render();
            Log::info('PDF rendered successfully', [
                'output_size' => strlen($pdf->output()) / 1024 / 1024 . ' MB',
            ]);

            // Download PDF
            Log::info('Initiating PDF download');
            return $pdf->download('calendar.pdf');
        } catch (\Exception $e) {
            // Enhanced error logging
            Log::error('Failed to export PDF: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
                'calendar_data_summary' => isset($calendarData) ? [
                    'month_count' => count($calendarData),
                    'total_days' => array_sum(array_map(fn($month) => count($month['days']), $calendarData)),
                ] : 'not generated',
                'setup' => isset($setup) ? $setup->toArray() : 'not retrieved',
                'view' => 'calendar.pdf',
            ]);
            return redirect()->back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    private function generateCalendarData($setup)
    {
        $startDate = Carbon::parse($setup->start_date)->startOfDay();
        $endDate = Carbon::parse($setup->end_date)->endOfDay();
        $events = CalendarEvent::with('programs')
            ->whereBetween('event_date', [$startDate, $endDate])
            ->get();
        $calendarData = [];
        $currentMonth = null;
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $month = $currentDate->format('F Y');
            $weekNumber = $currentDate->weekOfYear;

            if ($month !== $currentMonth) {
                $currentMonth = $month;
                $calendarData[] = ['month' => $month, 'days' => [], 'weekNumber' => $weekNumber];
            }

            $dayEvents = $events->where('event_date', $currentDate->toDateString());
            $dayData = [
                'date' => $currentDate->toDateString(),
                'dayName' => $currentDate->format('l'),
                'dayNumber' => $currentDate->day,
                'weekNumber' => $weekNumber,
                'isWeekEnd' => in_array($currentDate->format('l'), ['Saturday', 'Sunday']),
                'isMonthEnd' => $currentDate->isLastOfMonth(),
                'events' => [
                    'Degree Health' => '',
                    'Degree Non-Health' => '',
                    'Non-Degree Non-Health' => '',
                    'Non-Degree Health' => '',
                    'Masters' => '',
                    'Academic Calendar' => $dayEvents->where('category', 'Academic Calendar')->first()?->event_description ?? '',
                    'Meeting/Activities Calendar' => $dayEvents->where('category', 'Meeting/Activities Calendar')->first()?->event_description ?? '',
                ],
                'eventIds' => [
                    'Academic Calendar' => $dayEvents->where('category', 'Academic Calendar')->first()?->id ?? null,
                    'Meeting/Activities Calendar' => $dayEvents->where('category', 'Meeting/Activities Calendar')->first()?->id ?? null,
                ],
            ];

            // Assign custom week numbers for programs
            foreach ($dayEvents as $event) {
                foreach ($event->programs as $program) {
                    if (in_array($program->program, [
                        'Degree Health',
                        'Degree Non-Health',
                        'Non-Degree Non-Health',
                        'Non-Degree Health',
                        'Masters',
                    ])) {
                        $dayData['events'][$program->program] = 'Week ' . $program->custom_week_number;
                    }
                }
            }

            $calendarData[array_key_last($calendarData)]['days'][] = $dayData;
            $currentDate->addDay();
        }

        return $calendarData;
    }
}
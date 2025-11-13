<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BuildingsVenuesImport;
use App\Exports\VenuesExport;
use App\Models\TimetableSemester;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VenueController extends Controller
{
    protected $venueTypes = [
        'lecture_theatre',
        'seminar_room',
        'computer_lab',
        'physics_lab',
        'chemistry_lab',
        'medical_lab',
        'nursing_demo',
        'pharmacy_lab',
        'other'
    ];

    public function index()
    {
        $venues = Venue::with('building')->paginate(10);
        return view('venues.index', compact('venues'));
    }

    public function create()
    {
        $buildings = Building::all();
        $venueTypes = $this->venueTypes;
        if ($buildings->isEmpty()) {
            Log::warning('No buildings found when accessing venue create page.');
        }
        return view('venues.create', compact('buildings', 'venueTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:venues',
            'longform' => 'required|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'building_id' => 'nullable|exists:buildings,id',
            'capacity' => 'required|integer|min:1',
            'type' => 'required|in:' . implode(',', $this->venueTypes),
        ]);

        $data = $request->only([
            'name',
            'longform',
            'lat',
            'lng',
            'building_id',
            'capacity',
            'type',
        ]);

        $data['building_id'] = isset($data['building_id']) ? (int) $data['building_id'] : null;

        Log::info('Storing venue with raw request: ', $request->all());
        Log::info('Storing venue with filtered data: ', $data);

        try {
            $venue = Venue::create($data);
            if (!$venue->building_id) {
                Log::info('Venue created with null building_id.', ['venue_id' => $venue->id, 'data' => $data]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create venue: ' . $e->getMessage(), ['data' => $data]);
            return redirect()->back()->with('error', 'Failed to create venue: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('venues.index')->with('success', 'Venue added successfully.');
    }

    public function availability(Request $request): JsonResponse
{
    $semesterId = $request->query('semester_id')
        ?? TimetableSemester::getFirstSemester()?->semester_id;

    $venues = Venue::with('building')
        ->withAvailability($semesterId)
        ->get()
        ->map(function ($venue) {
            $venue->free = (bool) $venue->free;
            $venue->booked_slots = json_decode($venue->booked_slots, true);
            return $venue;
        });

    return response()->json($venues);
}

    public function show(Venue $venue)
    {
        $venue->load('building');
        return view('venues.show', compact('venue'));
    }

    public function edit(Venue $venue)
    {
        $buildings = Building::all();
        $venueTypes = $this->venueTypes;
        if ($buildings->isEmpty()) {
            Log::warning('No buildings found when accessing venue edit page.');
        }
        return view('venues.edit', compact('venue', 'buildings', 'venueTypes'));
    }


  public function sessionsIndex(Request $request)
{
    $semesterId = TimetableSemester::getFirstSemester()?->semester_id;

    if (!$semesterId) {
        return view('venues.sessions', ['venues' => collect(), 'search' => '']);
    }

    $search = $request->query('search', '');

    $venues = Venue::with('building')
        ->withCount(['timetables as total_sessions' => fn($q) => $q->where('semester_id', $semesterId)])
        ->when($search, function ($q) use ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                   ->orWhere('longform', 'like', "%{$search}%");
            });
        })
        ->orderByDesc('total_sessions')
        ->paginate(15)
        ->withQueryString(); // preserves search

    return view('venues.sessions', compact('venues', 'semesterId', 'search'));
}

public function sessionsShow(Venue $venue)
{
    $semesterId = TimetableSemester::getFirstSemester()?->semester_id;

    $slots = DB::table('timetables')
        ->where('venue_id', $venue->id)
        ->where('semester_id', $semesterId)
        ->leftJoin('faculties', 'timetables.faculty_id', '=', 'faculties.id')
        ->select(
            'timetables.day',
            DB::raw('TIME_FORMAT(timetables.time_start, "%H:%i") as start'),
            DB::raw('TIME_FORMAT(timetables.time_end, "%H:%i") as end'),
            'timetables.course_code',
            'timetables.activity',
            'timetables.lecturer_id',
            'timetables.group_selection',
            'faculties.name as faculty_name'
        )
        ->orderBy('day')
        ->orderBy('time_start')
        ->get()
        ->groupBy(fn($i) => "{$i->day}|{$i->start}|{$i->end}")
        ->map(function ($group) {
            $first = $group->first();
            return [
                'day' => $first->day,
                'start' => $first->start,
                'end' => $first->end,
                'courses' => $group->pluck('course_code')->unique()->values()->toArray(),
                'lecturers' => $group->pluck('lecturer_id')->filter()->unique()->values()->toArray(),
                'groups' => $group->map(fn($i) => $i->group_selection === 'All Groups' ? 'All Groups' : $i->group_selection)
                                 ->unique()
                                 ->implode(', '),
                'activity' => $group->pluck('activity')->filter()->unique()->implode(' / '),
                'count' => $group->count(),
                'faculty' => $group->pluck('faculty_name')->filter()->unique()->implode(' / '),
            ];
        })
        ->values();

    $venue->load(['building']);

    return view('venues.sessions-show', compact('venue', 'slots', 'semesterId'));
}

public function sessionsPdf(Venue $venue)
{
    $semester = TimetableSemester::getFirstSemester();

    $slots = DB::table('timetables')
        ->where('venue_id', $venue->id)
        ->where('semester_id', $semester->semester_id)
        ->leftJoin('faculties', 'timetables.faculty_id', '=', 'faculties.id')
        ->leftJoin('users', 'timetables.lecturer_id', '=', 'users.id')
        ->select(
            'timetables.day',
            DB::raw('TIME_FORMAT(timetables.time_start, "%H:%i") as start'),
            DB::raw('TIME_FORMAT(timetables.time_end, "%H:%i") as end'),
            'timetables.course_code',
            'timetables.activity',
            'timetables.group_selection',
            'faculties.name as faculty_name',
            'users.name as lecturer_name'
        )
        ->orderBy('day')
        ->orderBy('time_start')
        ->get()
        ->groupBy(fn($i) => "{$i->day}|{$i->start}|{$i->end}")
        ->map(function ($group) {
            $first = $group->first();
            return [
                'day' => $first->day,
                'start' => $first->start,
                'end' => $first->end,
                'courses' => $group->pluck('course_code')->unique()->values()->toArray(),
                'lecturers' => $group->pluck('lecturer_name')->filter()->unique()->implode(', '),
                'groups' => $group->map(fn($i) => $i->group_selection === 'All Groups' ? 'All Groups' : $i->group_selection)
                                 ->unique()
                                 ->implode(', '),
                'activity' => $group->pluck('activity')->filter()->unique()->implode(' / '),
                'faculty' => $group->pluck('faculty_name')->filter()->unique()->implode(' / '),
            ];
        })
        ->values();

    $venue->load('building');

    // In controller, before loadView():
    Log::info('Logo path: ' . public_path('images/logo.png'));
    Log::info('File exists: ' . (file_exists(public_path('images/logo.png')) ? 'YES' : 'NO'));

    $pdf = Pdf::loadView('venues.pdf.sessions', compact('venue', 'slots', 'semester'))
              ->setPaper('a4', 'portrait')
              ->setOptions([
                  'isRemoteEnabled' => true,
                  'defaultFont' => 'DejaVu Sans',
                  'dpi' => 150,
                  'isHtml5ParserEnabled' => true,
                  'margin_top'    => 0,
                  'margin_right'  => 0,
                  'margin_bottom' => 0,
                  'margin_left'   => 0,
              ]);

    $filename = "venue-usage-{$venue->name}-" . now()->format('Y-m-d') . ".pdf";
    return $pdf->download($filename);
}

    public function update(Request $request, Venue $venue)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:venues,name,' . $venue->id,
            'longform' => 'required|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'building_id' => 'nullable|exists:buildings,id',
            'capacity' => 'required|integer|min:1',
            'type' => 'required|in:' . implode(',', $this->venueTypes),
        ]);

        $data = $request->only([
            'name',
            'longform',
            'lat',
            'lng',
            'building_id',
            'capacity',
            'type',
        ]);

        $data['building_id'] = isset($data['building_id']) ? (int) $data['building_id'] : null;

        Log::info('Updating venue with raw request: ', $request->all());
        Log::info('Updating venue with filtered data: ', $data);

        try {
            $venue->update($data);
            if (!$venue->building_id) {
                Log::info('Venue updated with null building_id.', ['venue_id' => $venue->id, 'data' => $data]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update venue: ' . $e->getMessage(), ['data' => $data]);
            return redirect()->back()->with('error', 'Failed to update venue: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('venues.index')->with('success', 'Venue updated successfully.');
    }

    public function destroy(Venue $venue)
    {
        try {
            $venue->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete venue: ' . $e->getMessage(), ['venue_id' => $venue->id]);
            return redirect()->back()->with('error', 'Failed to delete venue: ' . $e->getMessage());
        }
        return redirect()->route('venues.index')->with('success', 'Venue deleted successfully.');
    }

    public function apiIndex()
    {
        $venues = Venue::with('building')->get();
        return response()->json($venues);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new BuildingsVenuesImport, $request->file('file'));
            return redirect()->route('venues.index')->with('success', 'Buildings and venues imported successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to import buildings and venues: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to import buildings and venues: ' . $e->getMessage());
        }
    }

    public function exportVenues()
    {
        $filename = 'sjutvenues' . rand(1000, 9999) . '.xlsx';
        return Excel::download(new VenuesExport, $filename);
    }


public function summary(Request $request)
{
    $timetableSemester = TimetableSemester::with('semester')->first();
    if (!$timetableSemester) {
        return view('venues.summary', [
            'venues' => collect(),
            'hours' => [], 'days' => [], 'grid' => [],
            'semester' => null, 'academicYear' => null
        ]);
    }

    $semesterId = $timetableSemester->semester_id;
    $semester = $timetableSemester->semester;
    $academicYear = $timetableSemester->academic_year;

    // 1-HOUR SLOTS: 8:00 to 19:00
    $hours = [];
    for ($h = 8; $h <= 20; $h++) {
        $hours[] = sprintf("%02d:00", $h);
    }

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $venues = Venue::with('building')->orderBy('name')->get();

    // Initialize grid
    $grid = [];
    foreach ($days as $day) {
        foreach ($hours as $hour) {
            $grid[$day][$hour] = [];
        }
    }

    // Fetch bookings
    $entries = DB::table('timetables')
        ->where('semester_id', $semesterId)
        ->leftJoin('faculties', 'timetables.faculty_id', '=', 'faculties.id')
        ->select(
            'timetables.venue_id',
            'timetables.day',
            'timetables.time_start',
            'timetables.time_end',
            'timetables.course_code',
            'timetables.activity',
            'faculties.name as faculty_name'
        )
        ->get();

    Log::info("Found {$entries->count()} timetable entries");

    foreach ($entries as $entry) {
        $day = ucfirst(strtolower($entry->day));
        if (!in_array($day, $days)) continue;

        $startHour = date('H:00', strtotime($entry->time_start));
        $endHour = date('H:00', strtotime($entry->time_end));
        $duration = (strtotime($entry->time_end) - strtotime($entry->time_start)) / 3600;

        $display = $entry->course_code;
        if ($entry->faculty_name) $display .= " | {$entry->faculty_name}";
        if ($entry->activity) $display .= " ({$entry->activity})";

        // Fill all hours this class covers
        $current = $startHour;
        while (strtotime($current) < strtotime($endHour)) {
            if (isset($grid[$day][$current])) {  // ← FIXED: $current, NOT $DMDcurrent
                $grid[$day][$current][$entry->venue_id] = [
                    'content' => $display,
                    'duration' => $duration,
                    'isFirst' => ($current === $startHour),
                    'rowspan' => $duration
                ];
            }
            $current = date('H:00', strtotime($current . ' +1 hour'));
        }
    }

    return view('venues.summary', compact(
        'venues', 'hours', 'days', 'grid', 'semester', 'academicYear'
    ));
}

public function summaryPdf()
{
    $timetableSemester = TimetableSemester::with('semester')->first();
    if (!$timetableSemester) {
        abort(404, 'No active semester');
    }

    $semesterId = $timetableSemester->semester_id;
    $semester = $timetableSemester->semester;
    $academicYear = $timetableSemester->academic_year;

    // 8:00 → 20:00
    $hours = [];
    for ($h = 8; $h <= 20; $h++) {
        $hours[] = sprintf("%02d:00", $h);
    }

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $venues = Venue::with('building')->orderBy('name')->get();

    $grid = [];
    foreach ($days as $day) {
        foreach ($hours as $hour) {
            $grid[$day][$hour] = [];
        }
    }

    $entries = DB::table('timetables')
        ->where('semester_id', $semesterId)
        ->leftJoin('faculties', 'timetables.faculty_id', '=', 'faculties.id')
        ->select(
            'timetables.venue_id', 'timetables.day',
            'timetables.time_start', 'timetables.time_end',
            'timetables.course_code', 'timetables.activity',
            'faculties.name as faculty_name'
        )
        ->get();

    foreach ($entries as $entry) {
        $day = ucfirst(strtolower($entry->day));
        if (!in_array($day, $days)) continue;

        $startHour = date('H:00', strtotime($entry->time_start));
        $endHour = date('H:00', strtotime($entry->time_end));
        $duration = (strtotime($entry->time_end) - strtotime($entry->time_start)) / 3600;

        $display = $entry->course_code;
        if ($entry->faculty_name) $display .= " | {$entry->faculty_name}";
        if ($entry->activity) $display .= " ({$entry->activity})";

        $current = $startHour;
        while (strtotime($current) < strtotime($endHour)) {
            if (isset($grid[$day][$current])) {
                $grid[$day][$current][$entry->venue_id] = [
                    'content' => $display,
                    'duration' => $duration,
                    'isFirst' => ($current === $startHour),
                    'rowspan' => $duration
                ];
            }
            $current = date('H:00', strtotime($current . ' +1 hour'));
        }
    }

        $pdf = Pdf::loadView('venues.pdf.summary', compact(
        'venues', 'hours', 'days', 'grid', 'semester', 'academicYear'
    ))
    ->setPaper('a4', 'landscape')
    ->setOptions([
        'isRemoteEnabled' => true,
        'defaultFont' => 'DejaVu Sans',
        'dpi' => 150,
    ]);

    // SAFE FILENAME — NO MORE ERRORS!
    $clean = function ($str) {
        return preg_replace('/[\/\\\:*?"<>|]/', '-', trim($str));
    };

    $safeSemester = $clean($semester->name ?? 'Semester');
    $safeYear = $clean($academicYear ?? 'Year');

    $filename = "Venue-Summary-{$safeSemester}-{$safeYear}-" . now()->format('Y-m-d') . ".pdf";

    return $pdf->download($filename);
}


}
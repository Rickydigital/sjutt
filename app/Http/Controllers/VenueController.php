<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BuildingsVenuesImport;
use App\Exports\VenuesExport;

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
            'building_id' => 'required|exists:buildings,id',
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

        $data['building_id'] = (int) $data['building_id'];

        Log::info('Storing venue with raw request: ', $request->all());
        Log::info('Storing venue with filtered data: ', $data);

        try {
            $venue = Venue::create($data);
            if (!$venue->building_id) {
                Log::error('Venue created but building_id not stored.', ['venue_id' => $venue->id, 'data' => $data]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create venue: ' . $e->getMessage(), ['data' => $data]);
            return redirect()->back()->with('error', 'Failed to create venue: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('venues.index')->with('success', 'Venue added successfully.');
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

    public function update(Request $request, Venue $venue)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:venues,name,' . $venue->id,
            'longform' => 'required|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'building_id' => 'required|exists:buildings,id',
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

        $data['building_id'] = (int) $data['building_id'];

        Log::info('Updating venue with raw request: ', $request->all());
        Log::info('Updating venue with filtered data: ', $data);

        try {
            $venue->update($data);
            if (!$venue->building_id) {
                Log::error('Venue updated but building_id not stored.', ['venue_id' => $venue->id, 'data' => $data]);
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

}
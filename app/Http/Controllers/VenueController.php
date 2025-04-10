<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    // Existing CRUD methods...

    public function index()
    {
        $venues = Venue::paginate(10);
        return view('venues.index', compact('venues'));
    }

    public function create()
    {
        return view('venues.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:venues|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);
        Venue::create($request->all());
        return redirect()->route('venues.index')->with('success', 'Venue added successfully.');
    }

    public function show(Venue $venue)
    {
        return view('venues.show', compact('venue'));
    }

    public function edit(Venue $venue)
    {
        return view('venues.edit', compact('venue'));
    }

    public function update(Request $request, Venue $venue)
    {
        $request->validate([
            'name' => 'required|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);
        $venue->update($request->all());
        return redirect()->route('venues.index')->with('success', 'Venue updated successfully.');
    }

    public function destroy(Venue $venue)
    {
        $venue->delete();
        return redirect()->route('venues.index')->with('success', 'Venue deleted successfully.');
    }

    // New API method
    public function apiIndex()
    {
        $venues = Venue::all();
        return response()->json($venues);
    }
}
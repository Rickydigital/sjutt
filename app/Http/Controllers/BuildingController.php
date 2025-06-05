<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BuildingsImport;
use Illuminate\Support\Facades\Log;

class BuildingController extends Controller
{
    public function index()
    {
        $buildings = Building::withCount('venues')->paginate(10);
        return view('admin.buildings.index', compact('buildings'));
    }

    public function create()
    {
        return view('admin.buildings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:buildings',
            'description' => 'nullable|string',
        ]);

        Building::create($request->only(['name', 'description']));

        return redirect()->route('buildings.index')->with('success', 'Building added successfully.');
    }

    public function show(Building $building)
    {
        $building->load('venues');
        return view('admin.buildings.show', compact('building'));
    }

    public function edit(Building $building)
    {
        return view('admin.buildings.edit', compact('building'));
    }

    public function update(Request $request, Building $building)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:buildings,name,' . $building->id,
            'description' => 'nullable|string',
        ]);

        $building->update($request->only(['name', 'description']));

        return redirect()->route('buildings.index')->with('success', 'Building updated successfully.');
    }

    public function destroy(Building $building)
    {
        if ($building->venues()->exists()) {
            return redirect()->route('buildings.index')->with('error', 'Cannot delete building with associated venues.');
        }

        $building->delete();
        return redirect()->route('buildings.index')->with('success', 'Building deleted successfully.');
    }

    public function apiIndex()
    {
        $buildings = Building::with('venues')->get();
        return response()->json($buildings);
    }

   
}
<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function index() {
        $faculties = Faculty::paginate(10);
        return view('faculties.index', compact('faculties'));
    }

    public function create() {
        return view('faculties.create');
    }

    public function store(Request $request) {
        $request->validate(['name' => 'required|unique:faculties|max:255']);
        Faculty::create($request->all());
        return redirect()->route('faculties.index')->with('success', 'Faculty created successfully.');
    }

    public function show(Faculty $faculty) {
        return view('faculties.show', compact('faculty'));
    }

    public function edit(Faculty $faculty) {
        return view('faculties.edit', compact('faculty'));
    }

    public function update(Request $request, Faculty $faculty) {
        $request->validate(['name' => 'required|max:255']);
        $faculty->update($request->all());
        return redirect()->route('faculties.index')->with('success', 'Faculty updated successfully.');
    }

    public function destroy(Faculty $faculty) {
        $faculty->delete();
        return redirect()->route('faculties.index')->with('success', 'Faculty deleted successfully.');
    }
}

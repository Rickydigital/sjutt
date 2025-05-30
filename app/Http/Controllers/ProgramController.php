<?php

namespace App\Http\Controllers;

use App\Exports\ProgramsExport;
use App\Imports\ProgramsImport;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::with('administrator')->paginate(10);
        return view('admin.programs.index', compact('programs'));
    }

    public function create()
    {
        $administrators = User::whereHas('roles', function ($query) {
            $query->where('name', 'Administrator');
        })->get();
        return view('admin.programs.create', compact('administrators'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50|unique:programs,short_name',
            'total_years' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string',
            'administrator_id' => 'required|exists:users,id',
        ]);

        Program::create($request->all());
        return redirect()->route('programs.index')->with('success', 'Program created successfully.');
    }

    public function edit(Program $program)
    {
        $administrators = User::whereHas('roles', function ($query) {
            $query->where('name', 'Administrator');
        })->get();
        return view('admin.programs.edit', compact('program', 'administrators'));
    }

    public function update(Request $request, Program $program)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50|unique:programs,short_name,' . $program->id,
            'total_years' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string',
            'administrator_id' => 'required|exists:users,id',
        ]);

        $program->update($request->all());
        return redirect()->route('programs.index')->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program)
    {
        $program->delete();
        return redirect()->route('programs.index')->with('success', 'Program deleted successfully.');
    }

    public function export()
    {
        $filename = 'programs_' . now()->format('Ymd') . '_' . rand(1000, 9999) . '.xlsx';
        return Excel::download(new ProgramsExport, $filename);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        Excel::import(new ProgramsImport, $request->file('file'));
        return redirect()->route('programs.index')->with('success', 'Programs imported successfully.');
    }
}
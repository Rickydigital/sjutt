<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use Illuminate\Http\Request;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $feeStructures = FeeStructure::when($search, function ($query, $search) {
            return $query->where('program_type', 'like', "%{$search}%")
                        ->orWhere('program_name', 'like', "%{$search}%");
        })->paginate(10);

        return view('admin.fee_structures.index', compact('feeStructures'));
    }
    public function create()
    {
        return view('admin.fee_structures.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'program_type' => 'required|string',
            'program_name' => 'required|string',
            'first_year' => 'required|numeric',
            'continuing_year' => 'required|numeric',
            'final_year' => 'required|numeric',
        ]);

        FeeStructure::create($request->all());

        return redirect()->route('fee_structures.index');
    }

    public function edit(FeeStructure $feeStructure)
    {
        return view('admin.fee_structures.edit', compact('feeStructure'));
    }

    public function update(Request $request, FeeStructure $feeStructure)
    {
        $request->validate([
            'program_type' => 'required|string',
            'program_name' => 'required|string',
            'first_year' => 'required|numeric',
            'continuing_year' => 'required|numeric',
            'final_year' => 'required|numeric',
        ]);

        $feeStructure->update($request->all());

        return redirect()->route('fee_structures.index');
    }

    public function destroy(FeeStructure $feeStructure)
    {
        $feeStructure->delete();
        return redirect()->route('fee_structures.index');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PositionDefinition;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PositionDefinitionController extends Controller
{
    public function index()
    {
        $definitions = PositionDefinition::orderBy('name')->get();
        return view('elections.positions', compact('definitions'));
    }

    public function create()
    {
        return view('position_definitions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('position_definitions')],
            'name' => ['required', 'string', 'max:255'],
            'default_scope_type' => ['required', Rule::in(['faculty', 'program', 'global'])],
            'max_votes_per_voter' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        PositionDefinition::create($validated);

        return redirect()
            ->route('position-definitions.index')
            ->with('success', 'Position definition created successfully');
    }

    public function edit(PositionDefinition $positionDefinition)
    {
        return view('position_definitions.edit', compact('positionDefinition'));
    }

    public function update(Request $request, PositionDefinition $positionDefinition)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('position_definitions')->ignore($positionDefinition->id)
            ],
            'name' => ['required', 'string', 'max:255'],
            'default_scope_type' => ['required', Rule::in(['faculty', 'program', 'global'])],
            'max_votes_per_voter' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $positionDefinition->update($validated);

        return redirect()
            ->route('position-definitions.index')
            ->with('success', 'Position definition updated');
    }

    public function destroy(PositionDefinition $positionDefinition)
    {
        if ($positionDefinition->electionPositions()->exists()) {
            return back()->withErrors([
                'error' => 'This position is already used in an election'
            ]);
        }

        $positionDefinition->delete();

        return back()->with('success', 'Position definition deleted');
    }
}

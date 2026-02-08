<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionPosition;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\PositionDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ElectionPositionController extends Controller
{
    public function index(Election $election)
    {
        $positions = $election->positions()
            ->with(['definition', 'faculties', 'programs'])
            ->latest()
            ->get();

        $definitions = PositionDefinition::orderBy('name')->get();
        $faculties   = Faculty::orderBy('name')->get();
        $programs    = Program::orderBy('name')->get();


        return view('officer.positions.index', compact(
            'election',
            'positions',
            'definitions',
            'faculties',
            'programs'
        ));
    }

  public function store(Request $request, Election $election)
{
    Log::info('Store method reached', $request->all());

    // ────────────────────────────────────────────────
    // Clean "all" from multi-select arrays BEFORE validation
    // ────────────────────────────────────────────────
    $input = $request->all();

    if (isset($input['faculty_ids']) && is_array($input['faculty_ids'])) {
        $input['faculty_ids'] = array_filter($input['faculty_ids'], function ($id) {
            return $id !== 'all' && is_numeric($id) && $id > 0;
        });
    }

    if (isset($input['program_ids']) && is_array($input['program_ids'])) {
        $input['program_ids'] = array_filter($input['program_ids'], function ($id) {
            return $id !== 'all' && is_numeric($id) && $id > 0;
        });
    }

    // Replace the request data with cleaned version
    $request->replace($input);

    // Now validate safely — "all" is gone
    $validated = $request->validate([
        'position_definition_id' => ['required', 'exists:position_definitions,id'],
        'scope_type'             => ['required', 'in:global,faculty,program'],
        'max_candidates'         => ['nullable', 'integer', 'min:1'],
        'faculty_ids'            => [
            'nullable',
            'array',
            'required_if:scope_type,faculty',
        ],
        'faculty_ids.*'          => ['integer', 'exists:faculties,id'],
        'program_ids'            => [
            'nullable',
            'array',
            'required_if:scope_type,program',
        ],
        'program_ids.*'          => ['integer', 'exists:programs,id'],
    ]);

    Log::info('Validation passed', $validated);

    // Optional: extra safety (already handled by cleaning above)
    if ($validated['scope_type'] === 'program') {
        $validated['faculty_ids'] = [];
    }
    if ($validated['scope_type'] === 'faculty') {
        $validated['program_ids'] = [];
    }
    if ($validated['scope_type'] === 'global') {
        $validated['faculty_ids'] = [];
        $validated['program_ids'] = [];
    }

    $position = ElectionPosition::create([
        'election_id'            => $election->id,
        'position_definition_id' => $validated['position_definition_id'],
        'scope_type'             => $validated['scope_type'],
        'max_candidates'         => $validated['max_candidates'] ?? null,
        'is_enabled'             => true,
    ]);

    Log::info('Position created', ['id' => $position->id]);

    // Use the already cleaned arrays (or fallback to validated)
    $facultyIds = $input['faculty_ids'] ?? $validated['faculty_ids'] ?? [];
    $programIds = $input['program_ids'] ?? $validated['program_ids'] ?? [];

    $position->faculties()->sync($facultyIds);
    $position->programs()->sync($programIds);

    Log::info('Sync completed');

    return redirect()
        ->route('officer.elections.positions.index', $election)
        ->with('success', 'Position added successfully.');
}
    public function update(Request $request, Election $election, ElectionPosition $position)
{
    abort_if($position->election_id !== $election->id, 404);

    Log::info('Update method reached', [
        'position_id' => $position->id,
        'request' => $request->all()
    ]);

    try {
        // ────────────────────────────────────────────────
        // Clean invalid values BEFORE validation (same as store)
        // ────────────────────────────────────────────────
        $input = $request->all();

        if (isset($input['faculty_ids']) && is_array($input['faculty_ids'])) {
            $input['faculty_ids'] = array_filter($input['faculty_ids'], fn($id) => $id !== 'all' && is_numeric($id) && $id > 0);
        }

        if (isset($input['program_ids']) && is_array($input['program_ids'])) {
            $input['program_ids'] = array_filter($input['program_ids'], fn($id) => $id !== 'all' && is_numeric($id) && $id > 0);
        }

        $request->replace($input);

        // ────────────────────────────────────────────────
        // Validate
        // ────────────────────────────────────────────────
        $validated = $request->validate([
            'position_definition_id' => ['required', 'exists:position_definitions,id'],
            'scope_type'             => ['required', 'in:global,faculty,program'],
            'max_candidates'         => ['nullable', 'integer', 'min:1'],
            'faculty_ids'            => [
                'nullable',
                'array',
                'required_if:scope_type,faculty',
            ],
            'faculty_ids.*'          => ['integer', 'exists:faculties,id'],
            'program_ids'            => [
                'nullable',
                'array',
                'required_if:scope_type,program',
            ],
            'program_ids.*'          => ['integer', 'exists:programs,id'],
        ]);

        Log::info('Update validation passed', $validated);

        // Normalize opposite scope fields
        if ($validated['scope_type'] === 'faculty') {
            $validated['program_ids'] = [];
        }

        if ($validated['scope_type'] === 'program') {
            $validated['faculty_ids'] = [];
        }

        if ($validated['scope_type'] === 'global') {
            $validated['faculty_ids'] = [];
            $validated['program_ids'] = [];
        }

        // Update main fields
        $position->update([
            'position_definition_id' => $validated['position_definition_id'],
            'scope_type'             => $validated['scope_type'],
            'max_candidates'         => $validated['max_candidates'] ?? null,
        ]);

        Log::info('Position updated', ['id' => $position->id]);

        // Use cleaned values for sync
        $facultyIds = $input['faculty_ids'] ?? $validated['faculty_ids'] ?? [];
        $programIds = $input['program_ids'] ?? $validated['program_ids'] ?? [];

        $position->faculties()->sync($facultyIds);
        $position->programs()->sync($programIds);

        Log::info('Pivot tables synced successfully');

        return redirect()
            ->route('officer.elections.positions.index', $election)
            ->with('success', 'Position updated successfully.');
    }
    catch (\Illuminate\Validation\ValidationException $e) {
        Log::warning('Update validation failed', $e->errors());
        return back()
            ->withErrors($e->validator)
            ->withInput();
    }
    catch (\Exception $e) {
        Log::error('Update failed', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString()
        ]);

        return back()
            ->with('error', 'Failed to update position. Please try again.')
            ->withInput();
    }
}

    public function destroy(Election $election, ElectionPosition $position)
    {
        abort_if($position->election_id !== $election->id, 404);

        $position->faculties()->detach();
        $position->programs()->detach();
        $position->delete();

        return redirect()
            ->route('officer.elections.positions.index', $election)
            ->with('success', 'Position deleted successfully.');
    }
}

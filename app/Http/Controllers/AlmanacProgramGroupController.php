<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlmanacProgramGroupRequest;
use App\Models\AlmanacProgramGroup;
use App\Models\AlmanacSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AlmanacProgramGroupController extends Controller
{
    public function store(StoreAlmanacProgramGroupRequest $request, AlmanacSetup $setup): RedirectResponse
    {
        DB::transaction(function () use ($request, $setup): void {
            $group = $setup->programGroups()->create([
                ...$request->safe()->except('program_ids'),
                'is_active' => $request->boolean('is_active', true),
            ]);
            $group->programs()->sync($request->input('program_ids', []));
        });

        return back()->with('success', 'Programme group created successfully.');
    }

    public function update(StoreAlmanacProgramGroupRequest $request, AlmanacSetup $setup, AlmanacProgramGroup $group): RedirectResponse
    {
        abort_unless($group->almanac_setup_id === $setup->id, 404);

        DB::transaction(function () use ($request, $group): void {
            $group->update([
                ...$request->safe()->except('program_ids'),
                'is_active' => $request->boolean('is_active'),
            ]);
            $group->programs()->sync($request->input('program_ids', []));
        });

        return back()->with('success', 'Programme group updated successfully.');
    }

    public function destroy(AlmanacSetup $setup, AlmanacProgramGroup $group): RedirectResponse
    {
        abort_unless($group->almanac_setup_id === $setup->id, 404);
        $group->delete();
        return back()->with('success', 'Programme group deleted successfully.');
    }
}

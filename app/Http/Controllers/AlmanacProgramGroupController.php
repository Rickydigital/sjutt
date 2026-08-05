<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlmanacProgramGroupRequest;
use App\Models\AlmanacProgramGroup;
use App\Models\AlmanacSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AlmanacProgramGroupController extends Controller
{
    public function show(AlmanacSetup $setup, AlmanacProgramGroup $group): JsonResponse
    {
        abort_unless((int) $group->almanac_setup_id === (int) $setup->id, 404);
        $group->load('programs:id');

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'level' => $group->level,
            'display_order' => $group->display_order,
            'background_color' => $group->background_color,
            'text_color' => $group->text_color,
            'is_active' => (bool) $group->is_active,
            'program_ids' => $group->programs->pluck('id')->values(),
        ]);
    }

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
        abort_unless((int) $group->almanac_setup_id === (int) $setup->id, 404);

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
        abort_unless((int) $group->almanac_setup_id === (int) $setup->id, 404);
        $group->delete();
        return back()->with('success', 'Programme group deleted successfully.');
    }
}

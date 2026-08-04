<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlmanacSetupRequest;
use App\Models\AlmanacSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AlmanacSetupController extends Controller
{
    public function store(StoreAlmanacSetupRequest $request): RedirectResponse
    {
        $setup = AlmanacSetup::create([
            ...$request->validated(),
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('almanac.index', ['setup_id' => $setup->id])
            ->with('success', 'Almanac setup created successfully.');
    }

    public function update(StoreAlmanacSetupRequest $request, AlmanacSetup $setup): RedirectResponse
    {
        $setup->update($request->validated());

        return back()->with('success', 'Almanac setup updated successfully.');
    }

    public function activate(AlmanacSetup $setup): RedirectResponse
    {
        $setup->activate();
        return back()->with('success', 'Almanac activated successfully.');
    }

    public function archive(AlmanacSetup $setup): RedirectResponse
    {
        $setup->update(['status' => 'archived']);
        return back()->with('success', 'Almanac archived successfully.');
    }

    public function destroy(AlmanacSetup $setup): RedirectResponse
    {
        abort_if($setup->status === 'active', 422, 'Archive the active Almanac before deleting it.');

        DB::transaction(fn () => $setup->delete());
        return redirect()->route('almanac.index')->with('success', 'Almanac deleted successfully.');
    }
}

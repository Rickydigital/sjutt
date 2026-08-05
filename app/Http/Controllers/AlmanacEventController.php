<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlmanacEventRequest;
use App\Models\AlmanacEvent;
use App\Models\AlmanacSetup;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AlmanacEventController extends Controller
{
    public function show(AlmanacSetup $setup, AlmanacEvent $event): JsonResponse
    {
        abort_unless((int) $event->almanac_setup_id === (int) $setup->id, 404);
        $event->load('programGroups:id');

        return response()->json([
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'start_date' => $event->start_date?->format('Y-m-d'),
            'end_date' => $event->end_date?->format('Y-m-d'),
            'event_column' => $event->event_column,
            'category' => $event->category,
            'applies_to_all' => (bool) $event->applies_to_all,
            'program_group_ids' => $event->programGroups->pluck('id')->values(),
            'is_no_classes' => (bool) $event->is_no_classes,
            'is_tentative' => (bool) $event->is_tentative,
            'background_color' => $event->background_color,
            'text_color' => $event->text_color,
        ]);
    }

    public function store(StoreAlmanacEventRequest $request, AlmanacSetup $setup): RedirectResponse
    {
        $this->ensureDatesInsideSetup($setup, $request->start_date, $request->end_date ?: $request->start_date);

        DB::transaction(function () use ($request, $setup): void {
            $event = $setup->events()->create([
                ...$request->safe()->except('program_group_ids'),
                'end_date' => $request->filled('end_date') ? $request->end_date : null,
                'applies_to_all' => $request->boolean('applies_to_all'),
                'is_no_classes' => $request->boolean('is_no_classes'),
                'is_tentative' => $request->boolean('is_tentative'),
                'created_by' => auth()->id(),
            ]);

            $event->programGroups()->sync(
                $request->boolean('applies_to_all') ? [] : $request->input('program_group_ids', [])
            );
        });

        return back()->with('success', 'Almanac event created successfully.');
    }

    public function update(StoreAlmanacEventRequest $request, AlmanacSetup $setup, AlmanacEvent $event): RedirectResponse
    {
        abort_unless((int) $event->almanac_setup_id === (int) $setup->id, 404);
        $this->ensureDatesInsideSetup($setup, $request->start_date, $request->end_date ?: $request->start_date);

        DB::transaction(function () use ($request, $event): void {
            $event->update([
                ...$request->safe()->except('program_group_ids'),
                'end_date' => $request->filled('end_date') ? $request->end_date : null,
                'applies_to_all' => $request->boolean('applies_to_all'),
                'is_no_classes' => $request->boolean('is_no_classes'),
                'is_tentative' => $request->boolean('is_tentative'),
            ]);

            $event->programGroups()->sync(
                $request->boolean('applies_to_all') ? [] : $request->input('program_group_ids', [])
            );
        });

        return back()->with('success', 'Almanac event updated successfully.');
    }

    public function destroy(AlmanacSetup $setup, AlmanacEvent $event): RedirectResponse
    {
        abort_unless((int) $event->almanac_setup_id === (int) $setup->id, 404);
        $event->delete();
        return back()->with('success', 'Almanac event deleted successfully.');
    }

    private function ensureDatesInsideSetup(AlmanacSetup $setup, string $start, string $end): void
    {
        if (Carbon::parse($start)->lt($setup->start_date) || Carbon::parse($end)->gt($setup->end_date)) {
            throw ValidationException::withMessages([
                'start_date' => 'The event must remain inside the Almanac date range.',
            ]);
        }
    }
}

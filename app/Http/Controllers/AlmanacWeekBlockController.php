<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlmanacWeekBlockRequest;
use App\Models\AlmanacSetup;
use App\Models\AlmanacWeekBlock;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AlmanacWeekBlockController extends Controller
{
    public function show(AlmanacSetup $setup, AlmanacWeekBlock $weekBlock): JsonResponse
    {
        abort_unless((int) $weekBlock->almanac_setup_id === (int) $setup->id, 404);

        return response()->json([
            'id' => $weekBlock->id,
            'almanac_program_group_id' => $weekBlock->almanac_program_group_id,
            'start_date' => $weekBlock->start_date?->format('Y-m-d'),
            'end_date' => $weekBlock->end_date?->format('Y-m-d'),
            'label_name' => $weekBlock->label_name,
            'display_value' => $weekBlock->display_value,
            'block_type' => $weekBlock->block_type,
            'background_color' => $weekBlock->background_color,
            'text_color' => $weekBlock->text_color,
            'notes' => $weekBlock->notes,
        ]);
    }

    public function store(StoreAlmanacWeekBlockRequest $request, AlmanacSetup $setup): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureDatesInsideSetup($setup, $data['start_date'], $data['end_date']);
        $this->ensureGroupBelongsToSetup($setup, (int) $data['almanac_program_group_id']);
        $this->ensureNoOverlap($setup, $data);

        $setup->weekBlocks()->create($data);
        return back()->with('success', 'Week block created successfully.');
    }

    public function update(StoreAlmanacWeekBlockRequest $request, AlmanacSetup $setup, AlmanacWeekBlock $weekBlock): RedirectResponse
    {
        abort_unless((int) $weekBlock->almanac_setup_id === (int) $setup->id, 404);
        $data = $request->validated();
        $this->ensureDatesInsideSetup($setup, $data['start_date'], $data['end_date']);
        $this->ensureGroupBelongsToSetup($setup, (int) $data['almanac_program_group_id']);
        $this->ensureNoOverlap($setup, $data, $weekBlock->id);

        $weekBlock->update($data);
        return back()->with('success', 'Week block updated successfully.');
    }

    public function generate(Request $request, AlmanacSetup $setup): RedirectResponse
    {
        $data = $request->validate([
            'almanac_program_group_id' => ['required', 'exists:almanac_program_groups,id'],
            'start_date' => ['required', 'date'],
            'number_of_weeks' => ['required', 'integer', 'min:1', 'max:60'],
            'starting_number' => ['required', 'integer', 'min:1'],
            'label_name' => ['required', 'string', 'max:50'],
            'background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $this->ensureGroupBelongsToSetup($setup, (int) $data['almanac_program_group_id']);
        $cursor = Carbon::parse($data['start_date']);

        DB::transaction(function () use ($data, $setup, $cursor): void {
            for ($i = 0; $i < $data['number_of_weeks']; $i++) {
                $end = $cursor->copy()->addDays(6);
                $payload = [
                    'almanac_program_group_id' => $data['almanac_program_group_id'],
                    'start_date' => $cursor->toDateString(),
                    'end_date' => $end->toDateString(),
                    'label_name' => trim($data['label_name']),
                    'display_value' => (string) ($data['starting_number'] + $i),
                    'block_type' => 'teaching',
                    'background_color' => $data['background_color'] ?? null,
                    'text_color' => $data['text_color'] ?? null,
                ];

                $this->ensureDatesInsideSetup($setup, $payload['start_date'], $payload['end_date']);
                $this->ensureNoOverlap($setup, $payload);
                $setup->weekBlocks()->create($payload);
                $cursor->addWeek();
            }
        });

        return back()->with('success', "{$data['number_of_weeks']} week blocks generated and labelled {$data['label_name']} {$data['starting_number']} onward.");
    }

    public function destroy(AlmanacSetup $setup, AlmanacWeekBlock $weekBlock): RedirectResponse
    {
        abort_unless((int) $weekBlock->almanac_setup_id === (int) $setup->id, 404);
        $weekBlock->delete();
        return back()->with('success', 'Week block deleted successfully.');
    }

    private function ensureNoOverlap(AlmanacSetup $setup, array $data, ?int $ignoreId = null): void
    {
        $query = AlmanacWeekBlock::query()
            ->where('almanac_setup_id', $setup->id)
            ->where('almanac_program_group_id', $data['almanac_program_group_id'])
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date']);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'This programme group already has a week block inside the selected date range.',
            ]);
        }
    }

    private function ensureDatesInsideSetup(AlmanacSetup $setup, string $start, string $end): void
    {
        if (Carbon::parse($start)->lt($setup->start_date) || Carbon::parse($end)->gt($setup->end_date)) {
            throw ValidationException::withMessages([
                'start_date' => 'The week block must remain inside the Almanac date range.',
            ]);
        }
    }

    private function ensureGroupBelongsToSetup(AlmanacSetup $setup, int $groupId): void
    {
        if (!$setup->programGroups()->whereKey($groupId)->exists()) {
            throw ValidationException::withMessages([
                'almanac_program_group_id' => 'The selected programme group does not belong to this Almanac.',
            ]);
        }
    }
}

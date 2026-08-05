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
    /**
     * Return all week blocks for the management modal.
     */
    public function index(AlmanacSetup $setup): JsonResponse
    {
        $blocks = $setup->weekBlocks()
            ->with('programGroup:id,name')
            ->orderBy('almanac_program_group_id')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->map(fn (AlmanacWeekBlock $block) => $this->serializeBlock($block));

        return response()->json([
            'setup_id' => $setup->id,
            'count' => $blocks->count(),
            'data' => $blocks,
        ]);
    }

    /**
     * Return one week block for the edit modal.
     */
    public function show(AlmanacSetup $setup, AlmanacWeekBlock $weekBlock): JsonResponse
    {
        $this->ensureBlockBelongsToSetup($setup, $weekBlock);
        $weekBlock->loadMissing('programGroup:id,name');

        return response()->json($this->serializeBlock($weekBlock));
    }

    /**
     * Create a single manual block.
     */
    public function store(
        StoreAlmanacWeekBlockRequest $request,
        AlmanacSetup $setup
    ): RedirectResponse {
        $data = $request->validated();

        $this->ensureDatesInsideSetup($setup, $data['start_date'], $data['end_date']);
        $this->ensureGroupBelongsToSetup($setup, (int) $data['almanac_program_group_id']);
        $this->ensureNoOverlap($setup, $data);

        $setup->weekBlocks()->create($data);

        return back()->with('success', 'Week block created successfully.');
    }

    /**
     * Update one existing block, including generated blocks.
     */
    public function update(
        StoreAlmanacWeekBlockRequest $request,
        AlmanacSetup $setup,
        AlmanacWeekBlock $weekBlock
    ): RedirectResponse {
        $this->ensureBlockBelongsToSetup($setup, $weekBlock);

        $data = $request->validated();

        $this->ensureDatesInsideSetup($setup, $data['start_date'], $data['end_date']);
        $this->ensureGroupBelongsToSetup($setup, (int) $data['almanac_program_group_id']);
        $this->ensureNoOverlap($setup, $data, $weekBlock->id);

        $weekBlock->update($data);

        return back()->with('success', 'Week block updated successfully.');
    }

    /**
     * Generate consecutive labelled blocks, for example Week 1 to Week 15.
     */
    public function generate(Request $request, AlmanacSetup $setup): RedirectResponse
    {
        $data = $request->validate([
            'almanac_program_group_id' => ['required', 'integer', 'exists:almanac_program_groups,id'],
            'start_date' => ['required', 'date'],
            'number_of_weeks' => ['required', 'integer', 'min:1', 'max:60'],
            'starting_number' => ['required', 'integer', 'min:1', 'max:999'],
            'label_name' => ['required', 'string', 'max:50'],
            'block_type' => [
                'required',
                'in:teaching,examination,registration,orientation,fieldwork,clinical,holiday,break,other',
            ],
            'background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->ensureGroupBelongsToSetup($setup, (int) $data['almanac_program_group_id']);

        $cursor = Carbon::parse($data['start_date'])->startOfDay();
        $labelName = trim($data['label_name']);

        DB::transaction(function () use ($data, $setup, $cursor, $labelName): void {
            for ($index = 0; $index < (int) $data['number_of_weeks']; $index++) {
                $start = $cursor->copy();
                $end = $start->copy()->addDays(6);
                $number = (int) $data['starting_number'] + $index;

                $payload = [
                    'almanac_program_group_id' => (int) $data['almanac_program_group_id'],
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'label_name' => $labelName,
                    'display_value' => (string) $number,
                    'block_type' => $data['block_type'],
                    'background_color' => $data['background_color'] ?? null,
                    'text_color' => $data['text_color'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ];

                $this->ensureDatesInsideSetup($setup, $payload['start_date'], $payload['end_date']);
                $this->ensureNoOverlap($setup, $payload);

                $setup->weekBlocks()->create($payload);
                $cursor->addWeek();
            }
        });

        $lastNumber = (int) $data['starting_number'] + (int) $data['number_of_weeks'] - 1;

        return back()->with(
            'success',
            "Generated {$labelName} {$data['starting_number']} up to {$labelName} {$lastNumber}."
        );
    }

    public function destroy(
        AlmanacSetup $setup,
        AlmanacWeekBlock $weekBlock
    ): RedirectResponse {
        $this->ensureBlockBelongsToSetup($setup, $weekBlock);
        $weekBlock->delete();

        return back()->with('success', 'Week block deleted successfully.');
    }

    private function serializeBlock(AlmanacWeekBlock $block): array
    {
        $labelName = trim((string) $block->label_name);
        $displayValue = trim((string) $block->display_value);
        $fullLabel = trim($labelName . ' ' . $displayValue);

        return [
            'id' => $block->id,
            'almanac_setup_id' => $block->almanac_setup_id,
            'almanac_program_group_id' => $block->almanac_program_group_id,
            'program_group_name' => $block->programGroup?->name,
            'start_date' => $block->start_date?->format('Y-m-d'),
            'end_date' => $block->end_date?->format('Y-m-d'),
            'label_name' => $block->label_name,
            'display_value' => $block->display_value,
            'full_label' => $fullLabel,
            'block_type' => $block->block_type,
            'background_color' => $block->background_color,
            'text_color' => $block->text_color,
            'notes' => $block->notes,
        ];
    }

    private function ensureNoOverlap(
        AlmanacSetup $setup,
        array $data,
        ?int $ignoreId = null
    ): void {
        $query = AlmanacWeekBlock::query()
            ->where('almanac_setup_id', $setup->id)
            ->where('almanac_program_group_id', $data['almanac_program_group_id'])
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date']);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'This programme group already has a week block within the selected date range.',
            ]);
        }
    }

    private function ensureDatesInsideSetup(
        AlmanacSetup $setup,
        string $start,
        string $end
    ): void {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        if ($startDate->gt($endDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'The end date must be on or after the start date.',
            ]);
        }

        if ($startDate->lt($setup->start_date) || $endDate->gt($setup->end_date)) {
            throw ValidationException::withMessages([
                'start_date' => 'The week block must remain inside the Almanac setup date range.',
            ]);
        }
    }

    private function ensureGroupBelongsToSetup(
        AlmanacSetup $setup,
        int $groupId
    ): void {
        if (!$setup->programGroups()->whereKey($groupId)->exists()) {
            throw ValidationException::withMessages([
                'almanac_program_group_id' => 'The selected programme group does not belong to this Almanac.',
            ]);
        }
    }

    private function ensureBlockBelongsToSetup(
        AlmanacSetup $setup,
        AlmanacWeekBlock $weekBlock
    ): void {
        abort_unless(
            (int) $weekBlock->almanac_setup_id === (int) $setup->id,
            404
        );
    }
}

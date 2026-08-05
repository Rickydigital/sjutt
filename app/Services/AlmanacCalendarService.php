<?php

namespace App\Services;

use App\Models\AlmanacSetup;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AlmanacCalendarService
{
    public function build(AlmanacSetup $setup): array
    {
        $setup->loadMissing([
            'programGroups' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
            'weekBlocks.programGroup',
            'events.programGroups',
        ]);

        $groups = $setup->programGroups;
        $blocks = $setup->weekBlocks->groupBy('almanac_program_group_id');
        $events = $setup->events;

        $days = collect(CarbonPeriod::create($setup->start_date, $setup->end_date))
            ->map(function (Carbon $date) use ($groups, $blocks, $events): array {
                $dayBlocks = [];

                foreach ($groups as $group) {
                    $block = ($blocks->get($group->id) ?? collect())->first(
                        fn ($item) => $date->betweenIncluded($item->start_date, $item->end_date)
                    );

                    $dayBlocks[$group->id] = $block ? [
                    'id' => $block->id,
                    'start_date' => $block->start_date?->format('Y-m-d'),
                    'end_date' => $block->end_date?->format('Y-m-d'),
                    'label_name' => $block->label_name,
                    'display_value' => $block->display_value,
                    'full_label' => trim(
                        implode(' ', array_filter([
                            $block->label_name,
                            $block->display_value,
                        ]))
                    ),
                    'block_type' => $block->block_type,
                    'background_color' => $block->background_color ?: $group->background_color,
                    'text_color' => $block->text_color ?: $group->text_color,
                ] : null;
                }

                $dayEvents = $events->filter(function ($event) use ($date): bool {
                    $end = $event->end_date ?: $event->start_date;
                    return $date->betweenIncluded($event->start_date, $end);
                });

                return [
                    'date' => $date->copy(),
                    'date_value' => $date->format('Y-m-d'),
                    'month_key' => $date->format('Y-m'),
                    'month_label' => $date->format('F-y'),
                    'day_label' => $date->format('D - d'),
                    'is_week_end' => $date->isSunday(),
                    'week_values' => $dayBlocks,
                    'academic_events' => $this->formatEvents($dayEvents->where('event_column', 'academic'), $date),
                    'meeting_events' => $this->formatEvents($dayEvents->where('event_column', 'meeting'), $date),
                ];
            });

        return [
            'setup' => $setup,
            'groups' => $groups,
            'months' => $days->groupBy('month_key')->map(function (Collection $monthDays): array {
                return [
                    'label' => $monthDays->first()['month_label'],
                    'days' => $monthDays->values(),
                ];
            })->values(),
        ];
    }

    private function formatEvents(Collection $events, Carbon $date): Collection
    {
        return $events->map(function ($event) use ($date): ?array {
            $isStart = $date->isSameDay($event->start_date);
            $hasDistinctEnd = $event->end_date && !$event->end_date->isSameDay($event->start_date);
            $isEnd = $hasDistinctEnd && $date->isSameDay($event->end_date);

            if (!$isStart && !$isEnd) {
                return null;
            }

            $text = $isEnd ? 'End of ' . $event->title : $event->title;
            if ($isStart && $event->description) {
                $text .= ' — ' . $event->description;
            }

            return [
                'id' => $event->id,
                'text' => $text,
                'is_start' => $isStart,
                'is_end' => $isEnd,
                'category' => $event->category,
                'event_column' => $event->event_column,
                'is_no_classes' => $event->is_no_classes,
                'is_tentative' => $event->is_tentative,
                'background_color' => $event->background_color,
                'text_color' => $event->text_color,
            ];
        })->filter()->values();
    }
}

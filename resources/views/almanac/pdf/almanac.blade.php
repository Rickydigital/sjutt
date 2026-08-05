<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>{{ $setup->title }}</title>
<style>
@page { margin: 7mm; size: A4 landscape; }
body { font-family: DejaVu Sans, sans-serif; font-size: 6.5px; color:#000; }
h1 { text-align:center; font-size:11px; margin:0 0 5px; }
table { width:100%; border-collapse:collapse; table-layout:fixed; }
th, td { border:0.5px solid #222; padding:2px; vertical-align:middle; }
th { background:#d9d9d9; text-align:center; font-weight:bold; }
.month { width:24px; text-align:center; font-weight:bold; }
.date { width:45px; white-space:nowrap; }
.event { width:29%; }
.week { width:42px; text-align:center; font-weight:bold; }
.page-break { page-break-before:always; }
</style></head><body>
@foreach($calendar['months']->chunk(2) as $pageMonths)
    @if(!$loop->first)<div class="page-break"></div>@endif
    <h1>{{ $setup->title }}</h1>
    <table>
        <thead><tr><th rowspan="2" class="month">Months</th><th colspan="{{ $calendar['groups']->count() }}">Week Number</th><th rowspan="2" class="date">Dates</th><th rowspan="2" class="event">Academic Calendar</th><th rowspan="2" class="event">Meeting/Activities Calendar</th></tr>
        <tr>@foreach($calendar['groups'] as $group)<th class="week">{{ $group->name }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach($pageMonths as $month)
            @foreach($month['days'] as $dayIndex => $day)
                <tr>
                    @if($dayIndex === 0)<td rowspan="{{ count($month['days']) }}" class="month">{{ $month['label'] }}</td>@endif
                    @foreach($calendar['groups'] as $group)
                        @php($block = $day['week_values'][$group->id] ?? null)
                        <td class="week" style="background:{{ $block['background_color'] ?? '#fff' }};color:{{ $block['text_color'] ?? '#000' }}">{{ $block['full_label'] ?? '' }}</td>
                    @endforeach
                    <td class="date">{{ $day['day_label'] }}</td>
                    <td>@foreach($day['academic_events'] as $event)<div style="color:{{ $event['text_color'] ?: ($event['is_no_classes'] ? '#c00' : '#000') }}">{{ $event['text'] }}</div>@endforeach</td>
                    <td>@foreach($day['meeting_events'] as $event)<div>{{ $event['text'] }}</div>@endforeach</td>
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
@endforeach
</body></html>

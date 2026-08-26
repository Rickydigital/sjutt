<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $setup->title }}</title>
<style>
    @page {
        margin: 30mm 6mm 10mm 6mm;
        size: A4 landscape;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 6.5pt;
        color: #000;
        margin: 0;
    }

    /* ===== REPEATING BRANDED HEADER (shows on every printed page) ===== */
    .print-header {
        position: fixed;
        top: -26mm;
        left: 0;
        right: 0;
        height: 22mm;
        background: white;
        text-align: center;
        padding: 5px 0;
        border-bottom: 2px solid #4B2E83;
        line-height: 1.25;
    }
    .print-header .main-title {
        color: #4B2E83;
        font-weight: bold;
        font-size: 13pt;
        margin: 0;
    }
    .print-header .subtitle {
        font-size: 9pt;
        color: #333;
        margin: 2px 0;
    }
    .print-header .draft {
        font-weight: bold;
        color: #4B2E83;
        font-size: 8.5pt;
    }
    .print-header .logo {
        height: 26px;
        margin-top: 3px;
    }

    h1 { display: none; } /* replaced by print-header */

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    th, td {
        border: 0.5px solid #4B2E83;
        padding: 2px;
        vertical-align: middle;
    }

    thead th {
        background: #4B2E83 !important;
        color: #fff !important;
        text-align: center;
        font-weight: bold;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* keep every row intact - never sliced by a page break */
    tr { page-break-inside: avoid !important; }

    /* ===== Month column: one vertical label per month block (rowspan-merged) ===== */
    .month {
        width: 16px;
        text-align: center;
        font-weight: normal;
        font-size: 6pt;
        color: #444;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        letter-spacing: 0.3px;
        white-space: nowrap;
    }
    .month-start .month,
    .month.merged {
        font-weight: bold;
        color: #4B2E83;
        border-top: 1.6px solid #4B2E83 !important;
    }

    .date { width: 45px; white-space: nowrap; }
    .event { width: 29%; }

    /* ===== Week columns: one vertical label per week block (rowspan-merged) ===== */
    .week {
        width: 20px;
        text-align: center;
        font-weight: bold;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        white-space: nowrap;
    }

    /* Spacer column between Programme Groups: a real gap instead of an
       overlapping border line, so the separation reads as actual space. */
    .group-spacer {
        width: 2.2mm;
        border-top: none;
        border-bottom: none;
        border-left: none;
        border-right: none;
        background: #ffffff;
        padding: 0;
    }
    thead .group-spacer {
        background: #ffffff;
    }

    .page-break { page-break-before: always; }
</style>
</head>
<body>

    <div class="print-header">
        <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
        <div class="subtitle">{{ $setup->title }} &bull; {{ $setup->academicYear->year ?? $setup->academicYear->name ?? '' }}</div>
        <div class="draft">{{ $setup->status === 'active' ? 'Official' : 'Draft' }}</div>
        <img src="{{ public_path('images/logo.png') }}" alt="SJUT" class="logo">
    </div>

    @php
        // Flatten months/days into a single ordered list so we can compute
        // rowspans that merge repeated month / week labels into a single
        // vertical cell instead of repeating them on every row.
        $flatDays = [];
        foreach ($calendar['months'] as $mIndex => $month) {
            foreach ($month['days'] as $dIndex => $day) {
                $flatDays[] = [
                    'is_month_start' => $dIndex === 0,
                    'month_label'    => $month['label'],
                    'day'            => $day,
                ];
            }
        }
        $totalRows = count($flatDays);

        // Rowspan for the Months column: one span per month block.
        $monthRowspan = [];
        foreach ($flatDays as $i => $fd) {
            if ($fd['is_month_start']) {
                $count = 1;
                for ($j = $i + 1; $j < $totalRows && !$flatDays[$j]['is_month_start']; $j++) {
                    $count++;
                }
                $monthRowspan[$i] = $count;
            }
        }

        // Rowspan for each Programme Group's Week column: merge consecutive
        // rows that share the same week label + colour into one cell.
        $weekRowspan = [];
        $weekSkip    = [];
        foreach ($calendar['groups'] as $group) {
            $gid = $group->id;
            $i = 0;
            while ($i < $totalRows) {
                $label = $flatDays[$i]['day']['week_values'][$gid]['full_label'] ?? null;
                $bg    = $flatDays[$i]['day']['week_values'][$gid]['background_color'] ?? null;
                $j = $i + 1;
                while ($j < $totalRows) {
                    $lbl2 = $flatDays[$j]['day']['week_values'][$gid]['full_label'] ?? null;
                    $bg2  = $flatDays[$j]['day']['week_values'][$gid]['background_color'] ?? null;
                    if ($lbl2 === $label && $bg2 === $bg) {
                        $j++;
                    } else {
                        break;
                    }
                }
                $weekRowspan[$gid][$i] = $j - $i;
                for ($k = $i + 1; $k < $j; $k++) {
                    $weekSkip[$gid][$k] = true;
                }
                $i = $j;
            }
        }
    @endphp

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="month">Months</th>
                <th colspan="{{ $calendar['groups']->count() * 2 - 1 }}">Week Number</th>
                <th rowspan="2" class="date">Dates</th>
                <th rowspan="2" class="event">Academic Calendar</th>
                <th rowspan="2" class="event">Meeting/Activities Calendar</th>
            </tr>
            <tr>
                @foreach ($calendar['groups'] as $index => $group)
                    @if ($index > 0)
                        <th class="group-spacer"></th>
                    @endif
                    <th class="week">{{ $group->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($flatDays as $i => $fd)
                @php($day = $fd['day'])
                <tr class="{{ $fd['is_month_start'] ? 'month-start' : '' }}">

                    @if ($fd['is_month_start'])
                        <td class="month merged" rowspan="{{ $monthRowspan[$i] }}">{{ $fd['month_label'] }}</td>
                    @endif

                    @foreach ($calendar['groups'] as $index => $group)
                        @php($gid = $group->id)
                        @if ($index > 0)
                            <td class="group-spacer"></td>
                        @endif
                        @if (empty($weekSkip[$gid][$i]))
                            @php($block = $day['week_values'][$gid] ?? null)
                            <td
                                class="week"
                                rowspan="{{ $weekRowspan[$gid][$i] }}"
                                style="background:{{ $block['background_color'] ?? '#fff' }};color:{{ $block['text_color'] ?? '#000' }}"
                            >{{ $block['full_label'] ?? '' }}</td>
                        @endif
                    @endforeach

                    <td class="date">{{ $day['day_label'] }}</td>

                    <td>
                        @foreach ($day['academic_events'] as $event)
                            <div style="color:{{ $event['text_color'] ?: ($event['is_no_classes'] ? '#c00' : '#000') }}">{{ $event['text'] }}</div>
                        @endforeach
                    </td>

                    <td>
                        @foreach ($day['meeting_events'] as $event)
                            <div>{{ $event['text'] }}</div>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
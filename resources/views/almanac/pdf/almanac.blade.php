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

    .month {
        width: 22px;
        text-align: center;
        font-weight: normal;
        font-size: 6pt;
        color: #444;
    }
    /* bold purple rule on the first day of a new month = strong, unmistakable separator */
    .month-start td, .month-start th {
        border-top: 1.6px solid #4B2E83 !important;
    }
    .month-start .month {
        font-weight: bold;
        color: #4B2E83;
    }

    .date { width: 45px; white-space: nowrap; }
    .event { width: 29%; }

    .week {
        width: 42px;
        text-align: center;
        font-weight: bold;
    }
    .week-block {
        padding: 3px 1px;
        line-height: 1.15;
    }
    .week-block-continue {
        border-top-color: transparent !important;
    }
    .week-block:not(.week-block-end) {
        border-bottom-color: transparent !important;
    }
    .week-block-label {
        display: block;
        font-size: 6.5pt;
        font-weight: bold;
        overflow-wrap: break-word;
    }
    /* bold separation between each Programme Group's week column */
    .week-group-start {
        border-left: 1.8px solid #4B2E83 !important;
    }

    .page-break { page-break-before: always; }
</style>
</head>
<body>

    <div class="print-header">
        <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
        <div class="subtitle">{{ $setup->title }}</div>
        <img src="{{ public_path('images/logo.png') }}" alt="SJUT" class="logo">
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="month">Months</th>
                <th colspan="{{ $calendar['groups']->count() }}">Week Number</th>
                <th rowspan="2" class="date">Dates</th>
                <th rowspan="2" class="event">Academic Calendar</th>
                <th rowspan="2" class="event">Meeting/Activities Calendar</th>
            </tr>
            <tr>
                @foreach ($calendar['groups'] as $index => $group)
                    <th class="week {{ $index > 0 ? 'week-group-start' : '' }}">{{ $group->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($calendar['months'] as $month)
                @foreach ($month['days'] as $dayIndex => $day)
                    <tr class="{{ $dayIndex === 0 ? 'month-start' : '' }}">
                        <td class="month">{{ $dayIndex === 0 ? $month['label'] : '' }}</td>

                        @foreach ($calendar['groups'] as $index => $group)
                            @php
                                $block = $day['week_values'][$group->id] ?? null;
                            @endphp
                            @if (!$block)
                                <td class="week {{ $index > 0 ? 'week-group-start' : '' }}"></td>
                            @else
                                <td
                                    class="week week-block
                                        {{ !$block['is_block_start'] ? 'week-block-continue' : '' }}
                                        {{ $block['is_block_end'] ? 'week-block-end' : '' }}
                                        {{ $index > 0 ? 'week-group-start' : '' }}"
                                    style="background:{{ $block['background_color'] }};color:{{ $block['text_color'] }}"
                                >
                                    @if ($block['is_block_start'])
                                        <span class="week-block-label">{{ $block['full_label'] }}</span>
                                    @endif
                                </td>
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
            @endforeach
        </tbody>
    </table>

</body>
</html>

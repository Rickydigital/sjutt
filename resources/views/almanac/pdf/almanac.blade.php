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

        /*
        |--------------------------------------------------------------------------
        | REPEATING HEADER
        |--------------------------------------------------------------------------
        */

        .print-header {
            position: fixed;
            top: -26mm;
            left: 0;
            right: 0;
            height: 22mm;
            background: #fff;
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

        h1 {
            display: none;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 0.5px solid #4B2E83;
            padding: 2px;
            vertical-align: middle;
        }

        thead {
            display: table-header-group;
        }

        thead th {
            background: #4B2E83 !important;
            color: #fff !important;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;

            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /*
        |--------------------------------------------------------------------------
        | ROW PAGE BREAK
        |--------------------------------------------------------------------------
        */

        tr {
            page-break-inside: avoid;
        }

        /*
        |--------------------------------------------------------------------------
        | MONTH COLUMN
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | No rowspan is used.
        |
        | Each day still receives a month cell but internal borders are hidden,
        | making the cells visually appear as one large merged month.
        |
        | This is safe when Dompdf moves rows to another page.
        |--------------------------------------------------------------------------
        */

        .month {
            width: 24px;
            padding: 0 !important;
            text-align: center;
            vertical-align: middle !important;

            border-left: 0.5px solid #4B2E83 !important;
            border-right: 0.5px solid #4B2E83 !important;

            position: relative;
        }

        /*
        |--------------------------------------------------------------------------
        | NORMAL MONTH ROW
        |--------------------------------------------------------------------------
        |
        | Remove horizontal lines between days in the month column.
        |--------------------------------------------------------------------------
        */

        .month-middle {
            border-top-color: transparent !important;
            border-bottom-color: transparent !important;
        }

        /*
        |--------------------------------------------------------------------------
        | FIRST DAY OF MONTH
        |--------------------------------------------------------------------------
        */

        .month-first {
            border-top: 1.6px solid #4B2E83 !important;
            border-bottom-color: transparent !important;
        }

        /*
        |--------------------------------------------------------------------------
        | LAST DAY OF MONTH
        |--------------------------------------------------------------------------
        */

        .month-last {
            border-top-color: transparent !important;
            border-bottom: 0.5px solid #4B2E83 !important;
        }

        /*
        |--------------------------------------------------------------------------
        | MONTH WITH ONLY ONE ROW
        |--------------------------------------------------------------------------
        */

        .month-only {
            border-top: 1.6px solid #4B2E83 !important;
            border-bottom: 0.5px solid #4B2E83 !important;
        }

        /*
        |--------------------------------------------------------------------------
        | MONTH LABEL
        |--------------------------------------------------------------------------
        */

        .month-label {
            display: inline-block;
            white-space: nowrap;

            font-size: 8pt;
            font-weight: bold;
            color: #222;

            transform: rotate(-90deg);
            transform-origin: center center;

            line-height: 1;
        }

        /*
        |--------------------------------------------------------------------------
        | MONTH START SEPARATOR
        |--------------------------------------------------------------------------
        */

        .month-start > td:not(.month),
        .month-start > th {
            border-top: 1.6px solid #4B2E83 !important;
        }

        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        .date {
            width: 45px;
            white-space: nowrap;
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | EVENT COLUMNS
        |--------------------------------------------------------------------------
        */

        .event {
            width: 29%;
        }

        /*
        |--------------------------------------------------------------------------
        | WEEK COLUMNS
        |--------------------------------------------------------------------------
        */

        .week {
            width: 42px;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
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
            word-wrap: break-word;
        }

        /*
        |--------------------------------------------------------------------------
        | GROUP SEPARATOR
        |--------------------------------------------------------------------------
        */

        .week-group-start {
            border-left: 1.8px solid #4B2E83 !important;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

    {{-- ================================================================
        REPEATING HEADER
    ================================================================= --}}

    <div class="print-header">

        <div class="main-title">
            ST JOHN'S UNIVERSITY OF TANZANIA
        </div>

        <div class="subtitle">
            {{ $setup->title }}
        </div>

        <img
            src="{{ public_path('images/logo.png') }}"
            alt="SJUT"
            class="logo"
        >

    </div>


    {{-- ================================================================
        CALENDAR TABLE
    ================================================================= --}}

    <table>

        <thead>

            <tr>

                <th rowspan="2" class="month">
                    Months
                </th>

                <th colspan="{{ $calendar['groups']->count() }}">
                    Week Number
                </th>

                <th rowspan="2" class="date">
                    Dates
                </th>

                <th rowspan="2" class="event">
                    Academic Calendar
                </th>

                <th rowspan="2" class="event">
                    Meeting/Activities Calendar
                </th>

            </tr>

            <tr>

                @foreach ($calendar['groups'] as $index => $group)

                    <th
                        class="
                            week
                            {{ $index > 0 ? 'week-group-start' : '' }}
                        "
                    >
                        {{ $group->name }}
                    </th>

                @endforeach

            </tr>

        </thead>


        <tbody>

            @foreach ($calendar['months'] as $month)

                @php
                    $monthDays = $month['days'];

                    $monthRowCount = count($monthDays);

                    /*
                    |--------------------------------------------------------------------------
                    | Choose approximately the middle row
                    |--------------------------------------------------------------------------
                    |
                    | Instead of putting the month label on day 1, place it close
                    | to the center of the month.
                    |
                    | Example:
                    | October with 31 days:
                    | middle = approximately day 16.
                    |--------------------------------------------------------------------------
                    */

                    $monthLabelRow = (int) floor(($monthRowCount - 1) / 2);
                @endphp


                @foreach ($monthDays as $dayIndex => $day)

                    @php

                        $isFirstDay =
                            $dayIndex === 0;

                        $isLastDay =
                            $dayIndex === ($monthRowCount - 1);

                        /*
                        |--------------------------------------------------------------------------
                        | Determine month-cell border class
                        |--------------------------------------------------------------------------
                        */

                        if ($isFirstDay && $isLastDay) {

                            $monthCellClass = 'month-only';

                        } elseif ($isFirstDay) {

                            $monthCellClass = 'month-first';

                        } elseif ($isLastDay) {

                            $monthCellClass = 'month-last';

                        } else {

                            $monthCellClass = 'month-middle';

                        }

                    @endphp


                    <tr
                        class="{{ $isFirstDay ? 'month-start' : '' }}"
                    >


                        {{-- ====================================================
                            MONTH COLUMN

                            DO NOT USE ROWSPAN HERE.

                            Every row gets its own cell so Dompdf can safely
                            move that row onto the next PDF page.
                        ===================================================== --}}

                        <td
                            class="
                                month
                                {{ $monthCellClass }}
                            "
                        >

                            {{-- Show label near middle of month --}}
                            @if ($dayIndex === $monthLabelRow)

                                <span class="month-label">
                                    {{ $month['label'] }}
                                </span>

                            @endif

                        </td>


                        {{-- ====================================================
                            WEEK COLUMNS
                        ===================================================== --}}

                        @foreach ($calendar['groups'] as $index => $group)

                            @php

                                $block =
                                    $day['week_values'][$group->id]
                                    ?? null;

                            @endphp


                            @if (!$block)

                                <td
                                    class="
                                        week
                                        {{ $index > 0
                                            ? 'week-group-start'
                                            : ''
                                        }}
                                    "
                                >
                                </td>

                            @else

                                <td
                                    class="
                                        week
                                        week-block

                                        {{ !$block['is_block_start']
                                            ? 'week-block-continue'
                                            : ''
                                        }}

                                        {{ $block['is_block_end']
                                            ? 'week-block-end'
                                            : ''
                                        }}

                                        {{ $index > 0
                                            ? 'week-group-start'
                                            : ''
                                        }}
                                    "

                                    style="
                                        background:
                                            {{ $block['background_color'] }};

                                        color:
                                            {{ $block['text_color'] }};
                                    "
                                >

                                    @if ($block['is_block_start'])

                                        <span class="week-block-label">

                                            {{ $block['full_label'] }}

                                        </span>

                                    @endif

                                </td>

                            @endif

                        @endforeach


                        {{-- ====================================================
                            DATE
                        ===================================================== --}}

                        <td class="date">

                            {{ $day['day_label'] }}

                        </td>


                        {{-- ====================================================
                            ACADEMIC CALENDAR
                        ===================================================== --}}

                        <td>

                            @foreach ($day['academic_events'] as $event)

                                <div
                                    style="
                                        color:
                                        {{
                                            $event['text_color']
                                            ?: (
                                                $event['is_no_classes']
                                                ? '#c00'
                                                : '#000'
                                            )
                                        }};
                                    "
                                >

                                    {{ $event['text'] }}

                                </div>

                            @endforeach

                        </td>


                        {{-- ====================================================
                            MEETING / ACTIVITIES
                        ===================================================== --}}

                        <td>

                            @foreach ($day['meeting_events'] as $event)

                                <div>
                                    {{ $event['text'] }}
                                </div>

                            @endforeach

                        </td>


                    </tr>

                @endforeach

            @endforeach

        </tbody>

    </table>

</body>
</html>
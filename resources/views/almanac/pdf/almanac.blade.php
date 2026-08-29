<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>{{ $setup->title }}</title>

    <style>

        /*
        |--------------------------------------------------------------------------
        | PAGE SETUP
        |--------------------------------------------------------------------------
        */

        @page {
            margin: 30mm 6mm 10mm 6mm;
            size: A4 landscape;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 6.5pt;
            color: #000;
            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | REPEATING PDF HEADER
        |--------------------------------------------------------------------------
        */

        .print-header {
            position: fixed;

            top: -26mm;
            left: 0;
            right: 0;

            height: 22mm;

            background: #ffffff;

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


        /*
        |--------------------------------------------------------------------------
        | TABLE HEADER
        |--------------------------------------------------------------------------
        */

        thead th {
            background: #4B2E83 !important;

            color: #ffffff !important;

            text-align: center;

            font-weight: bold;

            vertical-align: middle;

            -webkit-print-color-adjust: exact;

            print-color-adjust: exact;
        }


        /*
        |--------------------------------------------------------------------------
        | PREVENT ROW SPLITTING
        |--------------------------------------------------------------------------
        */

        tr {
            page-break-inside: avoid !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH COLUMN
        |--------------------------------------------------------------------------
        |
        | Each month is rendered only once and rowspan is used to merge all
        | days belonging to that month.
        |
        */

        .month {
            width: 24px;

            padding: 0 !important;

            text-align: center;

            vertical-align: middle !important;

            font-weight: bold;

            font-size: 7.5pt;

            color: #222;
        }


        /*
        |--------------------------------------------------------------------------
        | MERGED MONTH CELL
        |--------------------------------------------------------------------------
        */

        .month-merged {
            padding: 0 !important;

            text-align: center !important;

            vertical-align: middle !important;

            overflow: visible;
        }


        /*
        |--------------------------------------------------------------------------
        | VERTICAL MONTH NAME
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | October-26
        |
        | displayed vertically like the sample image.
        |
        | transform is generally safer for DOMPDF than writing-mode.
        |
        */

        .month-vertical {
            display: inline-block;

            white-space: nowrap;

            font-size: 8pt;

            font-weight: bold;

            color: #222;

            line-height: 1;

            transform: rotate(-90deg);

            transform-origin: center center;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH SEPARATOR
        |--------------------------------------------------------------------------
        */

        .month-start > td,
        .month-start > th {
            border-top: 1.6px solid #4B2E83 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | DATE COLUMN
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
        | WEEK COLUMN
        |--------------------------------------------------------------------------
        */

        .week {
            width: 42px;

            text-align: center;

            vertical-align: middle;

            font-weight: bold;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK BLOCK
        |--------------------------------------------------------------------------
        */

        .week-block {
            padding: 3px 1px;

            line-height: 1.15;
        }


        /*
        |--------------------------------------------------------------------------
        | CONTINUING WEEK BLOCK
        |--------------------------------------------------------------------------
        */

        .week-block-continue {
            border-top-color: transparent !important;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK BLOCK BOTTOM
        |--------------------------------------------------------------------------
        */

        .week-block:not(.week-block-end) {
            border-bottom-color: transparent !important;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK LABEL
        |--------------------------------------------------------------------------
        */

        .week-block-label {
            display: block;

            font-size: 6.5pt;

            font-weight: bold;

            overflow-wrap: break-word;

            word-wrap: break-word;
        }


        /*
        |--------------------------------------------------------------------------
        | PROGRAMME GROUP SEPARATOR
        |--------------------------------------------------------------------------
        */

        .week-group-start {
            border-left: 1.8px solid #4B2E83 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MANUAL PAGE BREAK
        |--------------------------------------------------------------------------
        */

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

        {{-- ============================================================
            HEADER
        ============================================================= --}}

        <thead>

            <tr>

                {{-- MONTH --}}
                <th
                    rowspan="2"
                    class="month"
                >
                    Months
                </th>


                {{-- WEEK NUMBER --}}
                <th
                    colspan="{{ $calendar['groups']->count() }}"
                >
                    Week Number
                </th>


                {{-- DATE --}}
                <th
                    rowspan="2"
                    class="date"
                >
                    Dates
                </th>


                {{-- ACADEMIC CALENDAR --}}
                <th
                    rowspan="2"
                    class="event"
                >
                    Academic Calendar
                </th>


                {{-- MEETING CALENDAR --}}
                <th
                    rowspan="2"
                    class="event"
                >
                    Meeting/Activities Calendar
                </th>

            </tr>


            {{-- PROGRAMME GROUPS --}}
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



        {{-- ============================================================
            BODY
        ============================================================= --}}

        <tbody>

            @foreach ($calendar['months'] as $month)

                @php
                    /*
                    |--------------------------------------------------------------------------
                    | Count rows belonging to this month
                    |--------------------------------------------------------------------------
                    |
                    | This is what allows the month cell to span from the
                    | first day until the last day of the month.
                    |
                    */

                    $monthRowCount = count($month['days']);
                @endphp


                @foreach ($month['days'] as $dayIndex => $day)

                    <tr
                        class="
                            {{ $dayIndex === 0 ? 'month-start' : '' }}
                        "
                    >


                        {{-- ====================================================
                            MONTH

                            Only create this TD on the FIRST day.

                            rowspan merges the month cell vertically.
                        ===================================================== --}}

                        @if ($dayIndex === 0)

                            <td
                                rowspan="{{ $monthRowCount }}"
                                class="month month-merged"
                            >

                                <div class="month-vertical">
                                    {{ $month['label'] }}
                                </div>

                            </td>

                        @endif



                        {{-- ====================================================
                            WEEK NUMBERS
                        ===================================================== --}}

                        @foreach ($calendar['groups'] as $index => $group)

                            @php

                                $block =
                                    $day['week_values'][$group->id]
                                    ?? null;

                            @endphp


                            {{-- EMPTY WEEK VALUE --}}
                            @if (!$block)

                                <td
                                    class="
                                        week
                                        {{ $index > 0 ? 'week-group-start' : '' }}
                                    "
                                >
                                </td>


                            {{-- WEEK VALUE EXISTS --}}
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


                                    {{-- Display label only once --}}
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
                            MEETING / ACTIVITIES CALENDAR
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
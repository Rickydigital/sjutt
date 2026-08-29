<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>{{ $setup->title }}</title>

    <style>

        /*
        |--------------------------------------------------------------------------
        | PAGE
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
        | REPEATING TABLE HEADER
        |--------------------------------------------------------------------------
        */

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
        | ROW
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
        |
        | Month DOES NOT use rowspan.
        |
        | A month can contain 28 - 31 rows and can cross a physical PDF page.
        |
        | Therefore every day keeps its month cell, but internal borders are
        | removed so visually it looks like one large merged cell.
        |--------------------------------------------------------------------------
        */

        .month {
            width: 25px;

            padding: 0 !important;

            text-align: center;

            vertical-align: middle !important;

            position: relative;

            border-left: 0.5px solid #4B2E83 !important;

            border-right: 0.5px solid #4B2E83 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH FIRST
        |--------------------------------------------------------------------------
        */

        .month-first {
            border-top: 1.6px solid #4B2E83 !important;

            border-bottom-color: transparent !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH MIDDLE
        |--------------------------------------------------------------------------
        */

        .month-middle {
            border-top-color: transparent !important;

            border-bottom-color: transparent !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH LAST
        |--------------------------------------------------------------------------
        */

        .month-last {
            border-top-color: transparent !important;

            border-bottom: 0.5px solid #4B2E83 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH ONLY
        |--------------------------------------------------------------------------
        */

        .month-only {
            border-top: 1.6px solid #4B2E83 !important;

            border-bottom: 0.5px solid #4B2E83 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH VERTICAL TEXT
        |--------------------------------------------------------------------------
        */

        .month-label {
            display: inline-block;

            white-space: nowrap;

            font-size: 8pt;

            font-weight: bold;

            line-height: 1;

            color: #222;

            transform: rotate(-90deg);

            transform-origin: center center;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH SEPARATOR
        |--------------------------------------------------------------------------
        */

        .month-start > td:not(.month),
        .month-start > th {
            border-top: 1.6px solid #4B2E83 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK COLUMN
        |--------------------------------------------------------------------------
        |
        | Week DOES use rowspan.
        |
        | Week blocks are short, generally around 7 days, therefore this gives
        | a much cleaner appearance than placing vertical text inside one day.
        |--------------------------------------------------------------------------
        */

        .week {
            width: 36px;

            padding: 0 !important;

            text-align: center;

            vertical-align: middle !important;

            font-weight: bold;
        }


        /*
        |--------------------------------------------------------------------------
        | MERGED WEEK CELL
        |--------------------------------------------------------------------------
        */

        .week-merged {
            padding: 0 !important;

            text-align: center !important;

            vertical-align: middle !important;

            overflow: hidden;

            line-height: 1;

            -webkit-print-color-adjust: exact;

            print-color-adjust: exact;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK VERTICAL TEXT
        |--------------------------------------------------------------------------
        */

        .week-label {
            display: inline-block;

            white-space: nowrap;

            font-size: 6.5pt;

            font-weight: bold;

            line-height: 1;

            text-align: center;

            transform: rotate(-90deg);

            transform-origin: center center;
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
        | PROGRAMME GROUP
        |--------------------------------------------------------------------------
        |
        | Long names wrap rather than entering another programme column.
        |--------------------------------------------------------------------------
        */

        .programme-group {
            width: 36px;

            text-align: center;

            vertical-align: middle !important;

            white-space: normal !important;

            word-wrap: break-word;

            overflow-wrap: break-word;

            word-break: break-word;

            line-height: 1.15;

            padding: 3px 2px !important;

            font-size: 5.5pt;
        }


        /*
        |--------------------------------------------------------------------------
        | PROGRAMME HEADER HEIGHT
        |--------------------------------------------------------------------------
        */

        thead tr:nth-child(2) th {
            height: auto;

            min-height: 32px;

            white-space: normal !important;

            line-height: 1.15;
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
        | EVENT TEXT
        |--------------------------------------------------------------------------
        */

        .academic-event,
        .meeting-event {
            line-height: 1.15;
        }


        /*
        |--------------------------------------------------------------------------
        | PAGE BREAK
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
            TABLE HEADER
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


            {{-- ========================================================
                PROGRAMME GROUPS
            ========================================================= --}}

            <tr>

                @foreach ($calendar['groups'] as $index => $group)

                    <th
                        class="
                            week
                            programme-group

                            {{ $index > 0
                                ? 'week-group-start'
                                : ''
                            }}
                        "
                    >

                        {{ $group->name }}

                    </th>

                @endforeach

            </tr>

        </thead>



        {{-- ============================================================
            TABLE BODY
        ============================================================= --}}

        <tbody>


            @foreach ($calendar['months'] as $month)


                @php

                    /*
                    |--------------------------------------------------------------------------
                    | MONTH INFORMATION
                    |--------------------------------------------------------------------------
                    */

                    $monthDays =
                        $month['days'];


                    $monthRowCount =
                        count($monthDays);


                    /*
                    |--------------------------------------------------------------------------
                    | MONTH LABEL POSITION
                    |--------------------------------------------------------------------------
                    |
                    | Put month text approximately in the middle.
                    |--------------------------------------------------------------------------
                    */

                    $monthLabelRow =
                        (int) floor(
                            ($monthRowCount - 1) / 2
                        );



                    /*
                    |--------------------------------------------------------------------------
                    | WEEK ROWSPAN MAP
                    |--------------------------------------------------------------------------
                    |
                    | Example:
                    |
                    | Mon   ┌───────────┐
                    | Tue   │           │
                    | Wed   │  Week 1   │
                    | Thu   │ vertical  │
                    | Fri   │           │
                    | Sat   │           │
                    | Sun   └───────────┘
                    |
                    | We detect start + end and generate one rowspan cell.
                    |--------------------------------------------------------------------------
                    */

                    $weekSpans = [];


                    foreach ($calendar['groups'] as $group) {


                        $blockStart = null;

                        $blockData = null;


                        foreach ($monthDays as $rowIndex => $monthDay) {


                            $currentBlock =
                                $monthDay['week_values'][$group->id]
                                ?? null;


                            /*
                            |--------------------------------------------------------------------------
                            | NO WEEK VALUE
                            |--------------------------------------------------------------------------
                            */

                            if (!$currentBlock) {

                                continue;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | START OF WEEK BLOCK
                            |--------------------------------------------------------------------------
                            */

                            if ($currentBlock['is_block_start']) {

                                $blockStart =
                                    $rowIndex;


                                $blockData =
                                    $currentBlock;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | END OF WEEK BLOCK
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $currentBlock['is_block_end']
                                &&
                                $blockStart !== null
                            ) {


                                $blockEnd =
                                    $rowIndex;


                                $rowspan =
                                    ($blockEnd - $blockStart) + 1;


                                /*
                                |--------------------------------------------------------------------------
                                | FIRST ROW
                                |--------------------------------------------------------------------------
                                */

                                $weekSpans[$group->id][$blockStart] = [

                                    'rowspan' =>
                                        $rowspan,

                                    'label' =>
                                        $blockData['full_label'],

                                    'background_color' =>
                                        $blockData['background_color'],

                                    'text_color' =>
                                        $blockData['text_color'],

                                    'skip' =>
                                        false,

                                ];


                                /*
                                |--------------------------------------------------------------------------
                                | COVERED ROWS
                                |--------------------------------------------------------------------------
                                |
                                | These rows must NOT output their own <td>
                                | because the rowspan above already covers them.
                                |--------------------------------------------------------------------------
                                */

                                for (
                                    $coveredRow = $blockStart + 1;
                                    $coveredRow <= $blockEnd;
                                    $coveredRow++
                                ) {

                                    $weekSpans[$group->id][$coveredRow] = [

                                        'skip' => true,

                                    ];

                                }


                                /*
                                |--------------------------------------------------------------------------
                                | RESET
                                |--------------------------------------------------------------------------
                                */

                                $blockStart = null;

                                $blockData = null;

                            }

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | SAFETY FOR A BLOCK THAT REACHES THE END OF THIS MONTH
                        |--------------------------------------------------------------------------
                        |
                        | Sometimes a week may begin but its "block end" can occur
                        | outside the current month.
                        |
                        | In that case create a rowspan only until the last day
                        | available in this month.
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $blockStart !== null
                            &&
                            $blockData !== null
                        ) {


                            $blockEnd =
                                $monthRowCount - 1;


                            $rowspan =
                                ($blockEnd - $blockStart) + 1;


                            $weekSpans[$group->id][$blockStart] = [

                                'rowspan' =>
                                    $rowspan,

                                'label' =>
                                    $blockData['full_label'],

                                'background_color' =>
                                    $blockData['background_color'],

                                'text_color' =>
                                    $blockData['text_color'],

                                'skip' =>
                                    false,

                            ];


                            for (
                                $coveredRow = $blockStart + 1;
                                $coveredRow <= $blockEnd;
                                $coveredRow++
                            ) {

                                $weekSpans[$group->id][$coveredRow] = [

                                    'skip' => true,

                                ];

                            }

                        }

                    }

                @endphp



                {{-- ========================================================
                    DAYS
                ========================================================= --}}

                @foreach ($monthDays as $dayIndex => $day)


                    @php

                        /*
                        |--------------------------------------------------------------------------
                        | MONTH POSITION
                        |--------------------------------------------------------------------------
                        */

                        $isFirstDay =
                            $dayIndex === 0;


                        $isLastDay =
                            $dayIndex ===
                            ($monthRowCount - 1);



                        /*
                        |--------------------------------------------------------------------------
                        | MONTH BORDER CLASS
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $isFirstDay
                            &&
                            $isLastDay
                        ) {

                            $monthCellClass =
                                'month-only';

                        }

                        elseif ($isFirstDay) {

                            $monthCellClass =
                                'month-first';

                        }

                        elseif ($isLastDay) {

                            $monthCellClass =
                                'month-last';

                        }

                        else {

                            $monthCellClass =
                                'month-middle';

                        }

                    @endphp



                    <tr
                        class="
                            {{ $isFirstDay
                                ? 'month-start'
                                : ''
                            }}
                        "
                    >



                        {{-- ====================================================
                            MONTH
                        ===================================================== --}}

                        <td
                            class="
                                month
                                {{ $monthCellClass }}
                            "
                        >


                            @if (
                                $dayIndex ===
                                $monthLabelRow
                            )

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

                                /*
                                |--------------------------------------------------------------------------
                                | WEEK SPAN FOR THIS POSITION
                                |--------------------------------------------------------------------------
                                */

                                $span =
                                    $weekSpans[$group->id][$dayIndex]
                                    ?? null;


                                /*
                                |--------------------------------------------------------------------------
                                | ORIGINAL BLOCK
                                |--------------------------------------------------------------------------
                                */

                                $originalBlock =
                                    $day['week_values'][$group->id]
                                    ?? null;

                            @endphp



                            {{-- ================================================
                                ROW ALREADY COVERED BY ROWSPAN
                            ================================================= --}}

                            @if (
                                $span
                                &&
                                ($span['skip'] ?? false)
                            )

                                {{--
                                    IMPORTANT:

                                    Do NOT output a TD here.

                                    The TD created on the first row already
                                    covers this position using rowspan.
                                --}}



                            {{-- ================================================
                                START OF WEEK ROWSPAN
                            ================================================= --}}

                            @elseif (
                                $span
                                &&
                                isset($span['rowspan'])
                            )


                                <td
                                    rowspan="{{ $span['rowspan'] }}"

                                    class="
                                        week
                                        week-merged

                                        {{ $index > 0
                                            ? 'week-group-start'
                                            : ''
                                        }}
                                    "

                                    style="
                                        background:
                                            {{ $span['background_color'] }};

                                        color:
                                            {{ $span['text_color'] }};
                                    "
                                >


                                    <span class="week-label">

                                        {{ $span['label'] }}

                                    </span>


                                </td>



                            {{-- ================================================
                                WEEK VALUE EXISTS BUT NOT MAPPED
                            ================================================= --}}

                            @elseif ($originalBlock)


                                <td
                                    class="
                                        week

                                        {{ $index > 0
                                            ? 'week-group-start'
                                            : ''
                                        }}
                                    "

                                    style="
                                        background:
                                            {{ $originalBlock['background_color'] }};

                                        color:
                                            {{ $originalBlock['text_color'] }};
                                    "
                                >

                                    @if ($originalBlock['is_block_start'])

                                        <span class="week-label">

                                            {{ $originalBlock['full_label'] }}

                                        </span>

                                    @endif

                                </td>



                            {{-- ================================================
                                EMPTY WEEK
                            ================================================= --}}

                            @else


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
                                    class="academic-event"

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


                                <div class="meeting-event">

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
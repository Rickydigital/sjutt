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
        | TABLE HEADER REPEATS
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
        | ROWS
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
        | NO REAL ROWSPAN
        |
        | Every date has its own month cell.
        | Internal horizontal borders are removed so visually it behaves
        | exactly like one merged rowspan cell.
        |
        | This remains safe when DomPDF creates another page.
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
        | MONTH FIRST CELL
        |--------------------------------------------------------------------------
        */

        .month-first {
            border-top: 1.6px solid #4B2E83 !important;

            border-bottom-color: transparent !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH MIDDLE CELL
        |--------------------------------------------------------------------------
        */

        .month-middle {
            border-top-color: transparent !important;

            border-bottom-color: transparent !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH LAST CELL
        |--------------------------------------------------------------------------
        */

        .month-last {
            border-top-color: transparent !important;

            border-bottom: 0.5px solid #4B2E83 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH SINGLE CELL
        |--------------------------------------------------------------------------
        */

        .month-only {
            border-top: 1.6px solid #4B2E83 !important;

            border-bottom: 0.5px solid #4B2E83 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MONTH VERTICAL LABEL
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
        | MONTH START LINE ACROSS TABLE
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
        | Same principle as MONTH:
        |
        | Week cells remain separate.
        | Internal borders disappear.
        | Visually the week looks like one vertical rowspan block.
        |--------------------------------------------------------------------------
        */

        .week {
            width: 42px;

            padding: 0 !important;

            text-align: center;

            vertical-align: middle !important;

            font-weight: bold;

            position: relative;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK FIRST CELL
        |--------------------------------------------------------------------------
        */

        .week-first {
            border-bottom-color: transparent !important;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK MIDDLE CELL
        |--------------------------------------------------------------------------
        */

        .week-middle {
            border-top-color: transparent !important;

            border-bottom-color: transparent !important;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK LAST CELL
        |--------------------------------------------------------------------------
        */

        .week-last {
            border-top-color: transparent !important;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK SINGLE CELL
        |--------------------------------------------------------------------------
        */

        .week-only {
            border-top: 0.5px solid #4B2E83 !important;

            border-bottom: 0.5px solid #4B2E83 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK VERTICAL TEXT
        |--------------------------------------------------------------------------
        |
        | Same appearance as month text.
        |--------------------------------------------------------------------------
        */

        .week-block-label {
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
        | WEEK BLOCK
        |--------------------------------------------------------------------------
        */

        .week-block {
            padding: 0 !important;

            text-align: center;

            vertical-align: middle !important;

            line-height: 1;
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
        | PROGRAMME GROUP HEADERS
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Long names wrap instead of entering another programme column.
        |--------------------------------------------------------------------------
        */

        .programme-group {
            width: 42px;

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
        | GIVE SECOND HEADER ROW ENOUGH SPACE
        |--------------------------------------------------------------------------
        */

        thead tr:nth-child(2) th {
            height: auto;

            min-height: 30px;

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



            {{-- ========================================================
                PROGRAMME GROUP NAMES
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

                    $monthDays = $month['days'];

                    $monthRowCount = count($monthDays);


                    /*
                    |--------------------------------------------------------------------------
                    | Place month text approximately in middle
                    |--------------------------------------------------------------------------
                    */

                    $monthLabelRow =
                        (int) floor(
                            ($monthRowCount - 1) / 2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | PRE-CALCULATE WEEK BLOCK MIDDLES
                    |--------------------------------------------------------------------------
                    |
                    | This allows the week label to behave like the month.
                    |
                    | Instead of writing Week 1 on the first row,
                    | we locate all rows belonging to that week's block and
                    | place the text approximately in the middle.
                    |--------------------------------------------------------------------------
                    */

                    $weekMeta = [];


                    foreach ($calendar['groups'] as $group) {


                        $currentBlockStart = null;

                        $currentBlockKey = null;


                        foreach ($monthDays as $rowIndex => $monthDay) {


                            $currentBlock =
                                $monthDay['week_values'][$group->id]
                                ?? null;


                            if (!$currentBlock) {

                                continue;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Build a unique key for the block
                            |--------------------------------------------------------------------------
                            */

                            $blockKey =
                                $currentBlock['full_label']
                                . '-'
                                . $rowIndex;


                            /*
                            |--------------------------------------------------------------------------
                            | Start of block
                            |--------------------------------------------------------------------------
                            */

                            if ($currentBlock['is_block_start']) {

                                $currentBlockStart = $rowIndex;


                                /*
                                |--------------------------------------------------------------------------
                                | Temporary key
                                |--------------------------------------------------------------------------
                                */

                                $currentBlockKey =
                                    $group->id
                                    . '-'
                                    . $rowIndex;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | End of block
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $currentBlock['is_block_end']
                                &&
                                $currentBlockStart !== null
                            ) {

                                $blockEnd =
                                    $rowIndex;


                                $blockMiddle =
                                    (int) floor(
                                        (
                                            $currentBlockStart
                                            +
                                            $blockEnd
                                        ) / 2
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | Store information for every row in this block
                                |--------------------------------------------------------------------------
                                */

                                for (
                                    $i = $currentBlockStart;
                                    $i <= $blockEnd;
                                    $i++
                                ) {

                                    $weekMeta[$group->id][$i] = [

                                        'start' =>
                                            $currentBlockStart,

                                        'end' =>
                                            $blockEnd,

                                        'middle' =>
                                            $blockMiddle,

                                        'label' =>
                                            $currentBlock['full_label'],

                                    ];

                                }


                                $currentBlockStart = null;

                                $currentBlockKey = null;

                            }

                        }

                    }

                @endphp



                @foreach ($monthDays as $dayIndex => $day)


                    @php

                        /*
                        |--------------------------------------------------------------------------
                        | MONTH CELL POSITION
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

                                $block =
                                    $day['week_values'][$group->id]
                                    ?? null;

                            @endphp



                            {{-- ================================================
                                EMPTY WEEK CELL
                            ================================================= --}}

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


                                @php

                                    /*
                                    |--------------------------------------------------------------------------
                                    | GET WEEK POSITION INFORMATION
                                    |--------------------------------------------------------------------------
                                    */

                                    $meta =
                                        $weekMeta[$group->id][$dayIndex]
                                        ?? null;



                                    /*
                                    |--------------------------------------------------------------------------
                                    | DEFAULT FROM ORIGINAL DATA
                                    |--------------------------------------------------------------------------
                                    */

                                    $weekIsFirst =
                                        $block['is_block_start'];


                                    $weekIsLast =
                                        $block['is_block_end'];


                                    /*
                                    |--------------------------------------------------------------------------
                                    | DETERMINE VISUAL WEEK CLASS
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        $weekIsFirst
                                        &&
                                        $weekIsLast
                                    ) {

                                        $weekClass =
                                            'week-only';

                                    }

                                    elseif ($weekIsFirst) {

                                        $weekClass =
                                            'week-first';

                                    }

                                    elseif ($weekIsLast) {

                                        $weekClass =
                                            'week-last';

                                    }

                                    else {

                                        $weekClass =
                                            'week-middle';

                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | WHERE SHOULD WEEK LABEL APPEAR?
                                    |--------------------------------------------------------------------------
                                    */

                                    $showWeekLabel =
                                        $meta
                                        &&
                                        (
                                            $dayIndex ===
                                            $meta['middle']
                                        );

                                @endphp



                                <td
                                    class="
                                        week

                                        week-block

                                        {{ $weekClass }}

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


                                    {{-- ============================================
                                        VERTICAL WEEK LABEL
                                    ============================================= --}}

                                    @if ($showWeekLabel)

                                        <span class="week-block-label">

                                            {{ $meta['label'] }}

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
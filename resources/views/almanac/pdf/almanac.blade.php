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
        | Month does NOT use rowspan.
        |
        | Every day has a month TD.
        | Internal borders disappear.
        |
        | Therefore DomPDF can safely break anywhere.
        |--------------------------------------------------------------------------
        */

        .month {
            width: 25px;

            padding: 0 !important;

            text-align: center;

            vertical-align: middle !important;

            position: relative;

            overflow: visible !important;

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
        | MONTH LABEL
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
        | IMPORTANT:
        |
        | Week now works exactly like month.
        |
        | NO rowspan.
        |
        | Every date has its own week TD.
        |
        | Internal horizontal borders disappear when cells belong to the
        | same week block.
        |
        | This prevents broken tables when a week crosses a PDF page.
        |--------------------------------------------------------------------------
        */

        .week {
            width: 36px;

            padding: 0 !important;

            text-align: center;

            vertical-align: middle !important;

            font-weight: bold;

            position: relative;

            overflow: visible !important;
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
        | WEEK LABEL HOLDER
        |--------------------------------------------------------------------------
        |
        | The holder itself stays in one row.
        |
        | The rotated label is wider than the cell height, so after rotation
        | it visually extends upward/downward over the continuous coloured
        | block.
        |--------------------------------------------------------------------------
        */

        .week-label-holder {
            position: relative;

            width: 100%;

            height: 9px;

            overflow: visible !important;

            text-align: center;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEK VERTICAL LABEL
        |--------------------------------------------------------------------------
        |
        | This is the important part.
        |
        | Width gives Week 1 / Week 10 / Week 18 enough physical length.
        |
        | After -90 degree rotation that width becomes the vertical height
        | of the visible text.
        |--------------------------------------------------------------------------
        */

        .week-label {
            display: inline-block;

            width: 45px;

            white-space: nowrap;

            text-align: center;

            font-size: 6.5pt;

            font-weight: bold;

            line-height: 9px;

            transform: rotate(-90deg);

            transform-origin: center center;

            margin-left: -5px;

            margin-right: -5px;
        }


        /*
        |--------------------------------------------------------------------------
        | PROGRAMME GROUP DIVIDER
        |--------------------------------------------------------------------------
        */

        .week-group-start {
            border-left: 1.8px solid #4B2E83 !important;
        }



        /*
        |--------------------------------------------------------------------------
        | PROGRAMME GROUP HEADER
        |--------------------------------------------------------------------------
        |
        | Long names MUST remain inside their own column.
        |--------------------------------------------------------------------------
        */

        .programme-group {
            width: 36px;

            text-align: center;

            vertical-align: middle !important;

            white-space: normal !important;

            word-wrap: break-word !important;

            overflow-wrap: break-word !important;

            word-break: break-word !important;

            line-height: 1.10;

            padding: 3px 2px !important;

            font-size: 5.2pt;
        }


        /*
        |--------------------------------------------------------------------------
        | PROGRAMME HEADER ROW
        |--------------------------------------------------------------------------
        */

        thead tr:nth-child(2) th {
            height: auto;

            min-height: 32px;

            white-space: normal !important;

            line-height: 1.10;
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


        .academic-event,
        .meeting-event {
            line-height: 1.15;
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
            BODY
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
                    | MONTH LABEL ROW
                    |--------------------------------------------------------------------------
                    */

                    $monthLabelRow =
                        (int) floor(
                            ($monthRowCount - 1) / 2
                        );



                    /*
                    |--------------------------------------------------------------------------
                    | WEEK BLOCK INFORMATION
                    |--------------------------------------------------------------------------
                    |
                    | NO ROWSPAN IS GENERATED.
                    |
                    | Instead we determine:
                    |
                    | first row
                    | middle row
                    | last row
                    |
                    | for every week block.
                    |--------------------------------------------------------------------------
                    */

                    $weekMeta = [];


                    foreach ($calendar['groups'] as $group) {


                        $currentStart = null;

                        $currentData = null;


                        foreach ($monthDays as $rowIndex => $monthDay) {


                            $block =
                                $monthDay['week_values'][$group->id]
                                ?? null;



                            /*
                            |--------------------------------------------------------------------------
                            | NO WEEK BLOCK
                            |--------------------------------------------------------------------------
                            */

                            if (!$block) {

                                continue;

                            }



                            /*
                            |--------------------------------------------------------------------------
                            | START
                            |--------------------------------------------------------------------------
                            */

                            if ($block['is_block_start']) {

                                $currentStart =
                                    $rowIndex;


                                $currentData =
                                    $block;

                            }



                            /*
                            |--------------------------------------------------------------------------
                            | END
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $block['is_block_end']
                                &&
                                $currentStart !== null
                            ) {


                                $currentEnd =
                                    $rowIndex;


                                /*
                                |--------------------------------------------------------------------------
                                | Middle row
                                |--------------------------------------------------------------------------
                                */

                                $middleRow =
                                    (int) floor(
                                        ($currentStart + $currentEnd) / 2
                                    );



                                /*
                                |--------------------------------------------------------------------------
                                | Register every row belonging to this week
                                |--------------------------------------------------------------------------
                                */

                                for (
                                    $i = $currentStart;
                                    $i <= $currentEnd;
                                    $i++
                                ) {


                                    $weekMeta[$group->id][$i] = [


                                        'start' =>
                                            $currentStart,


                                        'end' =>
                                            $currentEnd,


                                        'middle' =>
                                            $middleRow,


                                        'label' =>
                                            $currentData['full_label'],


                                        'background_color' =>
                                            $currentData['background_color'],


                                        'text_color' =>
                                            $currentData['text_color'],


                                    ];

                                }



                                /*
                                |--------------------------------------------------------------------------
                                | RESET
                                |--------------------------------------------------------------------------
                                */

                                $currentStart = null;

                                $currentData = null;

                            }

                        }



                        /*
                        |--------------------------------------------------------------------------
                        | WEEK CONTINUES BEYOND CURRENT MONTH
                        |--------------------------------------------------------------------------
                        |
                        | Close its visual block at the final day of this month.
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $currentStart !== null
                            &&
                            $currentData !== null
                        ) {


                            $currentEnd =
                                $monthRowCount - 1;


                            $middleRow =
                                (int) floor(
                                    ($currentStart + $currentEnd) / 2
                                );


                            for (
                                $i = $currentStart;
                                $i <= $currentEnd;
                                $i++
                            ) {


                                $weekMeta[$group->id][$i] = [


                                    'start' =>
                                        $currentStart,


                                    'end' =>
                                        $currentEnd,


                                    'middle' =>
                                        $middleRow,


                                    'label' =>
                                        $currentData['full_label'],


                                    'background_color' =>
                                        $currentData['background_color'],


                                    'text_color' =>
                                        $currentData['text_color'],


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
                        | MONTH CELL CLASS
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
                                | ORIGINAL WEEK BLOCK
                                |--------------------------------------------------------------------------
                                */

                                $block =
                                    $day['week_values'][$group->id]
                                    ?? null;



                                /*
                                |--------------------------------------------------------------------------
                                | CALCULATED WEEK INFORMATION
                                |--------------------------------------------------------------------------
                                */

                                $meta =
                                    $weekMeta[$group->id][$dayIndex]
                                    ?? null;



                                /*
                                |--------------------------------------------------------------------------
                                | DEFAULTS
                                |--------------------------------------------------------------------------
                                */

                                $weekClass =
                                    '';


                                $showWeekLabel =
                                    false;



                                /*
                                |--------------------------------------------------------------------------
                                | DETERMINE VISUAL POSITION
                                |--------------------------------------------------------------------------
                                */

                                if ($meta) {


                                    $weekStart =
                                        $meta['start'];


                                    $weekEnd =
                                        $meta['end'];



                                    if (
                                        $weekStart ===
                                        $weekEnd
                                    ) {


                                        $weekClass =
                                            'week-only';


                                    }

                                    elseif (
                                        $dayIndex ===
                                        $weekStart
                                    ) {


                                        $weekClass =
                                            'week-first';


                                    }

                                    elseif (
                                        $dayIndex ===
                                        $weekEnd
                                    ) {


                                        $weekClass =
                                            'week-last';


                                    }

                                    else {


                                        $weekClass =
                                            'week-middle';


                                    }



                                    /*
                                    |--------------------------------------------------------------------------
                                    | LABEL ONLY ON MIDDLE ROW
                                    |--------------------------------------------------------------------------
                                    */

                                    $showWeekLabel =
                                        $dayIndex ===
                                        $meta['middle'];


                                }

                            @endphp



                            {{-- ================================================
                                WEEK EXISTS
                            ================================================= --}}

                            @if ($block)


                                <td
                                    class="
                                        week

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

                                        -webkit-print-color-adjust: exact;

                                        print-color-adjust: exact;
                                    "
                                >



                                    @if (
                                        $showWeekLabel
                                        &&
                                        $meta
                                    )


                                        <div class="week-label-holder">


                                            <span class="week-label">

                                                {{ $meta['label'] }}

                                            </span>


                                        </div>


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
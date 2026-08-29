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
        | REPEATING UNIVERSITY HEADER
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
        | PAGE CONTAINER
        |--------------------------------------------------------------------------
        */

        .calendar-page {
            width: 100%;
        }

        .calendar-page + .calendar-page {
            page-break-before: always;
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

        tr {
            page-break-inside: avoid !important;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE HEADER
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
        | MONTH
        |--------------------------------------------------------------------------
        */

        .month {
            width: 25px;

            padding: 0 !important;

            text-align: center !important;

            vertical-align: middle !important;

            overflow: hidden;
        }

        .month-merged {
            text-align: center !important;
            vertical-align: middle !important;
        }

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
        | WEEK
        |--------------------------------------------------------------------------
        */

        .week {
            width: 36px;

            padding: 0 !important;

            text-align: center !important;

            vertical-align: middle !important;

            font-weight: bold;

            overflow: hidden;
        }

        .week-merged {
            text-align: center !important;

            vertical-align: middle !important;

            padding: 0 !important;

            line-height: 1;

            -webkit-print-color-adjust: exact;

            print-color-adjust: exact;
        }

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
        | PROGRAMME GROUP
        |--------------------------------------------------------------------------
        */

        .programme-group {
            width: 36px;

            text-align: center;

            vertical-align: middle !important;

            white-space: normal !important;

            overflow: hidden;

            overflow-wrap: anywhere;

            word-wrap: break-word;

            word-break: break-word;

            line-height: 1.10;

            padding: 3px 2px !important;

            font-size: 5.2pt;
        }

        .week-group-start {
            border-left: 1.8px solid #4B2E83 !important;
        }

        thead tr:nth-child(2) th {
            white-space: normal !important;

            line-height: 1.10;

            vertical-align: middle !important;
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
        | EVENTS
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
        | PAGE-LOCAL BLOCK SEPARATOR
        |--------------------------------------------------------------------------
        */

        .block-start td,
        .block-start th {
            border-top-width: 0.8px;
        }

    </style>
</head>


<body>


{{-- ========================================================================
    FIXED HEADER
=========================================================================== --}}

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



{{-- ========================================================================
    PREPARE PAGE DATA
=========================================================================== --}}

@php

    /*
    |--------------------------------------------------------------------------
    | STEP 1: FLATTEN ALL MONTHS INTO ONE CHRONOLOGICAL DAY ARRAY
    |--------------------------------------------------------------------------
    */

    $allRows = [];

    foreach ($calendar['months'] as $monthIndex => $month) {

        foreach ($month['days'] as $dayIndex => $day) {

            /*
            |--------------------------------------------------------------------------
            | Estimate how tall this row may become.
            |--------------------------------------------------------------------------
            |
            | Normal days = 1 unit.
            |
            | Days containing long Academic / Meeting text consume more units.
            |
            | This helps avoid DomPDF deciding to move several rows unexpectedly.
            |--------------------------------------------------------------------------
            */

            $academicTextLength = 0;

            foreach ($day['academic_events'] as $event) {
                $academicTextLength += mb_strlen(strip_tags($event['text']));
            }


            $meetingTextLength = 0;

            foreach ($day['meeting_events'] as $event) {
                $meetingTextLength += mb_strlen(strip_tags($event['text']));
            }


            $longestText =
                max(
                    $academicTextLength,
                    $meetingTextLength
                );


            /*
            |--------------------------------------------------------------------------
            | ROW WEIGHT
            |--------------------------------------------------------------------------
            */

            $rowWeight = 1;


            if ($longestText > 70) {
                $rowWeight = 2;
            }


            if ($longestText > 160) {
                $rowWeight = 3;
            }


            if ($longestText > 260) {
                $rowWeight = 4;
            }


            /*
            |--------------------------------------------------------------------------
            | Add some weight when several events are on the same date
            |--------------------------------------------------------------------------
            */

            $eventCount =
                count($day['academic_events'])
                +
                count($day['meeting_events']);


            if ($eventCount >= 3) {
                $rowWeight++;
            }


            /*
            |--------------------------------------------------------------------------
            | Create flat row
            |--------------------------------------------------------------------------
            */

            $allRows[] = [

                'month_index' =>
                    $monthIndex,

                'month_label' =>
                    $month['label'],

                'day_index' =>
                    $dayIndex,

                'day' =>
                    $day,

                'weight' =>
                    max(1, $rowWeight),

            ];

        }

    }



    /*
    |--------------------------------------------------------------------------
    | STEP 2: SPLIT INTO PHYSICAL PDF PAGES
    |--------------------------------------------------------------------------
    |
    | This is the important change.
    |
    | We decide approximately how much content goes on one PDF page BEFORE
    | creating rowspan cells.
    |
    | Because rowspan is calculated AFTER pagination, a rowspan can NEVER
    | cross one of our intentional page breaks.
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Normal A4-landscape capacity.
    |--------------------------------------------------------------------------
    |
    | Adjust this one number if necessary:
    |
    | 28 = fewer rows, safer
    | 30 = balanced
    | 32 = denser
    |
    */

    $maxPageUnits = 30;


    $pages = [];

    $currentPage = [];

    $currentUnits = 0;


    foreach ($allRows as $row) {


        /*
        |--------------------------------------------------------------------------
        | Start another page before row exceeds capacity
        |--------------------------------------------------------------------------
        */

        if (
            !empty($currentPage)
            &&
            (
                $currentUnits + $row['weight']
                >
                $maxPageUnits
            )
        ) {

            $pages[] =
                $currentPage;


            $currentPage =
                [];


            $currentUnits =
                0;

        }


        $currentPage[] =
            $row;


        $currentUnits +=
            $row['weight'];

    }


    /*
    |--------------------------------------------------------------------------
    | Final page
    |--------------------------------------------------------------------------
    */

    if (!empty($currentPage)) {

        $pages[] =
            $currentPage;

    }

@endphp



{{-- ========================================================================
    RENDER EACH PRE-CALCULATED PDF PAGE
=========================================================================== --}}

@foreach ($pages as $pageIndex => $pageRows)


    @php

        /*
        |--------------------------------------------------------------------------
        | PAGE-LOCAL MONTH SPANS
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | PAGE 1:
        |
        | Oct 01
        | ...
        | Oct 29
        |
        | rowspan = only those October rows on PAGE 1
        |
        |
        | PAGE 2:
        |
        | Oct 30
        | Oct 31
        |
        | rowspan = 2
        |
        | Then November gets its own rowspan.
        |--------------------------------------------------------------------------
        */

        $monthSpans = [];

        $pageRowCount =
            count($pageRows);


        $position = 0;


        while ($position < $pageRowCount) {


            $monthLabel =
                $pageRows[$position]['month_label'];


            $start =
                $position;


            $end =
                $position;


            while (
                ($end + 1) < $pageRowCount
                &&
                $pageRows[$end + 1]['month_label']
                    ===
                $monthLabel
            ) {

                $end++;

            }


            $monthSpans[$start] = [

                'rowspan' =>
                    ($end - $start) + 1,

                'label' =>
                    $monthLabel,

            ];


            for (
                $skipPosition = $start + 1;
                $skipPosition <= $end;
                $skipPosition++
            ) {

                $monthSpans[$skipPosition] = [

                    'skip' => true,

                ];

            }


            $position =
                $end + 1;

        }



        /*
        |--------------------------------------------------------------------------
        | PAGE-LOCAL WEEK SPANS
        |--------------------------------------------------------------------------
        |
        | Each programme group is calculated independently.
        |
        | Most importantly:
        |
        | Nothing here can cross to another $pageRows array.
        |--------------------------------------------------------------------------
        */

        $weekSpans = [];


        foreach ($calendar['groups'] as $group) {


            $rowPosition = 0;


            while ($rowPosition < $pageRowCount) {


                $day =
                    $pageRows[$rowPosition]['day'];


                $block =
                    $day['week_values'][$group->id]
                    ?? null;



                /*
                |--------------------------------------------------------------------------
                | No week
                |--------------------------------------------------------------------------
                */

                if (!$block) {

                    $rowPosition++;

                    continue;

                }



                /*
                |--------------------------------------------------------------------------
                | Build signature identifying this contiguous week block
                |--------------------------------------------------------------------------
                */

                $signature =
                    ($block['full_label'] ?? '')
                    . '|'
                    . ($block['background_color'] ?? '')
                    . '|'
                    . ($block['text_color'] ?? '');



                $start =
                    $rowPosition;


                $end =
                    $rowPosition;



                /*
                |--------------------------------------------------------------------------
                | Continue while next row has same week block
                |--------------------------------------------------------------------------
                */

                while (($end + 1) < $pageRowCount) {


                    $nextDay =
                        $pageRows[$end + 1]['day'];


                    $nextBlock =
                        $nextDay['week_values'][$group->id]
                        ?? null;


                    if (!$nextBlock) {

                        break;

                    }


                    $nextSignature =
                        ($nextBlock['full_label'] ?? '')
                        . '|'
                        . ($nextBlock['background_color'] ?? '')
                        . '|'
                        . ($nextBlock['text_color'] ?? '');


                    if ($nextSignature !== $signature) {

                        break;

                    }


                    $end++;

                }



                /*
                |--------------------------------------------------------------------------
                | PAGE-LOCAL SPAN
                |--------------------------------------------------------------------------
                */

                $weekSpans[$group->id][$start] = [

                    'rowspan' =>
                        ($end - $start) + 1,

                    'label' =>
                        $block['full_label'],

                    'background_color' =>
                        $block['background_color'],

                    'text_color' =>
                        $block['text_color'],

                ];



                /*
                |--------------------------------------------------------------------------
                | Mark covered cells
                |--------------------------------------------------------------------------
                */

                for (
                    $skipPosition = $start + 1;
                    $skipPosition <= $end;
                    $skipPosition++
                ) {

                    $weekSpans[$group->id][$skipPosition] = [

                        'skip' => true,

                    ];

                }


                $rowPosition =
                    $end + 1;

            }

        }

    @endphp



    <div class="calendar-page">


        <table>


            {{-- ============================================================
                HEADER
            ============================================================= --}}

            <thead>


                <tr>


                    <th
                        rowspan="2"
                        class="month"
                    >
                        Months
                    </th>


                    <th
                        colspan="{{ $calendar['groups']->count() }}"
                    >
                        Week Number
                    </th>


                    <th
                        rowspan="2"
                        class="date"
                    >
                        Dates
                    </th>


                    <th
                        rowspan="2"
                        class="event"
                    >
                        Academic Calendar
                    </th>


                    <th
                        rowspan="2"
                        class="event"
                    >
                        Meeting/Activities Calendar
                    </th>


                </tr>



                <tr>


                    @foreach ($calendar['groups'] as $groupIndex => $group)


                        <th
                            class="
                                week
                                programme-group

                                {{ $groupIndex > 0
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


                @foreach ($pageRows as $rowIndex => $row)


                    @php

                        $day =
                            $row['day'];


                        $monthSpan =
                            $monthSpans[$rowIndex]
                            ?? null;

                    @endphp



                    <tr>


                        {{-- ====================================================
                            MONTH
                        ===================================================== --}}


                        @if (
                            $monthSpan
                            &&
                            !($monthSpan['skip'] ?? false)
                        )


                            <td
                                rowspan="{{ $monthSpan['rowspan'] }}"

                                class="
                                    month
                                    month-merged
                                "
                            >


                                <span class="month-label">

                                    {{ $monthSpan['label'] }}

                                </span>


                            </td>


                        @endif



                        {{-- ====================================================
                            WEEK COLUMNS
                        ===================================================== --}}


                        @foreach ($calendar['groups'] as $groupIndex => $group)


                            @php

                                $weekSpan =
                                    $weekSpans[$group->id][$rowIndex]
                                    ?? null;


                                $originalBlock =
                                    $day['week_values'][$group->id]
                                    ?? null;

                            @endphp



                            {{-- ================================================
                                COVERED BY PREVIOUS ROWSPAN
                            ================================================= --}}


                            @if (
                                $weekSpan
                                &&
                                ($weekSpan['skip'] ?? false)
                            )


                                {{-- No TD --}}



                            {{-- ================================================
                                START OF PAGE-LOCAL WEEK BLOCK
                            ================================================= --}}


                            @elseif (
                                $weekSpan
                                &&
                                isset($weekSpan['rowspan'])
                            )


                                <td
                                    rowspan="{{ $weekSpan['rowspan'] }}"

                                    class="
                                        week
                                        week-merged

                                        {{ $groupIndex > 0
                                            ? 'week-group-start'
                                            : ''
                                        }}
                                    "

                                    style="
                                        background:
                                            {{ $weekSpan['background_color'] }};

                                        color:
                                            {{ $weekSpan['text_color'] }};

                                        -webkit-print-color-adjust: exact;

                                        print-color-adjust: exact;
                                    "
                                >


                                    <span class="week-label">

                                        {{ $weekSpan['label'] }}

                                    </span>


                                </td>



                            {{-- ================================================
                                EMPTY
                            ================================================= --}}


                            @else


                                <td
                                    class="
                                        week

                                        {{ $groupIndex > 0
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


            </tbody>


        </table>


    </div>


@endforeach


</body>

</html>
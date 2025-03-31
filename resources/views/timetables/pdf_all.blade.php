<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Examination Timetables - All Programs</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            margin: 0; 
            padding: 0; 
        }
        .header { 
            background-color: #4B2E83; 
            color: white; 
            padding: 10px; 
            text-align: center; 
            border-bottom: 5px solid #e6f3ff; 
            position: fixed; 
            top: 0; 
            width: 100%; 
            height: 80px; 
        }
        .header h1 { margin: 0; font-size: 20px; }
        .header h2 { margin: 5px 0; font-size: 16px; }
        .header h3 { margin: 5px 0; font-size: 12px; }
        .content { 
            margin-top: 100px; 
            padding-bottom: 20px; /* Space at bottom of page */
        }
        .program-title { 
            color: #4B2E83; 
            font-size: 10px; 
            position: absolute; 
            left: 10px; 
            top: -15px; 
        }
        h4 { 
            color: #4B2E83; 
            text-align: center; 
            margin: 10px 0; 
            font-size: 12px; 
            page-break-after: avoid; /* Ensure h4 stays with table */
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            page-break-inside: avoid; /* Prevent table splitting */
            position: relative; 
        }
        th, td { 
            border: 1px solid #4B2E83; 
            padding: 2px; 
            text-align: center; 
            font-size: 7px; 
        }
        th { background-color: #4B2E83; color: white; }
        .day-header { background-color: #e6f3ff; color: #4B2E83; }
        .sub-header { background-color: #f9f9f9; font-weight: bold; color: #4B2E83; }
        .time-slot { background-color: #fff3e6; }
        .page-break { 
            page-break-before: always; 
            margin-top: 100px; /* Ensure space for header on new page */
        }
        hr { margin: 1px 0; border: 0; border-top: 1px solid #4B2E83; }
        /* Ensure thead repeats on multi-page tables */
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
    </style>
</head>
<body>
    <div class="header">
        <h1>St John's University of Tanzania</h1>
        @php
            $firstProgramData = $groupedTimetables->first();
            $timetableType = ucfirst($firstProgramData['timetable_type'] ?? 'Examination');
        @endphp
        <h2>University {{ $timetableType }} Timetable</h2>
        <h3>{{ $firstProgramData['semester'] ?? 'Semester I 2024/2025' }}</h3>
    </div>

    <div class="content">
        @forelse ($groupedTimetables as $program => $data)
            <!-- Week 1 Table -->
            <div style="page-break-inside: avoid;">
                <h4>Week 1 (Feb 10 - Feb 14)</h4>
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">Time Slot</th>
                            <th rowspan="2">Year</th>
                            @foreach ($week1Days as $day)
                                <th colspan="{{ $data['faculties']->count() }}" class="day-header">
                                    {{ \Carbon\Carbon::parse($day)->format('l (M d)') }}
                                </th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($week1Days as $day)
                                @foreach ($data['faculties'] as $faculty)
                                    <th class="sub-header">{{ $faculty }}</th>
                                @endforeach
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($timeSlots as $slotName => $timeRange)
                            @php
                                [$startTime, $endTime] = explode('-', $timeRange);
                            @endphp
                            @foreach ($yearsList as $yearIndex => $year)
                                <tr>
                                    @if ($yearIndex === 0)
                                        <td rowspan="4" class="time-slot">
                                            {{ substr($startTime, 0, 5) }}-{{ substr($endTime, 0, 5) }} ({{ $slotName }})
                                        </td>
                                    @endif
                                    <td>{{ $year }}</td>
                                    @foreach ($week1Days as $day)
                                        @foreach ($data['faculties'] as $faculty)
                                            @php
                                                $entry = $data['timetables']->firstWhere(function ($item) use ($day, $faculty, $year, $startTime, $endTime) {
                                                    return $item->exam_date == $day &&
                                                           $item->faculty == $faculty &&
                                                           $item->year == $year &&
                                                           $item->start_time == $startTime &&
                                                           $item->end_time == $endTime;
                                                });
                                            @endphp
                                            <td>
                                                @if ($entry)
                                                    {{ $entry->course_code }} <br><hr> {{ $entry->venue }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
                <div class="program-title">{{ $program }}</div>
            </div>

            <!-- Week 2 Table -->
            @if ($week2Days->isNotEmpty())
                <div style="page-break-inside: avoid;">
                    <h4>Week 2 (Feb 17 - Feb 21)</h4>
                    <table>
                        <thead>
                            <tr>
                                <th rowspan="2">Time Slot</th>
                                <th rowspan="2">Year</th>
                                @foreach ($week2Days as $day)
                                    <th colspan="{{ $data['faculties']->count() }}" class="day-header">
                                        {{ \Carbon\Carbon::parse($day)->format('l (M d)') }}
                                    </th>
                                @endforeach
                        </tr>
                        <tr>
                            @foreach ($week2Days as $day)
                                @foreach ($data['faculties'] as $faculty)
                                    <th class="sub-header">{{ $faculty }}</th>
                                @endforeach
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                            @foreach ($timeSlots as $slotName => $timeRange)
                                @php
                                    [$startTime, $endTime] = explode('-', $timeRange);
                                @endphp
                                @foreach ($yearsList as $yearIndex => $year)
                                    <tr>
                                        @if ($yearIndex === 0)
                                            <td rowspan="4" class="time-slot">
                                                {{ substr($startTime, 0, 5) }}-{{ substr($endTime, 0, 5) }} ({{ $slotName }})
                                            </td>
                                        @endif
                                        <td>{{ $year }}</td>
                                        @foreach ($week2Days as $day)
                                            @foreach ($data['faculties'] as $faculty)
                                                @php
                                                    $entry = $data['timetables']->firstWhere(function ($item) use ($day, $faculty, $year, $startTime, $endTime) {
                                                        return $item->exam_date == $day &&
                                                               $item->faculty == $faculty &&
                                                               $item->year == $year &&
                                                               $item->start_time == $startTime &&
                                                               $item->end_time == $endTime;
                                                    });
                                                @endphp
                                                <td>
                                                    @if ($entry)
                                                        {{ $entry->course_code }} <br><hr> {{ $entry->venue }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (!$loop->last)
                <div class="page-break"></div>
            @endif
        @empty
            <p style="text-align: center;">No timetables found.</p>
        @endforelse
    </div>
</body>
</html>
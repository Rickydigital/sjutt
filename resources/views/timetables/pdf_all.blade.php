<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Examination Timetables - All Programs</title>
    <style>
        h1 { text-align: center; font-size: 16px; color: #4B2E83; margin: 5px 0; }
        h2 { text-align: center; font-size: 14px; color: #4B2E83; margin: 5px 0; }
        h3 { text-align: center; font-size: 12px; color: #4B2E83; margin: 5px 0; }
        h4 { color: #4B2E83; text-align: center; margin: 10px 0; font-size: 12px; page-break-after: avoid; }
        .program-title { text-align: center; font-size: 14px; font-weight: bold; margin: 20px 0 10px; color: #4B2E83; page-break-before: avoid; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 7px; }
        th, td { border: 1px solid #4B2E83; padding: 2px; text-align: center; }
        th { background-color: #4B2E83; color: white; }
        .day-header { background-color: #e6f3ff; color: #4B2E83; }
        .sub-header { background-color: #f9f9f9; font-weight: bold; color: #4B2E83; }
        .page-break { page-break-before: always; margin-top: 80px; }
        hr { margin: 1px 0; border: 0; border-top: 1px solid #4B2E83; }
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        table.summary { font-size: 10px; margin-bottom: 20px; width: 100%; }
        table.summary th, table.summary td { padding: 5px; border: 1px solid #4B2E83; text-align: left; }
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
        <!-- Summary Table -->
        <h2>Examination Timetable Summary</h2>
        <table class="summary">
            <thead>
                <tr>
                    <th>Program</th>
                    <th>Exam Dates</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupedTimetables as $program => $data)
                    <tr>
                        <td>{{ $program }}</td>
                        <td>{{ $data['date_range'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="page-break"></div>

        <!-- Detailed Timetables -->
        @forelse ($groupedTimetables as $program => $data)
            <div class="program-title">{{ $program }}</div>
            @foreach ($timeSlots as $slotName => $timeRange)
                @php
                    [$startTime, $endTime] = explode('-', $timeRange);
                @endphp
                <div style="page-break-inside: avoid;">
                    <h4>{{ substr($startTime, 0, 5) }}-{{ substr($endTime, 0, 5) }} ({{ $slotName }})</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Year</th>
                                @foreach ($week1Days as $day)
                                    <th colspan="{{ $data['faculties']->count() }}" class="day-header">
                                        {{ \Carbon\Carbon::parse($day)->format('l (M d)') }}
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                <th></th>
                                @foreach ($week1Days as $day)
                                    @foreach ($data['faculties'] as $faculty)
                                        <th class="sub-header">{{ $faculty->name }}</th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($yearsList as $year)
                                <tr>
                                    <td>{{ $year->year }}</td>
                                    @foreach ($week1Days as $day)
                                        @foreach ($data['faculties'] as $faculty)
                                            @php
                                                $entry = $data['timetables']->firstWhere(function ($item) use ($day, $faculty, $year, $startTime, $endTime) {
                                                    return $item->exam_date == $day &&
                                                           $item->faculty_id == $faculty->id &&
                                                           $item->year_id == $year->id &&
                                                           $item->start_time == $startTime &&
                                                           $item->end_time == $endTime;
                                                });
                                            @endphp
                                            <td>
                                                @if ($entry)
                                                    {{ $entry->course_code }} <br><hr> {{ $entry->venue ? $entry->venue->name : 'N/A' }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

            <!-- Week 2 Tables -->
            @if ($week2Days->isNotEmpty())
                @foreach ($timeSlots as $slotName => $timeRange)
                    @php
                        [$startTime, $endTime] = explode('-', $timeRange);
                    @endphp
                    <div style="page-break-inside: avoid;">
                        <h4>{{ substr($startTime, 0, 5) }}-{{ substr($endTime, 0, 5) }} ({{ $slotName }})</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    @foreach ($week2Days as $day)
                                        <th colspan="{{ $data['faculties']->count() }}" class="day-header">
                                            {{ \Carbon\Carbon::parse($day)->format('l (M d)') }}
                                        </th>
                                    @endforeach
                                </tr>
                                <tr>
                                    <th></th>
                                    @foreach ($week2Days as $day)
                                        @foreach ($data['faculties'] as $faculty)
                                            <th class="sub-header">{{ $faculty->name }}</th>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($yearsList as $year)
                                    <tr>
                                        <td>{{ $year->year }}</td>
                                        @foreach ($week2Days as $day)
                                            @foreach ($data['faculties'] as $faculty)
                                                @php
                                                    $entry = $data['timetables']->firstWhere(function ($item) use ($day, $faculty, $year, $startTime, $endTime) {
                                                        return $item->exam_date == $day &&
                                                               $item->faculty_id == $faculty->id &&
                                                               $item->year_id == $year->id &&
                                                               $item->start_time == $startTime &&
                                                               $item->end_time == $endTime;
                                                    });
                                                @endphp
                                                <td>
                                                    @if ($entry)
                                                        {{ $entry->course_code }} <br><hr> {{ $entry->venue ? $entry->venue->name : 'N/A' }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecture Timetable</title>
    <style>
        h1, h2 { text-align: center; }
        h1 { color: #4B2E83; font-weight: bold; }
        h3 { text-align: left; color: #4B2E83; font-weight: bold; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 2px solid #4B2E83; padding: 5px; text-align: center; vertical-align: middle; }
        th { background-color: #4B2E83; color: white; }
        .header-container { background-color: #f8f9fa; border-bottom: 2px solid #4B2E83; padding: 10px; margin-bottom: 20px; }
        .page { page-break-after: always; }
        .no-break { page-break-after: auto; }
        p { text-align: center; }
        /* New styles for side-by-side sessions */
        .session-container {
            display: flex;
            justify-content: space-between; /* Space out sessions evenly */
            flex-wrap: wrap; /* Allow wrapping if too many sessions */
            padding: 5px;
        }
        .session {
            background: linear-gradient(135deg, #e2e8f0, #f8f9fa);
            padding: 5px;
            margin: 2px;
            display: inline-block; /* Ensure proper alignment */
            vertical-align: top;
            width: auto; /* Dynamic width based on content */
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>ST JOHN'S UNIVERSITY OF TANZANIA</h1>
    <h2>Lectures Timetable for ACADEMIC Year 2024/2025 Semester II FIRST DRAFT</h2>

    @if ($faculties->isEmpty())
        <p>No timetable data available.</p>
    @else
        @foreach ($faculties->chunk(2) as $chunk)
            <div class="page">
                @foreach ($chunk as $faculty)
                    @php
                        $activitiesByDay = $faculty->timetables->groupBy('day');
                        $occupiedUntil = array_fill_keys($days, -1);
                    @endphp
                    <div class="header-container">
                        <h3>{{ $faculty->name }}</h3>
                    </div>
                    <table>
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Time</th>
                                @foreach ($days as $day)
                                    <th>{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($timeSlots as $i => $slotStart)
                                <tr>
                                    <td>{{ $slotStart }}-{{ date('H:i', strtotime($slotStart) + 3600) }}</td>
                                    @foreach ($days as $day)
                                        @if ($i > $occupiedUntil[$day])
                                            @php
                                                $slotEnd = date('H:i', strtotime($slotStart) + 3600);
                                                $slotStartTime = strtotime($slotStart);
                                                $slotEndTime = strtotime($slotEnd);
                                                $matchingActivities = [];
                                                foreach ($activitiesByDay[$day] ?? [] as $act) {
                                                    $actStart = strtotime($act->time_start);
                                                    $actEnd = strtotime($act->time_end);
                                                    $isMatch = ($actStart <= $slotStartTime && $actEnd > $slotStartTime);
                                                    if ($isMatch) {
                                                        $matchingActivities[] = $act;
                                                    }
                                                }
                                                // Calculate rowspan based on the maximum duration of all matching activities
                                                $maxSpan = 1;
                                                foreach ($matchingActivities as $act) {
                                                    $startTime = strtotime($act->time_start);
                                                    $endTime = strtotime($act->time_end);
                                                    $span = ceil(($endTime - $startTime) / 3600);
                                                    $maxSpan = max($maxSpan, $span);
                                                }
                                                $occupiedUntil[$day] = $i + $maxSpan - 1;
                                            @endphp
                                            @if (!empty($matchingActivities))
                                                <td rowspan="{{ $maxSpan }}">
                                                    <div class="session-container">
                                                        @foreach ($matchingActivities as $activity)
                                                            <div class="session">
                                                                <strong>{{ $activity->course_code }}</strong> <br>
                                                                {{ $activity->group_selection }} <br>
                                                                {{ $activity->activity }} <br>
                                                                {{ $activity->venue->name }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            @else
                                                <td> </td>
                                            @endif
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p>For any changes Consult Faculty/School Administrators</p>
                @endforeach
            </div>
        @endforeach
    @endif
</body>
</html>
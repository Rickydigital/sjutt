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
        .session {
            background: linear-gradient(135deg, #e2e8f0, #f8f9fa);
            padding: 5px;
            margin: 2px 0;
            text-align: center;
            box-sizing: border-box;
            font-size: 11px; /* Consistent font size for readability */
            line-height: 1.4;
        }
        @media print {
            .page { page-break-after: always; }
            .session {
                font-size: 10px; /* Slightly smaller for print, but still readable */
            }
        }
    </style>
</head>
<body>
    <h1>ST JOHN'S UNIVERSITY OF TANZANIA</h1>
    <h2>Lectures Timetable for ACADEMIC Year 2024/2025 Semester II FIRST DRAFT</h2>

    @if ($faculties->isEmpty())
        <p>No timetable data available.</p>
    @else
        @foreach ($faculties as $faculty)
            <div class="page">
                @php
                    $activitiesByDay = $faculty->timetables->groupBy('day');
                    $occupiedUntil = array_fill_keys($days, -1);
                    // Group activities by time slot and day for easier row management
                    $activitiesByTimeAndDay = [];
                    foreach ($activitiesByDay as $day => $activities) {
                        foreach ($activities as $act) {
                            $startTime = strtotime($act->time_start);
                            // Find the time slot index
                            $slotIndex = -1;
                            foreach ($timeSlots as $i => $slotStart) {
                                if (strtotime($slotStart) <= $startTime && $startTime < strtotime($slotStart) + 3600) {
                                    $slotIndex = $i;
                                    break;
                                }
                            }
                            if ($slotIndex >= 0) {
                                $activitiesByTimeAndDay[$slotIndex][$day][] = $act;
                            }
                        }
                    }
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
                            @php
                                $slotEnd = date('H:i', strtotime($slotStart) + 3600);
                                $maxActivities = 1; // Default to 1 row per time slot
                                // Calculate the maximum number of activities in this time slot across all days
                                foreach ($days as $day) {
                                    $activities = $activitiesByTimeAndDay[$i][$day] ?? [];
                                    $maxActivities = max($maxActivities, count($activities));
                                }
                            @endphp
                            <!-- Generate rows based on the maximum number of activities -->
                            @for ($row = 0; $row < $maxActivities; $row++)
                                <tr>
                                    @if ($row === 0)
                                        <td rowspan="{{ $maxActivities }}">{{ $slotStart }}-{{ $slotEnd }}</td>
                                    @endif
                                    @foreach ($days as $day)
                                        @if ($i > $occupiedUntil[$day])
                                            @php
                                                $activities = $activitiesByTimeAndDay[$i][$day] ?? [];
                                                $activity = $activities[$row] ?? null;
                                                if ($activity) {
                                                    $startTime = strtotime($activity->time_start);
                                                    $endTime = strtotime($activity->time_end);
                                                    $span = ceil(($endTime - $startTime) / 3600);
                                                    $occupiedUntil[$day] = $i + $span - 1;
                                                }
                                            @endphp
                                            @if ($activity)
                                                <td rowspan="{{ $span }}">
                                                    <div class="session">
                                                        <strong>{{ $activity->course_code }}</strong><br>
                                                        {{ $activity->group_selection }}<br>
                                                        {{ $activity->activity }}<br>
                                                        {{ $activity->venue->name }}
                                                    </div>
                                                </td>
                                            @else
                                                <td> </td>
                                            @endif
                                        @endif
                                    @endforeach
                                </tr>
                            @endfor
                        @endforeach
                    </tbody>
                </table>
                <p>For any changes Consult Faculty/School Administrators</p>
            </div>
        @endforeach
    @endif
</body>
</html>
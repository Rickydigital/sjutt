<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $venue->name }} - Timetable</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; }
        h1, h2 { text-align: center; margin: 8px 0; }
        h1 { color: #4B2E83; font-weight: bold; font-size: 18pt; }
        h2 { color: #4B2E83; font-size: 14pt; }

        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 2px solid #4B2E83; padding: 5px; text-align: center; vertical-align: middle; }
        th { background-color: #4B2E83; color: white; font-weight: bold; }

        .header-container {
            background-color: #f8f9fa;
            border-bottom: 2px solid #4B2E83;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .venue-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .venue-name {
            margin: 0;
            color: #4B2E83;
            font-size: 18pt;
            font-weight: bold;
        }
        .logo {
            height: 32px;
            width: auto;
            opacity: 0.95;
        }

        .time-header { background-color: #4B2E83 !important; color: white !important; font-weight: bold; }
        .time-cell { font-size: 9pt; color: #555; font-weight: 600; }

        .session {
            background: linear-gradient(135deg, #e2e8f0, #f8f9fa);
            padding: 4px 2px;
            margin: 1px;
            text-align: center;
            font-size: 8px;
            line-height: 1.2;
            border-radius: 3px;
        }
        .session strong {
            font-size: 8.5pt;
            display: block;
            color: #4B2E83;
        }

        .session-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3px;
            width: 100%;
            min-height: 60px;
        }

        .chapel {
            background: #4B2E83 !important;
            color: white;
            font-weight: bold;
            font-size: 7.5pt;
            padding: 3px 2px;
            border-radius: 3px;
            line-height: 1.1;
            text-align: center;
        }

        p.footer {
            text-align: center;
            margin: 15px 0;
            font-style: italic;
            color: #666;
            font-size: 10pt;
        }

        @media print {
            .session { font-size: 7px; }
            .logo { height: 28px; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body>
    <h1>ST JOHN'S UNIVERSITY OF TANZANIA</h1>
    <h2>Venue Timetable for Academic Year {{ $timetableSemester->academic_year }}</h2>
    <h2>{{ $timetableSemester->semester->name }}</h2>

    <div class="header-container">
        <div class="venue-header">
            <h3 class="venue-name">{{ $venue->name }}</h3>
            <img src="{{ public_path('images/logo.png') }}" alt="SJUT" class="logo">
        </div>
    </div>

    @php
        $activitiesByDay = $timetables->groupBy('day');
        $occupiedUntil = array_fill_keys($days, -1);
        $matrix = [];

        foreach ($activitiesByDay as $day => $acts) {
            foreach ($acts as $act) {
                $start = strtotime($act->time_start);
                $slot = -1;
                foreach ($timeSlots as $i => $ts) {
                    if (strtotime($ts) <= $start && $start < strtotime($ts) + 3600) {
                        $slot = $i;
                        break;
                    }
                }
                if ($slot >= 0) {
                    $matrix[$slot][$day][] = $act;
                }
            }
        }

        $chapelTimes = ['Tuesday' => '10:00', 'Friday' => '12:00'];
    @endphp

    <table>
        <thead>
            <tr>
                <th class="time-header">Time</th>
                @foreach ($days as $day)
                    <th>{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($timeSlots as $i => $slotStart)
                @php $slotEnd = date('H:i', strtotime($slotStart) + 3600); @endphp
                <tr>
                    <td class="time-cell">{{ $slotStart }}-{{ $slotEnd }}</td>

                    @foreach ($days as $day)
                        @if ($i > $occupiedUntil[$day])
                            @php
                                $isChapel = isset($chapelTimes[$day]) && $slotStart === $chapelTimes[$day];
                                $activities = $matrix[$i][$day] ?? [];
                                $maxSpan = 1;

                                if ($isChapel) {
                                    $maxSpan = 1;
                                    $occupiedUntil[$day] = $i;
                                } elseif ($activities) {
                                    foreach ($activities as $a) {
                                        $span = ceil((strtotime($a->time_end) - strtotime($a->time_start)) / 3600);
                                        $maxSpan = max($maxSpan, $span);
                                    }
                                    $occupiedUntil[$day] = $i + $maxSpan - 1;
                                }
                            @endphp

                            <td rowspan="{{ $maxSpan }}">
                                @if ($isChapel)
                                    <div class="chapel">
                                        Community<br>Chapel
                                    </div>
                                @elseif ($activities)
                                    <div class="session-container">
                                        @php
                                            $byCourse = [];
                                            foreach ($activities as $a) {
                                                $code = $a->course_code;
                                                $byCourse[$code]['faculty'] = $a->faculty->name;
                                                $byCourse[$code]['groups'][] = trim($a->group_selection);
                                                $byCourse[$code]['activity'] = $a->activity;
                                            }

                                            $merged = [];
                                            foreach ($byCourse as $code => $data) {
                                                $groups = array_unique($data['groups']);
                                                sort($groups);
                                                $merged[] = (object)[
                                                    'course'   => $code,
                                                    'faculty'  => $data['faculty'],
                                                    'groupStr' => implode(', ', $groups),
                                                    'activity' => $data['activity'],
                                                ];
                                            }
                                        @endphp

                                        @foreach ($merged as $m)
                                            <div class="session">
                                                <strong>{{ $m->course }}</strong><br>
                                                {{ $m->faculty }}<br>
                                                {{ $m->groupStr }}<br>
                                                {{ $m->activity }}
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    &nbsp;
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    {{--  <p class="footer">
        For any changes or room booking conflicts, consult the Timetable Office.
    </p>  --}}
</body>
</html>
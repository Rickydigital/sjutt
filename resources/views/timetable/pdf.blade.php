<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecture Timetable</title>
    <style>
        /* === FIXED REPEATING HEADER (ON EVERY PAGE) === */
        .print-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
            padding: 10px 0;
            background: white;
            border-bottom: 1.5px solid #4B2E83;
            font-family: Arial, sans-serif;
            z-index: 1000;
            page-break-after: avoid;
        }
        .print-header .main-title {
            color: #4B2E83;
            font-weight: bold;
            font-size: 12pt;
            margin: 0;
        }
        .print-header .subtitle {
            font-size: 9pt;
            color: #333;
            margin: 2px 0 4px;
        }
        .print-header .logo {
            height: 28px;
            margin-top: 4px;
            opacity: 0.95;
        }

        /* === PAGE CONTENT (STARTS BELOW HEADER) === */
        .page-content {
            margin-top: 70px; /* Space for fixed header */
            page-break-after: always;
        }

        /* === COMPACT TIMETABLE === */
        body { margin: 0; font-family: Arial, sans-serif; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 15px;
            font-size: 7.5pt;
            table-layout: fixed;
        }
        th, td {
            border: 1.5px solid #4B2E83;
            padding: 3px 2px;
            text-align: center;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background-color: #4B2E83;
            color: white;
            font-weight: bold;
            font-size: 8pt;
        }
        .faculty-title {
            text-align: center;
            margin: 12px 0 10px;
            font-weight: bold;
            color: #4B2E83;
            font-size: 12pt;
            page-break-after: avoid;
        }
        p {
            text-align: center;
            margin: 8px 0;
            font-size: 8pt;
            page-break-before: avoid;
        }
        .time-cell {
            font-size: 7pt;
            color: #444;
            font-weight: 600;
            width: 68px;
        }
        .session {
            background: linear-gradient(135deg, #e6ebed, #f5f6f7);
            padding: 2px 1px;
            margin: 1px;
            text-align: center;
            font-size: 6.5pt;
            line-height: 1.1;
            border-radius: 2px;
            min-height: 38px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border: 0.5px solid #ccc;
        }
        .session strong {
            font-size: 7pt;
            display: block;
            font-weight: bold;
            margin-bottom: 1px;
        }
        .session-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
            gap: 1px;
            width: 100%;
            min-height: 42px;
        }
        .chapel {
            background: #4B2E83;
            color: white;
            font-weight: bold;
            font-size: 6.5pt;
            padding: 2px 1px;
            border-radius: 2px;
            line-height: 1.1;
            text-align: center;
        }

        /* === PRINT OPTIMIZATIONS === */
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 65px;
            }
            .page-content {
                margin-top: 75px;
            }
            table { font-size: 6.5pt; margin: 8px 0; }
            th, td { padding: 2px 1.5px; border: 1px solid #4B2E83; }
            .time-cell { font-size: 6.5pt; }
            .session { font-size: 6pt; min-height: 34px; padding: 1px; margin: 0.5px; }
            .session strong { font-size: 6.5pt; }
            .session-container { gap: 0.5px; min-height: 36px; }
            .chapel { font-size: 6pt; padding: 1px; }
            @page { margin: 1.4cm 0.8cm 1.2cm 0.8cm; size: A4; }
        }
    </style>
</head>
<body>

    <!-- FIXED HEADER (REPEATS ON EVERY PAGE) -->
    <div class="print-header">
        <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
        <div class="subtitle">
            Lectures Timetable • {{ $timetableSemester->academic_year }} • {{ $timetableSemester->semester->name }}
        </div>
        
        <div style="font-weight: bold; color: #4B2E83; margin-top: 4px; font-size: 10pt;">
        {{ $draft ?? 'Final Draft' }}
    </div>
        <img src="{{ public_path('images/logo.png') }}" alt="SJUT" class="logo">
    </div>

    @if ($faculties->isEmpty())
        <div class="page-content">
            <p>No timetable data available.</p>
        </div>
    @else
        @php
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $chapelTimes = ['Tuesday' => '10:00', 'Friday' => '12:00'];
        @endphp

        @foreach ($faculties as $faculty)
            <div class="page-content">

                <!-- FACULTY NAME -->
                <div class="faculty-title">{{ $faculty->name }}</div>

                @php
                    $matrix = [];
                    $activitiesByDay = $faculty->timetables->groupBy('day');
                    foreach ($activitiesByDay as $day => $acts) {
                        foreach ($acts as $act) {
                            $start = strtotime($act->time_start);
                            $end = strtotime($act->time_end);
                            foreach ($timeSlots as $i => $slotStart) {
                                $slotStartTime = strtotime($slotStart);
                                $slotEndTime = $slotStartTime + 3600;
                                if ($start < $slotEndTime && $end > $slotStartTime) {
                                    $matrix[$i][$day][] = $act;
                                }
                            }
                        }
                    }
                @endphp

                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            @foreach ($days as $day)
                                <th>{{ substr($day, 0, 3) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($timeSlots as $i => $slotStart)
                            @php
                                $slotEnd = date('H:i', strtotime($slotStart) + 3600);
                                $currentHour = $slotStart;
                            @endphp
                            <tr>
                                <td class="time-cell">{{ $slotStart }}-{{ $slotEnd }}</td>
                                @foreach ($days as $day)
                                    @php
                                        $activities = $matrix[$i][$day] ?? [];
                                        $isChapel = (isset($chapelTimes[$day]) && $currentHour === $chapelTimes[$day]);
                                        $content = '&nbsp;';

                                        if ($isChapel) {
                                            $content = '<div class="chapel">Chapel</div>';
                                        } elseif ($activities) {
                                            $byCourse = [];
                                            foreach ($activities as $a) {
                                                $code = $a->course_code;
                                                $byCourse[$code]['groups'][] = trim($a->group_selection);
                                                $byCourse[$code]['venues'][] = $a->venue->name;
                                                $byCourse[$code]['activity'] = $a->activity;
                                            }
                                            $sessionsHtml = '';
                                            foreach ($byCourse as $code => $data) {
                                                $groups = array_unique($data['groups']); sort($groups);
                                                $groupStr = implode(', ', $groups);
                                                $venues = array_unique($data['venues']); sort($venues);
                                                $venueStr = implode(', ', $venues);
                                                $sessionsHtml .= "<div class=\"session\">
                                                    <strong>{$code}</strong>
                                                    {$groupStr}
                                                    {$data['activity']}
                                                    {$venueStr}
                                                </div>";
                                            }
                                            $content = "<div class=\"session-container\">{$sessionsHtml}</div>";
                                        }
                                    @endphp
                                    <td>{!! $content !!}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p>For any changes consult Faculty/School Administrators</p>
            </div>
        @endforeach
    @endif>

</body>
</html>
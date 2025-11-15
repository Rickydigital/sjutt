<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecture Timetable - Compact</title>
    <style>
        body { margin: 0; padding-top: 86px; font-family: Arial, sans-serif; }
        
        .print-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 76px;
            background: white;
            text-align: center;
            padding: 6px 0;
            border-bottom: 2px solid #4B2E83;
            z-index: 10000;
            line-height: 1.2;
        }
        .print-header .main-title {
            color: #4B2E83;
            font-weight: bold;
            font-size: 12.5pt;
            margin: 0;
        }
        .print-header .subtitle {
            font-size: 9pt;
            color: #333;
            margin: 2px 0;
        }
        .print-header .draft {
            font-weight: bold;
            color: #4B2E83;
            font-size: 8.5pt;
        }
        .print-header .logo {
            height: 26px;
            margin-top: 3px;
        }

        .faculty-page {
            page-break-before: always;
            padding-top: 8px;
        }

        .timetable-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.8pt;
            table-layout: fixed;
            margin: 0;
        }

        .timetable-table thead { display: table-header-group !important; }
        .timetable-table tr { page-break-inside: avoid !important; }

        .timetable-table th, .timetable-table td {
            border: 1.4px solid #4B2E83;
            padding: 1.5px 2px;
            text-align: center;
            vertical-align: middle;
        }

        /* FACULTY NAME - REPEATS */
        .faculty-row th {
            background: #4B2E83 !important;
            color: white !important;
            font-size: 11pt !important;
            font-weight: bold;
            padding: 7px 5px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* DAY HEADERS */
        .day-header th {
            background: #4B2E83 !important;
            color: white !important;
            font-weight: bold;
            font-size: 7.8pt;
            padding: 4px 2px;
        }

        .time-cell {
            background: #f5f5f5;
            font-weight: bold;
            font-size: 7pt;
            width: 64px;
            padding: 3px 2px;
        }

        /* ULTRA COMPACT SESSION */
        {{--  .session {
            background: #eef1f5;
            border: 0.6px solid #bbb;
            border-radius: 2px;
            padding: 1px 2px;
            margin: 0.8px 0;
            font-size: 5.8pt !important;
            line-height: 1.15;
            min-height: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }  --}}
        .session strong {
            font-size: 6.2pt !important;
            font-weight: bold;
            display: block;
            color: #222;
        }

        /* HORIZONTAL LAYOUT - MAX DENSITY */
        .session-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5px;
            justify-content: center;
            padding: 1px;
            min-height: 22px; /* 50% smaller! */
        }

        .chapel {
            background: #4B2E83;
            color: white;
            font-weight: bold;
            font-size: 6.2pt;
            padding: 2px 4px;
            border-radius: 2px;
            min-height: 20px;
            display: inline-block;
        }

        .footer {
            text-align: center;
            margin: 6px 0 10px;
            font-size: 7.5pt;
            color: #555;
            font-style: italic;
        }

        /* PRINT OPTIMIZATIONS */
        @media print {
            body { padding-top: 0 !important; }
            .print-header {
                height: 72px;
                padding: 5px 0;
            }
            .timetable-table {
                font-size: 6.4pt;
            }
            .faculty-row th {
                font-size: 10.5pt !important;
                padding: 6px 4px !important;
            }
            .day-header th {
                font-size: 7.5pt;
                padding: 3px 2px;
            }
            .time-cell {
                font-size: 6.8pt;
                padding: 2px;
            }
            .session {
                font-size: 5.6pt !important;
                padding: 0.8px 1.5px;
                margin: 0.6px 0;
                min-height: 18px;
            }
            .session strong {
                font-size: 6pt !important;
            }
            .session-container {
                gap: 1.2px;
                min-height: 20px;
            }
            .chapel {
                font-size: 6pt;
                padding: 1.5px 3px;
            }
            @page {
                margin: 1.1cm 0.7cm 1.1cm 0.7cm;
                size: A4 portrait;
            }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

    <div class="print-header">
        <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
        <div class="subtitle">Lectures Timetable • {{ $timetableSemester->academic_year }} • {{ $timetableSemester->semester->name }}</div>
        <div class="draft">{{ $draft ?? 'First Draft' }}</div>
        <img src="{{ public_path('images/logo.png') }}" alt="SJUT" class="logo">
    </div>

    @if ($faculties->isEmpty())
        <div class="faculty-page"><p>No timetable data available.</p></div>
    @else
        @php
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $chapelEvents = [
                    'Tuesday-10:00' => 'Community Chapel',
                    'Friday-12:00'  => 'Fellowship'
                ];
        @endphp

        @foreach ($faculties as $faculty)
            <div class="{{ $loop->first ? '' : 'faculty-page' }}">
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

                <table class="timetable-table">
                    <thead>
                        <tr class="faculty-row">
                            <th colspan="6">{{ $faculty->name }}</th>
                        </tr>
                        <tr class="day-header">
                            <th class="time-cell">Time</th>
                            @foreach ($days as $day) <th>{{ substr($day, 0, 3) }}</th> @endforeach
                        </tr>
                    </thead>
                    <tbody>
    @foreach ($timeSlots as $i => $slotStart)
        @php 
            $slotEnd = date('H:i', strtotime($slotStart) + 3600); 
            
            // Build chapel key: "Tuesday-10:00"
            $chapelKey = "{$day}-{$slotStart}";
            $chapelName = $chapelEvents[$chapelKey] ?? null;
        @endphp
        <tr>
            <td class="time-cell">{{ $slotStart }}-{{ $slotEnd }}</td>
            @foreach ($days as $day)
                @php
                    $activities = $matrix[$i][$day] ?? [];
                    $chapelKey = "{$day}-{$slotStart}";
                    $chapelName = $chapelEvents[$chapelKey] ?? null;
                    $content = '&nbsp;';

                    if ($chapelName) {
                        $content = '<div class="chapel">' . $chapelName . '</div>';
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
                            $groups = array_unique($data['groups']); 
                            sort($groups);
                            $groupStr = implode(',', $groups);
                            $venues = array_unique($data['venues']); 
                            sort($venues);
                            $venueStr = implode(',', $venues);
                            $act = $data['activity'];

                            $sessionsHtml .= "<div class=\"session\">
                                <strong>{$code}</strong>
                                {$groupStr} {$act}<br>
                                <small>{$venueStr}</small>
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

                <div class="footer">For changes, consult Faculty/School Administrators</div>
            </div>
        @endforeach
    @endif>

</body>
</html>
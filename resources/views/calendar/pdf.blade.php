```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic and Meeting Calendar - PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 15mm;
            color: #000;
            font-size: 10pt;
        }

        h1.calendar-title {
            font-size: 18pt;
            color: #6f42c1;
            text-align: center;
            margin-bottom: 10mm;
        }

        .cover-page {
            text-align: center;
            page-break-after: always;
            break-after: always;
            margin-top: 20mm;
        }

        .cover-title {
            font-size: 24pt;
            color: #6f42c1;
            font-weight: bold;
            margin-bottom: 20mm;
        }

        .cover-subtitle {
            font-size: 18pt;
            color: #6f42c1;
            margin-top: 20mm;
        }

        .cover-logo {
            max-width: 200px;
            width: 100%;
            height: auto;
            margin: 20mm auto;
            display: block;
        }

        .month-section {
            margin-bottom: 10mm;
            page-break-before: always;
            break-before: always;
        }

        .month-section:first-child {
            page-break-before: avoid;
            break-before: avoid;
        }

        .month-header {
            background-color: #6f42c1;
            color: #fff;
            padding: 6px;
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 5mm;
            border-radius: 4px;
        }

        .week-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 5mm;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .week-table th,
        .week-table td {
            border: 1px solid #666;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
            min-width: 40px;
            max-width: 90px;
        }

        .week-table th {
            background-color: #6f42c1;
            color: #fff;
            font-weight: bold;
            padding: 6px;
        }

        .week-table td.empty {
            background-color: #f5f6fa;
        }

        .week-table td.event {
            background-color: #dfe4ea;
            color: #000;
        }

        .week-end {
            border-bottom: 2px solid #2c3e50;
        }

        @page {
            margin: 15mm;
            size: A4 portrait;
        }

        @media print {
            .week-table {
                font-size: 7.5pt;
            }
            .week-table th,
            .week-table td {
                padding: 3px;
                min-width: 35px;
                max-width: 85px;
            }
        }
    </style>
</head>
<body>
    <div class="cover-page">
        <h1 class="cover-title">St. John’s University of Tanzania</h1>
        <img src="{{ public_path('images/logo.png') }}" alt="St. John’s University of Tanzania Logo" class="cover-logo">
        <h2 class="cover-subtitle">Almanac of {{ $setup->year ?? '2025-2026' }}</h2>
    </div>

    {{-- <h1 class="calendar-title">Academic and Meeting Calendar {{ $setup->year ?? '2025-2026' }}</h1> --}}
    <img src="{{ public_path('images/logo.png') }}" alt="St. John’s University of Tanzania Logo" class="cover-logo">

    @if(!$setup)
        <div style="text-align: center; color: #856404; background-color: #fff3cd; padding: 10px; border: 1px solid #ffeeba;">
            No calendar setup found. Please configure the calendar.
        </div>
    @else
        @foreach($calendarData as $monthData)
            <div class="month-section">
                <div class="month-header">
                    {{ $monthData['month'] }} {{ $setup->year }}
                </div>
                @php
                    $days = $monthData['days'];
                    $weekGroups = array_chunk($days, 7); // Split into groups of ~1 week
                @endphp
                @foreach($weekGroups as $weekIndex => $weekDays)
                    <table class="week-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th colspan="5">Week Number</th>
                                <th>Days</th>
                                <th>Academic Calendar</th>
                                <th>Meeting/Activities</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th>Degree Health</th>
                                <th>Degree Non-Health</th>
                                <th>Non-Degree Non-Health</th>
                                <th>Non-Degree Health</th>
                                <th>Masters</th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($weekDays as $index => $day)
                                @php
                                    $isFirstDayOfWeek = $index === 0;
                                    $rowSpanCount = count($weekDays);
                                @endphp
                                <tr class="{{ $day['isWeekEnd'] ? 'week-end' : '' }}">
                                    @if($isFirstDayOfWeek)
                                        <td rowspan="{{ $rowSpanCount }}">{{ $monthData['month'] }}</td>
                                        <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Degree Health'] ? 'event' : 'empty' }}">{{ $day['events']['Degree Health'] ?? '-' }}</td>
                                        <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Degree Non-Health'] ? 'event' : 'empty' }}">{{ $day['events']['Degree Non-Health'] ?? '-' }}</td>
                                        <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Non-Degree Non-Health'] ? 'event' : 'empty' }}">{{ $day['events']['Non-Degree Non-Health'] ?? '-' }}</td>
                                        <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Non-Degree Health'] ? 'event' : 'empty' }}">{{ $day['events']['Non-Degree Health'] ?? '-' }}</td>
                                        <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Masters'] ? 'event' : 'empty' }}">{{ $day['events']['Masters'] ?? '-' }}</td>
                                    @endif
                                    <td>{{ $day['dayName'] }} {{ $day['dayNumber'] }}</td>
                                    <td class="{{ $day['events']['Academic Calendar'] ? 'event' : 'empty' }}">{{ $day['events']['Academic Calendar'] ?? '-' }}</td>
                                    <td class="{{ $day['events']['Meeting/Activities Calendar'] ? 'event' : 'empty' }}">{{ $day['events']['Meeting/Activities Calendar'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        @endforeach
    @endif
</body>
</html>
```
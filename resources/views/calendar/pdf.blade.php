<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic and Meeting Calendar</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #4B2E83; text-align: center; }
        h5 { background: linear-gradient(135deg, #6f42c1, #4B2E83); color: white; padding: 10px; margin: 10px 0; }
        .timetable-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .timetable-table th, .timetable-table td { border: 2px solid #dee2e6; text-align: center; padding: 8px; vertical-align: middle; }
        .timetable-table th { background: linear-gradient(135deg, #6f42c1, #4B2E83); color: white; font-weight: bold; }
        .event-cell { background: linear-gradient(135deg, #e2e8f0, #f8f9fa); }
        .empty-cell { background: #f8f9fa; height: 80px; }
        .week-end { border-bottom: 3px solid #343a40; }
        .month-end { border-bottom: 5px solid #343a40; }
    </style>
</head>
<body>
    <h1>Academic and Meeting Calendar</h1>
    @foreach($calendarData as $monthData)
        <h5>{{ $monthData['month'] }}</h5>
        <table class="timetable-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th colspan="5">Week Number</th>
                    <th>Days</th>
                    <th>Academic Calendar</th>
                    <th>Meeting/Activities Calendar</th>
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
                @php $currentWeek = null; $weekDays = []; @endphp
                @foreach($monthData['days'] as $index => $day)
                    @if($day['weekNumber'] !== $currentWeek)
                        @php
                            $currentWeek = $day['weekNumber'];
                            $weekDays = array_filter($monthData['days'], fn($d) => $d['weekNumber'] === $currentWeek);
                            $rowSpanCount = count($weekDays);
                        @endphp
                        <tr class="{{ $day['isWeekEnd'] ? 'week-end' : '' }} {{ $day['isMonthEnd'] ? 'month-end' : '' }}">
                            @if($index === 0)
                                <td rowspan="{{ count($monthData['days']) }}">{{ $monthData['month'] }}</td>
                            @endif
                            <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Degree Health'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Degree Health'] }}</td>
                            <td rowspan="{{ $rowSpanCount }}" class={{ $day['events']['Degree Non-Health'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Degree Non-Health'] }}</td>
                            <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Non-Degree Non-Health'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Non-Degree Non-Health'] }}</td>
                            <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Non-Degree Health'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Non-Degree Health'] }}</td>
                            <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Masters'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Masters'] }}</td>
                            <td>{{ $day['dayName'] }} {{ $day['dayNumber'] }}</td>
                            <td class="{{ $day['events']['Academic Calendar'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Academic Calendar'] }}</td>
                            <td class="{{ $day['events']['Meeting/Activities Calendar'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Meeting/Activities Calendar'] }}</td>
                        </tr>
                    @else
                        <tr class="{{ $day['isWeekEnd'] ? 'week-end' : '' }} {{ $day['isMonthEnd'] ? 'month-end' : '' }}">
                            <td>{{ $day['dayName'] }} {{ $day['dayNumber'] }}</td>
                            <td class="{{ $day['events']['Academic Calendar'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Academic Calendar'] }}</td>
                            <td class="{{ $day['events']['Meeting/Activities Calendar'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Meeting/Activities Calendar'] }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
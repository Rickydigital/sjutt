<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic and Meeting Calendar</title>
    <style>
        /* Reset default styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #2d2d2d;
            background-color: #f5f5f5;
        }

        /* Page layout for A4 size */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 15mm auto;
            padding: 20mm;
            background: #ffffff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 15mm;
            padding-bottom: 10mm;
            border-bottom: 3px solid #1a3c6d;
        }

        .header h1 {
            font-size: 24pt;
            color: #1a3c6d;
            font-weight: 700;
            margin-bottom: 5mm;
        }

        .header p {
            font-size: 10pt;
            color: #666;
            font-style: italic;
        }

        /* Month heading */
        .month-heading {
            font-size: 18pt;
            color: #1a3c6d;
            margin: 10mm 0;
            text-align: center;
            font-weight: 500;
        }

        /* Table styling */
        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20mm;
            font-size: 10pt;
        }

        .calendar-table th,
        .calendar-table td {
            border: 1px solid #e0e0e0;
            padding: 4mm 2mm;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .calendar-table th {
            background: linear-gradient(135deg, #1a3c6d, #2e5a9b);
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 9pt;
            padding: 5mm;
        }

        .calendar-table .event-cell {
            background: #e6f0fa;
            font-weight: 500;
        }

        .calendar-table .empty-cell {
            background: #f9fafb;
            color: #999;
        }

        .calendar-table .month-cell {
            font-weight: bold;
            background: #edf2f7;
            font-size: 11pt;
        }

        .week-end {
            background: #f1f3f5;
        }

        .month-end {
            border-bottom: 3px solid #1a3c6d;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 10mm;
            width: calc(100% - 40mm);
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #e0e0e0;
            padding-top: 5mm;
        }

        /* Page break for printing */
        .month-section {
            page-break-after: always;
        }

        .month-section:last-child {
            page-break-after: auto;
        }

        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            .page {
                width: 100%;
                padding: 10mm;
                margin: 5mm;
            }

            .calendar-table th,
            .calendar-table td {
                font-size: 8pt;
                padding: 2mm;
            }

            .header h1 {
                font-size: 18pt;
            }

            .month-heading {
                font-size: 14pt;
            }
        }

        /* Print styles */
        @media print {
            .page {
                margin: 0;
                box-shadow: none;
                width: 210mm;
                min-height: 297mm;
            }

            .footer {
                position: fixed;
                bottom: 0;
            }

            .month-section {
                page-break-after: always;
            }

            .month-section:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <h1>Academic and Meeting Calendar</h1>
            <p>Generated on May 20, 2025</p>
        </div>

        <!-- Calendar Data -->
        @foreach ($calendarData as $monthData)
            <div class="month-section">
                <h2 class="month-heading">{{ $monthData['month'] }}</h2>
                <table class="calendar-table">
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
                        @php
                            $currentWeek = null;
                            $weekDays = [];
                        @endphp
                        @foreach ($monthData['days'] as $index => $day)
                            @if ($day['weekNumber'] != $currentWeek)
                                @php
                                    $currentWeek = $day['weekNumber'];
                                    $weekDays = array_filter($monthData['days'], fn($d) => $d['weekNumber'] == $currentWeek);
                                    $rowSpanCount = count($weekDays);
                                @endphp
                                <tr class="{{ $day['isWeekEnd'] ? 'week-end' : '' }} {{ $day['isMonthEnd'] ? 'month-end' : '' }}">
                                    @if ($index == 0)
                                        <td rowspan="{{ count($monthData['days']) }}" class="month-cell">{{ $monthData['month'] }}</td>
                                    @endif
                                    <td rowspan="{{ $rowSpanCount }}" class="{{ !empty($day['events']['Degree Health']) ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Degree Health'] ?? '-' }}</td>
                                    <td rowspan="{{ $rowSpanCount }}" class="{{ !empty($day['events']['Degree Non-Health']) ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Degree Non-Health'] ?? '-' }}</td>
                                    <td rowspan="{{ $rowSpanCount }}" class="{{ !empty($day['events']['Non-Degree Non-Health']) ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Non-Degree Non-Health'] ?? '-' }}</td>
                                    <td rowspan="{{ $rowSpanCount }}" class="{{ !empty($day['events']['Non-Degree Health']) ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Non-Degree Health'] ?? '-' }}</td>
                                    <td rowspan="{{ $rowSpanCount }}" class="{{ !empty($day['events']['Masters']) ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Masters'] ?? '-' }}</td>
                                    <td>{{ $day['dayName'] }} {{ $day['dayNumber'] }}</td>
                                    <td class="{{ !empty($day['events']['Academic Calendar']) ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Academic Calendar'] ?? '-' }}</td>
                                    <td class="{{ !empty($day['events']['Meeting/Activities Calendar']) ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Meeting/Activities Calendar'] ?? '-' }}</td>
                                </tr>
                            @else
                                <tr class="{{ $day['isWeekEnd'] ? 'week-end' : '' }} {{ $day['isMonthEnd'] ? 'month-end' : '' }}">
                                    <td>{{ $day['dayName'] }} {{ $day['dayNumber'] }}</td>
                                    <td class="{{ !empty($day['events']['Academic Calendar']) ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Academic Calendar'] ?? '-' }}</td>
                                    <td class="{{ !empty($day['events']['Meeting/Activities Calendar']) ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Meeting/Activities Calendar'] ?? '-' }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <!-- Footer -->
        <div class="footer">
            <p>Page <span class="page-number"></span> | Academic and Meeting Calendar | 2025 SJUT</p>
        </div>
    </div>

    <!-- JavaScript for dynamic page numbering -->
    <script>
        document.querySelectorAll('.page-number').forEach((elem, index) => {
            elem.textContent = index + 1;
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Examination Venue Usage - {{ $venue->name }}</title>
    <style>
        @page { margin: 28mm 7mm 10mm; size: A4 portrait; }
        body { margin:0; font-family:DejaVu Sans, sans-serif; color:#222; }
        .print-header { position:fixed; top:-24mm; left:0; right:0; height:20mm; text-align:center; border-bottom:2px solid #4B2E83; }
        .main-title { color:#4B2E83; font-size:12.5pt; font-weight:bold; }
        .subtitle { margin-top:2px; font-size:8.5pt; }
        .logo { height:27px; margin-top:3px; }
        .summary { margin-bottom:6px; padding:5px; border:1px solid #4B2E83; background:#f2edfb; font-size:7.5pt; }
        .summary strong { color:#4B2E83; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; margin-bottom:9px; }
        thead { display:table-header-group; }
        tr { page-break-inside:avoid; }
        th, td { border:1.2px solid #4B2E83; padding:3px 2px; text-align:center; vertical-align:middle; font-size:6.8pt; }
        th { background:#4B2E83; color:#fff; font-weight:bold; }
        .week-title th { font-size:9pt; padding:5px; }
        .time { width:105px; background:#f4f4f4; font-weight:bold; }
        .used { background:#eee7fb; }
        .free { background:#f2fbf5; color:#258044; font-weight:bold; }
        .exam { margin:1px 0; padding:2px; border-bottom:.5px solid #b9a5db; line-height:1.2; }
        .exam:last-child { border-bottom:0; }
        .students { color:#4B2E83; font-weight:bold; }
        * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
    </style>
</head>
<body>
    <div class="print-header">
        <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
        <div class="subtitle">Examination Venue Usage • {{ $setup->academic_year }} • {{ $setup->semester?->name ?? 'Semester' }}</div>
        <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
    </div>

    <div class="summary">
        <strong>Venue:</strong> {{ $venue->name }}{{ $venue->longform ? ' — '.$venue->longform : '' }}
        &nbsp; | &nbsp; <strong>Venue capacity:</strong> {{ $venue->capacity }}
        &nbsp; | &nbsp; <strong>Total allocated students:</strong> {{ $totalStudents }}
    </div>

    @foreach($dateChunks as $weekIndex => $chunkDays)
        <table>
            <thead>
                <tr class="week-title"><th colspan="{{ 1 + count($chunkDays) }}">{{ $venue->name }} — Week {{ $weekIndex + 1 }}</th></tr>
                <tr>
                    <th class="time">TIME</th>
                    @foreach($chunkDays as $day)
                        <th>{{ \Carbon\Carbon::parse($day)->format('d-m') }} ({{ strtoupper(\Carbon\Carbon::parse($day)->format('D')) }})</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($timeSlots as $slot)
                    <tr>
                        <td class="time">{{ $slot['name'] }}<br>{{ $slot['start_time'] }}-{{ $slot['end_time'] }}</td>
                        @foreach($chunkDays as $day)
                            @php
                                $items = $grid[$day][$slot['start_time']] ?? [];
                            @endphp
                            <td class="{{ empty($items) ? 'free' : 'used' }}">
                                @forelse($items as $item)
                                    <div class="exam">
                                        <strong>{{ $item['course_code'] }}</strong><br>
                                        {{ $item['faculty'] }}
                                        @if($item['program'])<br>{{ $item['program'] }}@endif
                                        <br><span class="students">{{ $item['allocated_capacity'] }} students</span>
                                    </div>
                                @empty
                                    FREE
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>

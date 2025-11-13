<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Venue Usage – {{ $venue->longform }}</title>

    <style>
        @page {
            margin: 1.2cm 1.5cm;
            size: A4 portrait;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            color: #222;
            line-height: 1.35;
            position: relative;
        }

        /* ── WATERMARK ── */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 4.5rem;
            color: rgba(75,46,131,0.07);
            pointer-events: none;
            white-space: nowrap;
            z-index: 0;
            font-weight: 300;
            user-select: none;
        }

        /* ── HEADER ── */
        .header {
            text-align: center;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        .logo {
            height: 45px;
            width: auto;
            display: block;
            margin: 6px auto;
        }
        h1 {
            margin: 4px 0;
            font-size: 17px;
            color: #4B2E83;
            font-weight: 700;
        }
        h2 {
            margin: 2px 0 8px;
            font-size: 13px;
            color: #4B2E83;
        }

        /* ── INFO BLOCK ── */
        .info {
            text-align: center;
            font-size: 9.5px;
            margin-bottom: 12px;
            color: #444;
            line-height: 1.5;
        }
        .info strong { color: #4B2E83; }

        /* ── TABLE ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 9px;
            position: relative;
            z-index: 1;
        }
        th, td {
            border: 1.8px solid #4B2E83;
            padding: 4px 5px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: #4B2E83;
            color: #fff;
            font-weight: 600;
            font-size: 8.2px;
            letter-spacing: .3px;
        }
        .badge {
            display: inline-block;
            background: #4B2E83;
            color: #fff;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            margin: 1px 2px;
            line-height: 1.2;
            white-space: nowrap;
        }
        .badge-wrap {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 3px;
            padding: 2px 0;
        }

        /* ── EMPTY STATE ── */
        .empty {
            text-align: center;
            padding: 35px 0;
            font-size: 11px;
            color: #666;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 22px;
            border-top: 1px solid #4B2E83;
            padding-top: 8px;
            text-align: center;
            font-size: 8px;
            color: #555;
        }

        /* ── PRINT OPTIMIZATIONS ── */
        @media print {
            .watermark { font-size: 4rem; }
            .logo { height: 38px; }
            table { font-size: 8px; }
            th { font-size: 7.8px; }
            .badge { font-size: 7px; }
            .info { font-size: 9px; }
        }
    </style>
</head>

<body>

    {{-- WATERMARK --}}
    <div class="watermark">ST JOHN'S UNIVERSITY OF TANZANIA</div>

    {{-- LOGO (Base64 Embedded) --}}
    @php
        $logoPath = public_path('images/logo.png');
        $logoSrc = '';
        $logoAlt = 'SJUT';

        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = base64_encode(file_get_contents($logoPath));
            $logoSrc = "data:image/{$type};base64,{$data}";
        }
    @endphp

    <div class="header">
        @if($logoSrc)
            <img src="{{ $logoSrc }}" alt="{{ $logoAlt }} Logo" class="logo">
        @else
            <div style="height:45px; display:flex; align-items:center; justify-content:center; background:#f8f9fa; border:2px dashed #ccc; color:#4B2E83; font-weight:bold; font-size:14px; margin:6px auto; width:120px;">
                SJUT
            </div>
        @endif

        <h1>ST JOHN'S UNIVERSITY OF TANZANIA</h1>
        <h2>Venue Usage Summary</h2>
    </div>

    {{-- VENUE & SEMESTER INFO --}}
    <div class="info">
        <strong>Venue:</strong> {{ $venue->longform }} ({{ $venue->name }})<br>
        <strong>Capacity:</strong> {{ $venue->capacity }} | 
        <strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $venue->type)) }}<br>
        <strong>Semester:</strong> {{ $semester->semester_name }} ({{ $semester->academic_year }})<br>
        <strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}
    </div>

    @if($slots->isEmpty())
        <div class="empty">
            <strong>No sessions booked</strong> for this venue in the current semester.
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Courses</th>
                    <th>Faculty</th>
                    <th>Lecturer</th>
                    <th>Groups</th>
                    <th>Activity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($slots as $slot)
                    <tr>
                        <td>{{ $slot['day'] }}</td>
                        <td>{{ $slot['start'] }}–{{ $slot['end'] }}</td>
                        <td class="badge-wrap">
                            @foreach($slot['courses'] as $code)
                                <span class="badge">{{ $code }}</span>
                            @endforeach
                        </td>
                        <td>{{ $slot['faculty'] ?: '–' }}</td>
                        <td>{{ $slot['lecturers'] ?: '–' }}</td>
                        <td>{{ $slot['groups'] }}</td>
                        <td>{{ $slot['activity'] ?: '–' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        Generated by SJUT Timetable System | For inquiries, contact Timetable Administrator
    </div>

</body>
</html>
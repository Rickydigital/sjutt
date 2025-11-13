<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecturer Schedule – {{ $user->name }}</title>

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
            overflow: hidden;
        }

        /* ── LOGO WATERMARK (CENTERED, ROTATED, FAINT) ── */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            width: 420px;
            height: auto;
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
            user-select: none;
        }

        /* ── HEADER ── */
        .header {
            textAlign: center;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        .logo {
            height: 48px;
            width: auto;
            display: block;
            margin: 8px auto 4px;
        }
        h1 {
            margin: 4px 0;
            font-size: 18px;
            color: #4B2E83;
            font-weight: 700;
        }
        h2 {
            margin: 2px 0 10px;
            font-size: 14px;
            color: #4B2E83;
            font-weight: 500;
        }

        /* ── USER INFO ── */
        .info {
            text-align: center;
            font-size: 10px;
            margin-bottom: 14px;
            color: #444;
            line-height: 1.5;
        }
        .info strong { color: #4B2E83; }

        /* ── TABLE ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 9.2px;
            position: relative;
            z-index: 1;
        }
        th, td {
            border: 1.8px solid #4B2E83;
            padding: 5px 6px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: #4B2E83;
            color: #fff;
            font-weight: 600;
            font-size: 8.5px;
            letter-spacing: .4px;
        }
        .badge {
            display: inline-block;
            background: #4B2E83;
            color: #fff;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 7.8px;
            margin: 1px 2px;
            line-height: 1.3;
            white-space: nowrap;
            font-weight: 500;
        }
        .badge-wrap {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 4px;
            padding: 3px 0;
        }

        /* ── EMPTY STATE ── */
        .empty {
            text-align: center;
            padding: 40px 0;
            font-size: 12px;
            color: #777;
            font-style: italic;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 25px;
            border-top: 1.2px solid #4B2E83;
            padding-top: 10px;
            text-align: center;
            font-size: 8.5px;
            color: #555;
        }

        /* ── PRINT OPTIMIZATIONS ── */
        @media print {
            .watermark { opacity: 0.06; width: 380px; }
            .logo { height: 42px; }
            table { font-size: 8.5px; }
            th { font-size: 8px; }
            .badge { font-size: 7.2px; }
            .info { font-size: 9.5px; }
        }
    </style>
</head>

<body>

    {{-- LOGO WATERMARK (BASE64) --}}
    @php
        $logoPath = public_path('images/logo.png');
        $logoSrc = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = base64_encode(file_get_contents($logoPath));
            $logoSrc = "data:image/{$type};base64,{$data}";
        }
    @endphp

    @if($logoSrc)
        <img src="{{ $logoSrc }}" alt="Watermark" class="watermark">
    @endif

    {{-- HEADER WITH LOGO --}}
    <div class="header">
        @if($logoSrc)
            <img src="{{ $logoSrc }}" alt="SJUT Logo" class="logo">
        @else
            <div style="height:48px; display:flex; align-items:center; justify-content:center; background:#f8f9fa; border:2px dashed #ccc; color:#4B2E83; font-weight:bold; font-size:15px; margin:8px auto; width:130px;">
                SJUT
            </div>
        @endif

        <h1>ST JOHN'S UNIVERSITY OF TANZANIA</h1>
        <h2>Lecturer Teaching Schedule</h2>
    </div>

    {{-- LECTURER & SEMESTER INFO --}}
    <div class="info">
        <strong>Lecturer:</strong> {{ $user->name }}<br>
        <strong>Role:</strong> {{ $user->roles->pluck('name')->implode(', ') ?: 'Lecturer' }}<br>
        <strong>Semester:</strong> {{ $semester->semester_name }} ({{ $semester->academic_year }})<br>
        <strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}
    </div>

    @if($slots->isEmpty())
        <div class="empty">
            <strong>No teaching sessions assigned</strong> to this lecturer in the current semester.
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Courses</th>
                    <th>Venue</th>
                    <th>Faculty</th>
                    <th>Groups</th>
                    <th>Activity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($slots as $slot)
                    <tr>
                        <td><strong>{{ $slot['day'] }}</strong></td>
                        <td>{{ $slot['start'] }}–{{ $slot['end'] }}</td>
                        <td class="badge-wrap">
                            @foreach($slot['courses'] as $code)
                                <span class="badge">{{ $code }}</span>
                            @endforeach
                        </td>
                        <td>{{ $slot['venue'] }} ({{ $slot['venue_code'] }})</td>
                        <td>{{ $slot['faculty'] ?: '–' }}</td>
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
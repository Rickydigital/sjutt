<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Register • {{ $today }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding-top: 90px;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
        }

        /* === FIXED HEADER - SAME AS TIMETABLE === */
        .print-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 82px;
            background: white;
            text-align: center;
            padding: 8px 0;
            border-bottom: 3px double #4B2E83;
            z-index: 10000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .print-header .main-title {
            color: #4B2E83;
            font-weight: bold;
            font-size: 14pt;
            margin: 4px 0;
        }
        .print-header .subtitle {
            font-size: 10.5pt;
            color: #333;
            margin: 3px 0;
        }
        .print-header .generated {
            font-size: 9pt;
            color: #4B2E83;
            font-weight: bold;
        }
        .print-header .logo {
            height: 38px;
            margin-top: 4px;
        }

        /* Faculty Title - Repeating Purple Bar */
        .faculty-header {
            background: #4B2E83 !important;
            color: white !important;
            font-size: 15pt !important;
            font-weight: bold;
            text-align: center;
            padding: 10px !important;
            margin: 20px 0 10px 0;
            page-break-after: avoid;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.8pt;
            margin-bottom: 15px;
        }
        th, td {
            border: 1.8px solid #4B2E83;
            padding: 6px 4px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: #4B2E83 !important;
            color: white !important;
            font-weight: bold;
            font-size: 10pt;
            -webkit-print-color-adjust: exact;
        }
        .today {
            background: #fff3cd !important;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
        }
        .student-name {
            text-align: left !important;
            padding-left: 12px;
            font-weight: 600;
            width: 28% !important;
        }
        .signature {
            height: 72px !important;
            font-size: 8pt;
            color: #666;
            line-height: 1.2;
        }
        .signature::before {
            content: "Sign Here";
            display: block;
            margin-bottom: 35px;
            font-size: 7pt;
            color: #999;
        }

        /* Compact Rows */
        tr {
            height: 38px;
        }

        .footer {
            text-align: center;
            margin: 40px 0 20px;
            font-size: 10pt;
            color: #444;
            page-break-before: avoid;
        }

        @page {
            margin: 1cm 0.8cm;
            size: A4 landscape;
        }

        @media print {
            body { padding-top: 0; }
            .print-header {
                position: running(header);
                height: 78px;
                padding: 6px 0;
            }
            .faculty-header {
                page-break-before: always;
            }
            .faculty-header:first-child {
                page-break-before: avoid;
            }
            tr { height: 36px; }
            .signature { height: 68px !important; }
            .signature::before { margin-bottom: 32px; }
        }

        /* Running header for PDF */
        @top-center {
            content: element(header);
        }
    </style>
</head>
<body>

<!-- FIXED REPEATING HEADER -->
<div class="print-header">
    <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
    <div class="subtitle">Attendance Register • {{ $today }}</div>
    <div class="generated">Generated: {{ $generated_at }}</div>
    <img src="{{ public_path('images/logo.png') }}" alt="SJUT" class="logo">
</div>

@foreach($faculties as $faculty)
    <div class="faculty-header">
        {{ $faculty->name }}
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="28%" class="student-name">Student Name</th>
                <th width="12%">Reg No</th>
                @foreach($workingDays as $day)
                    <th width="10.5%" @if($day['is_today']) class="today" @endif>
                        {{ $day['short'] }}<br>
                        <small style="font-size:8pt">{{ $day['date'] }}</small>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($faculty->students()->orderBy('first_name')->orderBy('last_name')->get() as $index => $student)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="student-name">
                        {{ $student->first_name }} {{ $student->last_name }}
                    </td>
                    <td><strong>{{ $student->reg_no }}</strong></td>
                    @foreach($workingDays as $day)
                        <td class="signature"></td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="color:#999; font-style:italic;">
                        No students registered in this faculty
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endforeach

<div class="footer">
    <p>
        <strong>Lecturer's Signature:</strong> _______________________________ 
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <strong>Date:</strong> _______________________________
    </p>
</div>

</body>
</html>
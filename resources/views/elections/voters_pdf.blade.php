<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { margin:0; padding-top: 95px; font-family: Arial, sans-serif; }

        .print-header{
            position: fixed; top:0; left:0; right:0;
            height: 86px; background:#fff;
            text-align:center; padding:6px 0;
            border-bottom: 2px solid #4B2E83;
            z-index: 10000; line-height:1.2;
        }
        .print-header .main-title{ color:#4B2E83; font-weight:bold; font-size:12.5pt; margin:0; }
        .print-header .subtitle{ font-size:9pt; color:#333; margin:2px 0; }
        .print-header .draft{ font-weight:bold; color:#4B2E83; font-size:8.5pt; }
        .print-header .logo{ height: 28px; margin-top: 3px; }

        table { width:100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 10px; }
        thead { display: table-header-group !important; }
        tr { page-break-inside: avoid !important; }

        th, td {
            border: 1.4px solid #4B2E83;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
            font-size: 8.2pt;
        }

        th{
            background:#4B2E83 !important;
            color:#fff !important;
            font-weight:bold;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .left { text-align:left; }
        .muted { color:#555; font-size:7.5pt; }

        @page { margin: 1.1cm 0.7cm 1.1cm 0.7cm; size: A4 portrait; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    </style>
</head>

<body>
<div class="print-header">
    <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
    <div class="subtitle">
        {{ $title }} • {{ $election->title }}
    </div>
    <div class="draft">
        Generated: {{ $generatedAt->format('d M Y H:i') }}
        {{--  @if($scope === 'faculty' && $facultyId) • Filter: Faculty ID {{ $facultyId }} @endif
        @if($scope === 'program' && $programId) • Filter: Program ID {{ $programId }} @endif
        @if(!empty($q)) • Search: {{ $q }} @endif  --}}
    </div>
    <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
</div>

<table>
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:24%;">Student</th>
            <th style="width:14%;">Reg No</th>
            <th style="width:18%;">Faculty</th>
            <th style="width:18%;">Program</th>
            <th style="width:7%;">Votes</th>
            <th style="width:14%;">Categories</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="left">
                    <strong>{{ $r->student_name }}</strong>
                    <div class="muted">ID: {{ $r->student_id }}</div>
                </td>
                <td>{{ $r->reg_no ?? '—' }}</td>
                <td class="left">{{ $r->faculty_name ?? '—' }}</td>
                <td class="left">{{ $r->program_name ?? '—' }}</td>
                <td><strong>{{ (int)$r->total_votes }}</strong></td>
                <td>
                    G: {{ (int)$r->global_votes }},
                    F: {{ (int)$r->faculty_votes }},
                    P: {{ (int)$r->program_votes }}
                    <div class="muted">Distinct: {{ (int)$r->categories_participated }}</div>
                </td>
            </tr>
        @endforeach

        @if($rows->isEmpty())
            <tr><td colspan="7">No voters found.</td></tr>
        @endif
    </tbody>
</table>

</body>
</html>

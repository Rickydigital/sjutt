<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Candidate List - {{ $election->title }}</title>
    <style>
        body { margin: 0; padding-top: 86px; font-family: Arial, sans-serif; }

        .print-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 76px;
            background: white;
            border-bottom: 2px solid #4B2E83;
            z-index: 10000;
            padding: 6px 12px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .header-wrap {
            display: table;
            width: 100%;
        }
        .header-left, .header-center, .header-right {
            display: table-cell;
            vertical-align: middle;
        }
        .header-left { width: 70px; }
        .header-right { width: 70px; }

        .logo {
            height: 42px;
        }

        .main-title {
            color: #4B2E83;
            font-weight: bold;
            font-size: 12.5pt;
            margin: 0;
            text-align: center;
        }
        .subtitle {
            font-size: 9pt;
            color: #333;
            margin: 2px 0 0;
            text-align: center;
        }

        .scope-block {
            page-break-before: always;
            padding-top: 8px;
        }
        .scope-block.first {
            page-break-before: auto;
        }

        .scope-title {
            background: #4B2E83;
            color: #fff;
            padding: 8px 10px;
            font-weight: bold;
            border-radius: 4px;
            margin: 0 0 10px 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .position-title {
            background: #f3f0fb;
            border: 1px solid #4B2E83;
            color: #4B2E83;
            padding: 7px 10px;
            font-weight: bold;
            border-radius: 4px;
            margin: 10px 0 6px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            table-layout: fixed;
        }
        thead { display: table-header-group !important; }
        tr { page-break-inside: avoid !important; }

        th, td {
            border: 1.2px solid #4B2E83;
            padding: 6px 6px;
            vertical-align: top;
        }

        th {
            background: #4B2E83;
            color: #fff;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .photo {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: #eee;
        }

        .muted { color: #666; font-size: 8pt; }

        .badge{
            display:inline-block;
            padding: 1px 4px;      /* smaller */
            border-radius: 8px;    /* tighter */
            font-size: 6.2pt;      /* smaller text */
            font-weight: bold;
            line-height: 1;        /* compact */
        }

        .badge-approved { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-pending  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        @page {
            margin: 1.1cm 0.7cm 1.1cm 0.7cm;
            size: A4 portrait;
        }
    </style>
</head>
<body>

    <div class="print-header">
        <div class="header-wrap">
            <div class="header-left">
                {{-- Put your logo in public/images/logo.png --}}
                <img src="{{ public_path('images/logo.png') }}" alt="LOGO" class="logo">
            </div>

            <div class="header-center">
                <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
                <div class="subtitle">{{ $election->title }} • Candidate List</div>
            </div>

            <div class="header-right">
                {{-- keep empty for symmetry --}}
            </div>
        </div>
    </div>

    @php
        $scopeLabels = [
            'global' => 'GLOBAL POSITIONS',
            'program' => 'PROGRAM POSITIONS',
            'faculty' => 'FACULTY POSITIONS',
        ];
        $scopeOrder = ['global', 'program', 'faculty'];
    @endphp

    @foreach($scopeOrder as $scope)
        @php $positions = $grouped->get($scope, collect()); @endphp
        @if($positions->isNotEmpty())
            <div class="scope-block {{ $loop->first ? 'first' : '' }}">
                

                @foreach($positions as $pos)
                    @php
                        $posName = $pos->definition?->name ?? 'Position';
                        $posDesc = $pos->definition?->description ?? null;
                        $cands   = $pos->candidates ?? collect();
                    @endphp

                    <div class="position-title">
                        {{ $posName }}
                        <span class="muted"> • Candidates: {{ $cands->count() }}</span>
                        @if($posDesc)
                            <div class="muted" style="margin-top:3px;">{{ $posDesc }}</div>
                        @endif
                    </div>

                    @if($cands->isEmpty())
                        <div class="muted">No candidates in this position.</div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Photo</th>
                                    <th style="width: 28%;">Candidate</th>
                                    <th style="width: 18%;">Reg No</th>
                                    <th style="width: 18%;">Class</th>
                                    <th style="width: 18%;">Program</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cands as $cand)
                                    @php
                                        $s = $cand->student;
                                        $photoPath = $cand->photo ? public_path('storage/' . $cand->photo) : null;
                                        $faculty = $s?->faculty?->name ?? '—';
                                        $program = $s?->program?->name ?? '—';
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($photoPath && file_exists($photoPath))
                                                <img src="{{ $photoPath }}" class="photo" alt="photo">
                                            @else
                                                <div class="photo"></div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ trim(($s?->first_name ?? '').' '.($s?->last_name ?? '')) ?: '—' }}</strong>
                                            @if($cand->description)
                                                <div class="muted" style="margin-top:4px;">
                                                    {{ $cand->description }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $s?->reg_no ?? '—' }}</td>
                                        <td>{{ $faculty }}</td>
                                        <td>{{ $program }}</td>
                                        <td>
                                            @if($cand->is_approved)
                                                <span class="badge badge-approved">APPROVED</span>
                                            @else
                                                <span class="badge badge-pending">PENDING</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endforeach
            </div>
        @endif
    @endforeach

</body>
</html>

{{-- resources/views/officer/results/published_pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Published Results • {{ $election->title }}</title>

    <style>
        /* ====== PAGE SETUP ====== */
        @page { margin: 1.1cm 0.7cm 1.1cm 0.7cm; size: A4 portrait; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { margin:0; padding-top: 95px; font-family: Arial, sans-serif; color:#111; }

        /* ====== HEADER ====== */
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

        /* ====== TABLE STYLE (like your sample) ====== */
        table { width:100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 10px; }
        thead { display: table-header-group !important; }
        tr { page-break-inside: avoid !important; }

        th, td {
            border: 1.4px solid #4B2E83;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
            font-size: 8.2pt;
            word-wrap: break-word;
        }

        th{
            background:#4B2E83 !important;
            color:#fff !important;
            font-weight:bold;
        }

        .left { text-align:left; }
        .right { text-align:right; }
        .muted { color:#555; font-size:7.5pt; }

        /* ====== SECTION TITLES ====== */
        .section-title{
            margin: 10px 0 6px 0;
            font-weight: bold;
            color:#4B2E83;
            font-size: 10.5pt;
        }

        .scope-chip{
            display:inline-block;
            border: 1.4px solid #4B2E83;
            color:#4B2E83;
            padding: 2px 8px;
            border-radius: 14px;
            font-size: 8pt;
            font-weight: bold;
        }

        .summary{
            width:100%;
            margin: 4px 0 10px 0;
            border: 1.4px solid #4B2E83;
        }
        .summary td{
            border: 1.4px solid #4B2E83;
            font-size: 8.2pt;
            padding: 5px;
        }

        /* ====== PAGE BREAK ====== */
        .page-break { page-break-after: always; }
        .avoid-break { page-break-inside: avoid; }

        /* Winner highlight */
        .winner-row td{ font-weight:bold; }
        .winner-badge{
            display:inline-block;
            padding: 1px 6px;
            border: 1.4px solid #4B2E83;
            color:#4B2E83;
            border-radius: 10px;
            font-size: 7.6pt;
            margin-left: 4px;
        }
    </style>
</head>

<body>

@php
    // ✅ Use maps passed from controller (already correct)
    // $programMap: [id => short_name/name]
    // $facultyMap: [id => name]

    // Ensure $programMap and $facultyMap are defined (fallback to empty array if not)
    $programMap = $programMap ?? [];
    $facultyMap = $facultyMap ?? [];

    $scopeName = function(string $type, ?int $programId, ?int $facultyId) use ($programMap, $facultyMap) {
        if ($type === 'global') return 'GLOBAL';
        if ($type === 'program') {
            $id = (int)($programId ?? 0);
            return $programMap[$id] ?? 'Program';
        }
        if ($type === 'faculty') {
            $id = (int)($facultyId ?? 0);
            return $facultyMap[$id] ?? 'Faculty';
        }
        return strtoupper($type);
    };

    $scopeTitleLine = function(string $type, ?int $programId, ?int $facultyId) use ($scopeName) {
        $base = strtoupper($type);
        if ($type === 'global') return $base;
        return $base . ' • ' . $scopeName($type, $programId, $facultyId);
    };

    $renderSummary = function($scopeRow){
        $eligible = (int)($scopeRow->eligible_students ?? 0);
        $voters   = (int)($scopeRow->voters ?? 0);
        $turnout  = (float)($scopeRow->turnout_percent ?? 0);
        $turnout  = min(100, max(0, $turnout));
        return [$eligible, $voters, $turnout];
    };
@endphp

{{-- FIXED HEADER --}}
<div class="print-header">
    <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
    <div class="subtitle">
        Published Election Results • {{ $election->title }}
    
    </div>
    <div class="draft">
        Published: {{ optional($publish->published_at)->format('d M Y H:i') }}
        
    </div>
    <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
</div>

{{-- ======================================================
     PAGE 1: GLOBAL (all global positions + candidates + winner)
     ====================================================== --}}
@php
    $g = $globalScope;
    $gPositions = $g ? ($positionsByScopeId[$g->id] ?? collect()) : collect();
    [$gEligible, $gVoters, $gTurnout] = $renderSummary($g);
@endphp

<div class="section-title">
    <span class="scope-chip">GLOBAL</span>
    <span style="margin-left:8px;">SCOPE SUMMARY</span>
</div>

<table class="summary">
    <tr>
        <td class="left"><strong>Eligible Active Students:</strong> {{ number_format($gEligible) }}</td>
        <td class="left"><strong>Voters:</strong> {{ number_format($gVoters) }}</td>
        <td class="left"><strong>Turnout:</strong> {{ $gTurnout }}%</td>
    </tr>
</table>

@foreach($gPositions as $pos)
    @php
        $eligible = (int)($pos->eligible_students ?? 0);
        $voters   = (int)($pos->voters ?? 0);
        $turnout  = min(100, max(0, (float)($pos->turnout_percent ?? 0)));
        $cands    = collect($pos->candidates ?? []);
    @endphp

    <div class="section-title avoid-break">
        {{ $pos->position_name }}
        <span class="muted">
            • Eligible: {{ number_format($eligible) }}
            • Voters: {{ number_format($voters) }}
            • Turnout: {{ $turnout }}%
        </span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:6%;">Rank</th>
                <th style="width:44%;">Candidate</th>
                <th style="width:16%;">Reg No</th>
                <th style="width:10%;">Votes</th>
                <th style="width:24%;">% of Eligible</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cands as $cand)
                @php
                    $isWinner = (int)($cand->is_winner ?? 0) === 1 || (int)($cand->rank ?? 0) === 1;
                    $pct = min(100, max(0, (float)($cand->vote_percent ?? 0)));
                @endphp
                <tr class="{{ $isWinner ? 'winner-row' : '' }}">
                    <td>{{ $cand->rank ?? '—' }}</td>
                    <td class="left">
                        <strong>{{ $cand->candidate_name ?? '—' }}</strong>
                        @if($isWinner)
                            <span class="winner-badge">WINNER</span>
                        @endif
                    </td>
                    <td>{{ $cand->candidate_reg_no ?? '—' }}</td>
                    <td><strong>{{ number_format((int)($cand->vote_count ?? 0)) }}</strong></td>
                    <td>{{ $pct }}%</td>
                </tr>
            @empty
                <tr><td colspan="5">No candidates saved for this position.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="muted" style="margin-top:-6px; margin-bottom: 10px;">
        % of Eligible = candidate votes ÷ eligible active students for this position scope.
    </div>
@endforeach

<div class="page-break"></div>

{{-- ======================================================
     PROGRAM SCOPES (each program on its own page)
     ====================================================== --}}
@foreach($programScopes as $scope)
    @php
        $scopeTitle = $scopeTitleLine('program', (int)($scope->program_id ?? 0), null);
        [$eligible, $voters, $turnout] = $renderSummary($scope);
        $scopePositions = $positionsByScopeId[$scope->id] ?? collect();
    @endphp

    <div class="section-title">
        <span class="scope-chip">PROGRAM</span>
        <span style="margin-left:8px;">{{ $scopeTitle }}</span>
    </div>

    <table class="summary">
        <tr>
            <td class="left"><strong>Eligible Active Students:</strong> {{ number_format($eligible) }}</td>
            <td class="left"><strong>Voters:</strong> {{ number_format($voters) }}</td>
            <td class="left"><strong>Turnout:</strong> {{ $turnout }}%</td>
        </tr>
    </table>

    @foreach($scopePositions as $pos)
        @php
            $pEligible = (int)($pos->eligible_students ?? 0);
            $pVoters   = (int)($pos->voters ?? 0);
            $pTurnout  = min(100, max(0, (float)($pos->turnout_percent ?? 0)));
            $cands     = collect($pos->candidates ?? []);
        @endphp

        <div class="section-title avoid-break">
            {{ $pos->position_name }}
            <span class="muted">
                • Eligible: {{ number_format($pEligible) }}
                • Voters: {{ number_format($pVoters) }}
                • Turnout: {{ $pTurnout }}%
            </span>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:6%;">Rank</th>
                    <th style="width:44%;">Candidate</th>
                    <th style="width:16%;">Reg No</th>
                    <th style="width:10%;">Votes</th>
                    <th style="width:24%;">% of Eligible</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cands as $cand)
                    @php
                        $isWinner = (int)($cand->is_winner ?? 0) === 1 || (int)($cand->rank ?? 0) === 1;
                        $pct = min(100, max(0, (float)($cand->vote_percent ?? 0)));
                    @endphp
                    <tr class="{{ $isWinner ? 'winner-row' : '' }}">
                        <td>{{ $cand->rank ?? '—' }}</td>
                        <td class="left">
                            <strong>{{ $cand->candidate_name ?? '—' }}</strong>
                            @if($isWinner)
                                <span class="winner-badge">WINNER</span>
                            @endif
                        </td>
                        <td>{{ $cand->candidate_reg_no ?? '—' }}</td>
                        <td><strong>{{ number_format((int)($cand->vote_count ?? 0)) }}</strong></td>
                        <td>{{ $pct }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No candidates saved for this position.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="muted" style="margin-top:-6px; margin-bottom: 10px;">
            % of Eligible = candidate votes ÷ eligible active students for this position scope.
        </div>
    @endforeach

    <div class="page-break"></div>
@endforeach

{{-- ======================================================
     FACULTY SCOPES (each faculty on its own page)
     ====================================================== --}}
@foreach($facultyScopes as $scope)
    @php
        $scopeTitle = $scopeTitleLine('faculty', null, (int)($scope->faculty_id ?? 0));
        [$eligible, $voters, $turnout] = $renderSummary($scope);
        $scopePositions = $positionsByScopeId[$scope->id] ?? collect();
    @endphp

    <div class="section-title">
        <span class="scope-chip">FACULTY</span>
        <span style="margin-left:8px;">{{ $scopeTitle }}</span>
    </div>

    <table class="summary">
        <tr>
            <td class="left"><strong>Eligible Active Students:</strong> {{ number_format($eligible) }}</td>
            <td class="left"><strong>Voters:</strong> {{ number_format($voters) }}</td>
            <td class="left"><strong>Turnout:</strong> {{ $turnout }}%</td>
        </tr>
    </table>

    @foreach($scopePositions as $pos)
        @php
            $pEligible = (int)($pos->eligible_students ?? 0);
            $pVoters   = (int)($pos->voters ?? 0);
            $pTurnout  = min(100, max(0, (float)($pos->turnout_percent ?? 0)));
            $cands     = collect($pos->candidates ?? []);
        @endphp

        <div class="section-title avoid-break">
            {{ $pos->position_name }}
            <span class="muted">
                • Eligible: {{ number_format($pEligible) }}
                • Voters: {{ number_format($pVoters) }}
                • Turnout: {{ $pTurnout }}%
            </span>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:6%;">Rank</th>
                    <th style="width:44%;">Candidate</th>
                    <th style="width:16%;">Reg No</th>
                    <th style="width:10%;">Votes</th>
                    <th style="width:24%;">% of Eligible</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cands as $cand)
                    @php
                        $isWinner = (int)($cand->is_winner ?? 0) === 1 || (int)($cand->rank ?? 0) === 1;
                        $pct = min(100, max(0, (float)($cand->vote_percent ?? 0)));
                    @endphp
                    <tr class="{{ $isWinner ? 'winner-row' : '' }}">
                        <td>{{ $cand->rank ?? '—' }}</td>
                        <td class="left">
                            <strong>{{ $cand->candidate_name ?? '—' }}</strong>
                            @if($isWinner)
                                <span class="winner-badge">WINNER</span>
                            @endif
                        </td>
                        <td>{{ $cand->candidate_reg_no ?? '—' }}</td>
                        <td><strong>{{ number_format((int)($cand->vote_count ?? 0)) }}</strong></td>
                        <td>{{ $pct }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No candidates saved for this position.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="muted" style="margin-top:-6px; margin-bottom: 10px;">
            % of Eligible = candidate votes ÷ eligible active students for this position scope.
        </div>
    @endforeach

    {{-- avoid extra blank page at the end --}}
    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>

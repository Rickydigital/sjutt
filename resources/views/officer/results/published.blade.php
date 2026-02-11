{{-- resources/views/officer/results/published.blade.php --}}
@extends('officer.layouts.app')

@section('title', $election->title . ' - Published Results')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1 fw-bold">{{ $election->title }}</h4>
                    <div class="text-muted">
                        <span class="badge bg-success">PUBLISHED</span>
                        <span class="ms-2">Version: <strong>#{{ $publish->version }}</strong></span>
                        <span class="ms-2">Published at: <strong>{{ optional($publish->published_at)->format('d M Y, H:i') }}</strong></span>
                    </div>
                    @if(!empty($publish->notes))
                        <div class="text-muted mt-1 small">
                            <i class="bi bi-info-circle me-1"></i> {{ $publish->notes }}
                        </div>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('officer.results.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>

                    
                </div>
            </div>
        </div>
    </div>

    {{-- Scopes Summary --}}
    @php
        /** expected from controller:
         * $globalScope (single)
         * $programScopes (collection)
         * $facultyScopes (collection)
         * $programMap (id => short_name or name)  (optional but recommended)
         * $facultyMap (id => name)                (optional but recommended)
         */

        $global   = $globalScope ?? null;
        $programs = $programScopes ?? collect();
        $faculties= $facultyScopes ?? collect();

        $globalEligible = (int)($global?->eligible_students ?? 0);
        $globalVoters   = (int)($global?->voters ?? 0);
        $globalTurnout  = min(100, max(0, (float)($global?->turnout_percent ?? 0)));

        $programEligible = (int)$programs->sum('eligible_students');
        $programVoters   = (int)$programs->sum('voters');
        $programTurnout  = $programEligible > 0 ? round(($programVoters / $programEligible) * 100, 2) : 0;

        $facultyEligible = (int)$faculties->sum('eligible_students');
        $facultyVoters   = (int)$faculties->sum('voters');
        $facultyTurnout  = $facultyEligible > 0 ? round(($facultyVoters / $facultyEligible) * 100, 2) : 0;
    @endphp

    <div class="row g-4 mb-4">
        {{-- Global --}}
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted small fw-semibold mb-1">Global Scope</div>
                            <span class="badge bg-success px-3 py-2">
                                <i class="bi bi-globe me-1"></i> GLOBAL
                            </span>
                        </div>
                        <div class="text-muted small">
                            Turnout
                            <div class="h4 fw-bold mb-0">{{ $globalTurnout }}%</div>
                        </div>
                    </div>

                    <div class="mt-3 text-muted">
                        Eligible: <strong class="text-dark">{{ number_format($globalEligible) }}</strong><br>
                        Voters: <strong class="text-dark">{{ number_format($globalVoters) }}</strong>
                    </div>

                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $globalTurnout }}%"></div>
                    </div>

                    @if($globalEligible <= 0)
                        <div class="alert alert-warning mt-3 mb-0 py-2 small">
                            No eligible active students detected for global scope.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Program aggregate --}}
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted small fw-semibold mb-1">Program Scope (Aggregate)</div>
                            <span class="badge bg-info text-dark px-3 py-2">
                                <i class="bi bi-diagram-3 me-1"></i> PROGRAM
                            </span>
                            <div class="text-muted small mt-2">
                                Programs: <strong class="text-dark">{{ number_format($programs->count()) }}</strong>
                            </div>
                        </div>
                        <div class="text-muted small">
                            Turnout
                            <div class="h4 fw-bold mb-0">{{ $programTurnout }}%</div>
                        </div>
                    </div>

                    <div class="mt-3 text-muted">
                        Eligible: <strong class="text-dark">{{ number_format($programEligible) }}</strong><br>
                        Voters: <strong class="text-dark">{{ number_format($programVoters) }}</strong>
                    </div>

                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ min(100,max(0,$programTurnout)) }}%"></div>
                    </div>

                    @if($programEligible <= 0)
                        <div class="alert alert-warning mt-3 mb-0 py-2 small">
                            No eligible active students detected for program scope.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Faculty aggregate --}}
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted small fw-semibold mb-1">Faculty Scope (Aggregate)</div>
                            <span class="badge bg-primary px-3 py-2">
                                <i class="bi bi-buildings me-1"></i> FACULTY
                            </span>
                            <div class="text-muted small mt-2">
                                Faculties: <strong class="text-dark">{{ number_format($faculties->count()) }}</strong>
                            </div>
                        </div>
                        <div class="text-muted small">
                            Turnout
                            <div class="h4 fw-bold mb-0">{{ $facultyTurnout }}%</div>
                        </div>
                    </div>

                    <div class="mt-3 text-muted">
                        Eligible: <strong class="text-dark">{{ number_format($facultyEligible) }}</strong><br>
                        Voters: <strong class="text-dark">{{ number_format($facultyVoters) }}</strong>
                    </div>

                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ min(100,max(0,$facultyTurnout)) }}%"></div>
                    </div>

                    @if($facultyEligible <= 0)
                        <div class="alert alert-warning mt-3 mb-0 py-2 small">
                            No eligible active students detected for faculty scope.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Program/Faculty detailed lists (so you can see ALL programs/faculties) --}}
    <div class="row g-4 mb-4">
        {{-- Programs list --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="fw-bold">
                        <i class="bi bi-diagram-3 me-1"></i> Program Scope Details
                    </div>
                    <span class="text-muted small">{{ number_format($programs->count()) }} programs</span>
                </div>

                <div class="card-body">
                    @if($programs->isEmpty())
                        <div class="text-muted">No program scopes found in this published version.</div>
                    @else
                        <div class="accordion" id="programScopesAccordion">
                            @foreach($programs as $i => $row)
                                @php
                                    $pid = (int)($row->program_id ?? 0);
                                    $pname = $programMap[$pid] ?? ('Program #' . $pid);
                                    $eligible = (int)($row->eligible_students ?? 0);
                                    $voters   = (int)($row->voters ?? 0);
                                    $turnout  = (float)($row->turnout_percent ?? 0);
                                    $turnout  = min(100, max(0, $turnout));
                                @endphp

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="progScopeHead{{ $i }}">
                                        <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#progScopeCol{{ $i }}">
                                            <div class="d-flex justify-content-between w-100 align-items-center">
                                                <span class="fw-semibold">{{ $pname }}</span>
                                                <span class="text-muted small">
                                                    Eligible: <strong class="text-dark">{{ number_format($eligible) }}</strong>
                                                    • Voters: <strong class="text-dark">{{ number_format($voters) }}</strong>
                                                    • Turnout: <strong class="text-dark">{{ $turnout }}%</strong>
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="progScopeCol{{ $i }}" class="accordion-collapse collapse"
                                         data-bs-parent="#programScopesAccordion">
                                        <div class="accordion-body">
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar" style="width: {{ $turnout }}%"></div>
                                            </div>
                                            <div class="text-muted small mt-2">
                                                This is the snapshot for voters who belong to <strong>{{ $pname }}</strong>.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Faculties list --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="fw-bold">
                        <i class="bi bi-buildings me-1"></i> Faculty Scope Details
                    </div>
                    <span class="text-muted small">{{ number_format($faculties->count()) }} faculties</span>
                </div>

                <div class="card-body">
                    @if($faculties->isEmpty())
                        <div class="text-muted">No faculty scopes found in this published version.</div>
                    @else
                        <div class="accordion" id="facultyScopesAccordion">
                            @foreach($faculties as $i => $row)
                                @php
                                    $fid = (int)($row->faculty_id ?? 0);
                                    $fname = $facultyMap[$fid] ?? ('Faculty #' . $fid);
                                    $eligible = (int)($row->eligible_students ?? 0);
                                    $voters   = (int)($row->voters ?? 0);
                                    $turnout  = (float)($row->turnout_percent ?? 0);
                                    $turnout  = min(100, max(0, $turnout));
                                @endphp

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="facScopeHead{{ $i }}">
                                        <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#facScopeCol{{ $i }}">
                                            <div class="d-flex justify-content-between w-100 align-items-center">
                                                <span class="fw-semibold">{{ $fname }}</span>
                                                <span class="text-muted small">
                                                    Eligible: <strong class="text-dark">{{ number_format($eligible) }}</strong>
                                                    • Voters: <strong class="text-dark">{{ number_format($voters) }}</strong>
                                                    • Turnout: <strong class="text-dark">{{ $turnout }}%</strong>
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="facScopeCol{{ $i }}" class="accordion-collapse collapse"
                                         data-bs-parent="#facultyScopesAccordion">
                                        <div class="accordion-body">
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar" style="width: {{ $turnout }}%"></div>
                                            </div>
                                            <div class="text-muted small mt-2">
                                                This is the snapshot for voters who belong to <strong>{{ $fname }}</strong>.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Positions --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold">Published Results by Position</h5>
            <span class="text-muted small">Priority: Global → Program → Faculty</span>
        </div>

        <div class="card-body">
            @if ($positions->isEmpty())
                <div class="alert alert-info mb-0 text-center py-5">
                    No positions found in this published version.
                </div>
            @else
                <div class="accordion" id="publishedPositionsAccordion">

                    @foreach($positions as $pos)
                        @php

                            $scopeLabel = null;
                            $scopeBadge = match($pos->scope_type) {
                                'global' => 'bg-success',
                                'program' => 'bg-info text-dark',
                                'faculty' => 'bg-primary',
                                default => 'bg-secondary',
                            };

                            $scopeIcon = match($pos->scope_type) {
                                'global' => 'bi-globe',
                                'program' => 'bi-diagram-3',
                                'faculty' => 'bi-buildings',
                                default => 'bi-circle',
                            };

                            if ($pos->scope_type === 'program' && !empty($pos->program_id)) {
                                    $scopeLabel = $programMap[$pos->program_id] ?? null;
                                }

                                // faculty scope position: show faculty name
                                if ($pos->scope_type === 'faculty' && !empty($pos->faculty_id)) {
                                    $scopeLabel = $facultyMap[$pos->faculty_id] ?? null;
                                }

                            $eligible = (int)($pos->eligible_students ?? 0);
                            $voters   = (int)($pos->voters ?? 0);
                            $turnout  = (float)($pos->turnout_percent ?? 0);
                            $turnout  = min(100, max(0, $turnout));

                            $cands = collect($pos->candidates ?? []);
                        @endphp

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPub{{ $pos->id }}">
                                <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapsePub{{ $pos->id }}"
                                        aria-expanded="false"
                                        aria-controls="collapsePub{{ $pos->id }}">
                                    <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi {{ $scopeIcon }}"></i>
                                            <span>{{ $pos->position_name }}</span>
                                            <span class="badge {{ $scopeBadge }}">
                                                {{ strtoupper($pos->scope_type) }}
                                                @if($scopeLabel)
                                                    <span class="ms-1">({{ $scopeLabel }})</span>
                                                @endif
                                            </span>
                                        </div>

                                        <div class="text-muted small">
                                            Eligible: <strong class="text-dark">{{ number_format($eligible) }}</strong>
                                            • Voters: <strong class="text-dark">{{ number_format($voters) }}</strong>
                                            • Turnout: <strong class="text-dark">{{ $turnout }}%</strong>
                                        </div>
                                    </div>
                                </button>
                            </h2>

                            <div id="collapsePub{{ $pos->id }}" class="accordion-collapse collapse"
                                 aria-labelledby="headingPub{{ $pos->id }}"
                                 data-bs-parent="#publishedPositionsAccordion">

                                <div class="accordion-body">

                                    {{-- Position turnout mini card --}}
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            <div class="p-3 rounded border bg-light h-100">
                                                <div class="text-muted small fw-semibold">Eligible Active Students</div>
                                                <div class="h4 fw-bold mb-0">{{ number_format($eligible) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 rounded border bg-light h-100">
                                                <div class="text-muted small fw-semibold">Voters (This Position)</div>
                                                <div class="h4 fw-bold mb-0">{{ number_format($voters) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 rounded border bg-light h-100">
                                                <div class="text-muted small fw-semibold">Turnout</div>
                                                <div class="h4 fw-bold mb-0">{{ $turnout }}%</div>
                                                <div class="progress mt-2" style="height:10px;">
                                                    <div class="progress-bar" style="width: {{ $turnout }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Candidates --}}
                                    @if($eligible <= 0)
                                        <div class="alert alert-warning mb-0">
                                            No eligible students found for this position scope. Check scope targets and student status.
                                        </div>
                                    @elseif($cands->isEmpty())
                                        <div class="alert alert-info mb-0">
                                            No candidate results saved in this published version.
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover align-middle">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 10%">Rank</th>
                                                        <th style="width: 45%">Candidate</th>
                                                        <th style="width: 15%">Reg No</th>
                                                        <th style="width: 10%" class="text-end">Votes</th>
                                                        <th style="width: 20%">% of Eligible</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($cands as $cand)
                                                        @php
                                                            $isWinner = (int)($cand->is_winner ?? 0) === 1 || (int)($cand->rank ?? 0) === 1;
                                                            $pct = (float)($cand->vote_percent ?? 0);
                                                            $pct = min(100, max(0, $pct));
                                                        @endphp
                                                        <tr class="{{ $isWinner ? 'table-success fw-bold' : '' }}">
                                                            <td>
                                                                @if($isWinner)
                                                                    <i class="bi bi-trophy-fill text-warning me-1"></i>
                                                                @endif
                                                                {{ $cand->rank ?? '—' }}
                                                            </td>
                                                            <td>
                                                                {{ $cand->candidate_name ?? '—' }}
                                                                @if($isWinner)
                                                                    <span class="badge bg-success ms-2">WINNER</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $cand->candidate_reg_no ?? '—' }}</td>
                                                            <td class="text-end">{{ number_format((int)($cand->vote_count ?? 0)) }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <div class="progress flex-grow-1" style="height: 10px;">
                                                                        <div class="progress-bar" style="width: {{ $pct }}%"></div>
                                                                    </div>
                                                                    <span class="small fw-semibold">{{ $pct }}%</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="text-muted small mt-2">
                                            % of Eligible = candidate votes ÷ eligible active students for this position scope.
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            @endif
        </div>
    </div>
</div>

{{-- Print styles --}}
<style>
@media print {
    .btn, .nav, .accordion-button { display: none !important; }
    .accordion-collapse { display: block !important; }
    .accordion-item { break-inside: avoid; }
}
</style>
@endsection

{{-- resources/views/officer/results/show.blade.php --}}
@extends('officer.layouts.app')

@section('title', $election->title . ' - Results')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1 fw-bold">{{ $election->title }}</h4>
                    <div class="text-muted">
                        {{ optional($election->start_date)->format('d M Y') }} —
                        {{ optional($election->end_date)->format('d M Y') }}
                        • <span class="badge bg-secondary">{{ strtoupper($election->status) }}</span>
                    </div>
                </div>
                <a href="{{ route('officer.results.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Results List
                </a>

                <form method="POST" action="{{ route('officer.results.publish', $election) }}">
    @csrf
    <button type="submit" class="btn btn-success">
        <i class="bi bi-megaphone me-1"></i> Publish Results
    </button>
</form>

            </div>
        </div>
    </div>

    {{-- Overall Turnout --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-uppercase text-muted small fw-semibold mb-2">Overall Turnout</div>
                    <div class="display-5 fw-bold text-primary mb-1">{{ $overallTurnoutPercent }}%</div>
                    <div class="text-muted">
                        {{ number_format($votersCount) }} / {{ number_format($totalActiveStudents) }} active students voted
                    </div>

                    @php $percent = min(100, max(0, (float)$overallTurnoutPercent)); @endphp
                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick legend --}}
        <div class="col-lg-8 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small fw-semibold mb-2">Scope Legend</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-success px-3 py-2"><i class="bi bi-globe me-1"></i> Global</span>
                        <span class="badge bg-info text-dark px-3 py-2"><i class="bi bi-diagram-3 me-1"></i> Program</span>
                        <span class="badge bg-primary px-3 py-2"><i class="bi bi-buildings me-1"></i> Faculty</span>
                    </div>
                    <div class="text-muted mt-3">
                        Results are shown per position. Percentages in <strong>Overall Results</strong> are based on
                        <strong>eligible active students for that position scope</strong>.
                        <br>
                        <strong>Scope Breakdown</strong> shows results per Faculty/Program for <strong>ALL scopes (including Global)</strong>,
                        and includes <strong>all candidates</strong> (even 0 votes) with progress bars.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Positions --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold">Results by Position</h5>
            <span class="text-muted small">Priority: Global → Program → Faculty</span>
        </div>

        <div class="card-body">
            @if (empty($resultsPerPosition) || $resultsPerPosition->isEmpty())
                <div class="alert alert-info mb-0 text-center py-5">
                    No positions/results available for this election.
                </div>
            @else
                <div class="accordion" id="positionsResultsAccordion">

                    @foreach ($resultsPerPosition as $pos)
                        @php
                            $scopeBadge = match($pos->scope_type) {
                                'global' => 'bg-success',
                                'program' => 'bg-info text-dark',
                                'faculty' => 'bg-primary',
                                default   => 'bg-secondary',
                            };

                            $scopeIcon = match($pos->scope_type) {
                                'global' => 'bi-globe',
                                'program' => 'bi-diagram-3',
                                'faculty' => 'bi-buildings',
                                default   => 'bi-circle',
                            };

                            $posId = $pos->position_id;

                            // overall block (contains eligible/voters/turnout_percent)
                            $overall = $pos->overall ?? null;

                            $eligibleStudents = (int)($overall['eligible_students'] ?? ($pos->eligible_students ?? 0));
                            $positionVoters   = (int)($overall['voters'] ?? 0);
                            $turnoutPercent   = (float)($overall['turnout_percent'] ?? 0);

                            $overallCandidates = collect($overall['candidates'] ?? []);

                            // ✅ NEW collections produced by updated controller:
                            // groupBy(faculty_id) / groupBy(program_id) and includes 0 votes candidates
                            $byFacultyAll = $pos->by_faculty_all ?? collect(); // [faculty_id => rows]
                            $byProgramAll = $pos->by_program_all ?? collect(); // [program_id => rows]

                            // optional scope turnout tables (only filled for program/faculty scoped positions)
                            $turnoutByProgram = $pos->turnout_by_program ?? collect();
                            $turnoutByFaculty = $pos->turnout_by_faculty ?? collect();

                            // trends blocks
                            $supportFaculty = $pos->support_faculty ?? collect();
                            $supportProgram = $pos->support_program ?? collect();

                            $tabsId = "tabs-{$posId}";
                        @endphp

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPos{{ $posId }}">
                                <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapsePos{{ $posId }}"
                                        aria-expanded="false"
                                        aria-controls="collapsePos{{ $posId }}">
                                    <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi {{ $scopeIcon }}"></i>
                                            <span>{{ $pos->position_name }}</span>
                                            <span class="badge {{ $scopeBadge }}">{{ strtoupper($pos->scope_type) }}</span>
                                        </div>

                                        <div class="text-muted small">
                                            Eligible: <strong class="text-dark">{{ number_format($eligibleStudents) }}</strong>
                                            • Voters: <strong class="text-dark">{{ number_format($positionVoters) }}</strong>
                                            • Turnout: <strong class="text-dark">{{ $turnoutPercent }}%</strong>
                                        </div>
                                    </div>
                                </button>
                            </h2>

                            <div id="collapsePos{{ $posId }}" class="accordion-collapse collapse"
                                 aria-labelledby="headingPos{{ $posId }}"
                                 data-bs-parent="#positionsResultsAccordion">
                                <div class="accordion-body">

                                    {{-- Tabs --}}
                                    <ul class="nav nav-pills mb-3" id="{{ $tabsId }}" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active"
                                                    id="overall-tab-{{ $posId }}"
                                                    data-bs-toggle="pill"
                                                    data-bs-target="#overall-{{ $posId }}"
                                                    type="button" role="tab">
                                                <i class="bi bi-award me-1"></i> Overall Results
                                            </button>
                                        </li>

                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link"
                                                    id="breakdown-tab-{{ $posId }}"
                                                    data-bs-toggle="pill"
                                                    data-bs-target="#breakdown-{{ $posId }}"
                                                    type="button" role="tab">
                                                <i class="bi bi-grid-3x3-gap me-1"></i> Scope Breakdown
                                            </button>
                                        </li>

                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link"
                                                    id="trends-tab-{{ $posId }}"
                                                    data-bs-toggle="pill"
                                                    data-bs-target="#trends-{{ $posId }}"
                                                    type="button" role="tab">
                                                <i class="bi bi-graph-up-arrow me-1"></i> Trends
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">

                                        {{-- =======================
                                             TAB 1: OVERALL
                                             ======================= --}}
                                        <div class="tab-pane fade show active" id="overall-{{ $posId }}" role="tabpanel">
                                            @if ($eligibleStudents <= 0)
                                                <div class="alert alert-warning mb-0">
                                                    <strong>No eligible students found</strong> for this position scope.
                                                    Check pivot targets (faculties/programs) or student status.
                                                </div>
                                            @elseif ($overallCandidates->isEmpty())
                                                <div class="alert alert-info mb-0">
                                                    No candidates/votes recorded for this position yet.
                                                </div>
                                            @else
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-4">
                                                        <div class="p-3 rounded border bg-light h-100">
                                                            <div class="text-muted small fw-semibold">Eligible Active Students</div>
                                                            <div class="h4 fw-bold mb-0">{{ number_format($eligibleStudents) }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-3 rounded border bg-light h-100">
                                                            <div class="text-muted small fw-semibold">Voters (This Position)</div>
                                                            <div class="h4 fw-bold mb-0">{{ number_format($positionVoters) }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-3 rounded border bg-light h-100">
                                                            <div class="text-muted small fw-semibold">Turnout</div>
                                                            <div class="h4 fw-bold mb-0">{{ $turnoutPercent }}%</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover align-middle">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 10%">Rank</th>
                                                                <th style="width: 40%">Candidate</th>
                                                                <th style="width: 15%">Reg No</th>
                                                                <th style="width: 15%">Votes</th>
                                                                <th style="width: 20%">% of Eligible</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($overallCandidates as $cand)
                                                                @php
                                                                    $isWinner = ($cand->rank ?? null) === 1;
                                                                    $vp = (float)($cand->vote_percent ?? 0);
                                                                    $vp = min(100, max(0, $vp));
                                                                @endphp
                                                                <tr class="{{ $isWinner ? 'table-success fw-bold' : '' }}">
                                                                    <td>
                                                                        @if($isWinner)
                                                                            <i class="bi bi-trophy-fill text-warning me-1"></i>
                                                                        @endif
                                                                        {{ $cand->rank ?? '—' }}
                                                                    </td>
                                                                    <td>{{ $cand->candidate_name }}</td>
                                                                    <td>{{ $cand->candidate_reg_no ?? '—' }}</td>
                                                                    <td>{{ number_format($cand->vote_count) }}</td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <div class="progress flex-grow-1" style="height: 10px;">
                                                                                <div class="progress-bar" style="width: {{ $vp }}%"></div>
                                                                            </div>
                                                                            <span class="small fw-semibold">{{ $vp }}%</span>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="text-muted small mt-2">
                                                    <strong>% of Eligible</strong> = candidate votes ÷ eligible active students for this position scope.
                                                </div>
                                            @endif
                                        </div>

                                        {{-- =======================
                                             TAB 2: BREAKDOWN
                                             ✅ UPDATED:
                                             - Works for GLOBAL, PROGRAM, FACULTY
                                             - Shows ALL candidates in each group (even 0 votes)
                                             - Shows progress bars (% inside that group)
                                             ======================= --}}
                                        <div class="tab-pane fade" id="breakdown-{{ $posId }}" role="tabpanel">

                                            <div class="alert alert-light border mb-3">
                                                <div class="fw-semibold mb-1">How this breakdown works</div>
                                                <div class="small text-muted">
                                                    Each section shows votes grouped by <strong>where voters came from</strong> (Faculty / Program).
                                                    Inside every Faculty/Program you will see <strong>all candidates</strong> with votes + percentage + progress.
                                                </div>
                                            </div>

                                            <div class="row g-3">

                                                {{-- =======================
                                                     Breakdown by Faculty (ALL scopes)
                                                     ======================= --}}
                                                <div class="col-12">
                                                    <div class="card border">
                                                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                            <strong><i class="bi bi-buildings me-1"></i> Results per Faculty</strong>
                                                            <span class="text-muted small">Shows votes from voters in each faculty</span>
                                                        </div>

                                                        <div class="card-body">
                                                            @if ($byFacultyAll->isEmpty())
                                                                <div class="text-muted">No faculty breakdown data.</div>
                                                            @else
                                                                <div class="accordion" id="facultyAllBreakdown{{ $posId }}">
                                                                    @foreach ($byFacultyAll as $facultyId => $rows)
                                                                        @php
                                                                            $facultyName = $rows->first()->faculty_name ?? 'Faculty';
                                                                            $total = (int) $rows->sum('vote_count');
                                                                            $sorted = $rows->sortByDesc('vote_count')->values();
                                                                        @endphp

                                                                        <div class="accordion-item">
                                                                            <h2 class="accordion-header" id="facAllHead{{ $posId }}-{{ $facultyId }}">
                                                                                <button class="accordion-button collapsed" type="button"
                                                                                        data-bs-toggle="collapse"
                                                                                        data-bs-target="#facAllCol{{ $posId }}-{{ $facultyId }}">
                                                                                    <div class="d-flex justify-content-between w-100 align-items-center">
                                                                                        <span class="fw-semibold">{{ $facultyName }}</span>
                                                                                        <span class="text-muted small">
                                                                                            Votes in faculty: <strong>{{ number_format($total) }}</strong>
                                                                                        </span>
                                                                                    </div>
                                                                                </button>
                                                                            </h2>

                                                                            <div id="facAllCol{{ $posId }}-{{ $facultyId }}" class="accordion-collapse collapse"
                                                                                 data-bs-parent="#facultyAllBreakdown{{ $posId }}">
                                                                                <div class="accordion-body">

                                                                                    @if ($sorted->isEmpty())
                                                                                        <div class="text-muted">No candidates found.</div>
                                                                                    @else
                                                                                        <div class="table-responsive">
                                                                                            <table class="table table-sm table-hover align-middle mb-0">
                                                                                                <thead>
                                                                                                    <tr>
                                                                                                        <th style="width:5%">#</th>
                                                                                                        <th style="width:45%">Candidate</th>
                                                                                                        <th style="width:15%" class="text-end">Votes</th>
                                                                                                        <th style="width:35%">Progress</th>
                                                                                                    </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                    @foreach ($sorted as $i => $r)
                                                                                                        @php
                                                                                                            $pct = $total > 0 ? round(((int)$r->vote_count / $total) * 100, 2) : 0;
                                                                                                            $pctBar = min(100, max(0, (float)$pct));
                                                                                                            $isTop = $i === 0 && (int)$r->vote_count > 0;
                                                                                                        @endphp
                                                                                                        <tr class="{{ $isTop ? 'table-success fw-semibold' : '' }}">
                                                                                                            <td>{{ $i + 1 }}</td>
                                                                                                            <td>{{ $r->candidate_name }}</td>
                                                                                                            <td class="text-end">{{ number_format((int)$r->vote_count) }}</td>
                                                                                                            <td>
                                                                                                                <div class="d-flex align-items-center gap-2">
                                                                                                                    <div class="progress flex-grow-1" style="height: 8px;">
                                                                                                                        <div class="progress-bar" style="width: {{ $pctBar }}%"></div>
                                                                                                                    </div>
                                                                                                                    <span class="small fw-semibold">{{ $pct }}%</span>
                                                                                                                </div>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    @endforeach
                                                                                                </tbody>
                                                                                            </table>
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

                                                {{-- =======================
                                                     Breakdown by Program (ALL scopes)
                                                     ======================= --}}
                                                <div class="col-12">
                                                    <div class="card border">
                                                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                            <strong><i class="bi bi-diagram-3 me-1"></i> Results per Program</strong>
                                                            <span class="text-muted small">Shows votes from voters in each program</span>
                                                        </div>

                                                        <div class="card-body">
                                                            @if ($byProgramAll->isEmpty())
                                                                <div class="text-muted">No program breakdown data.</div>
                                                            @else
                                                                <div class="accordion" id="programAllBreakdown{{ $posId }}">
                                                                    @foreach ($byProgramAll as $programId => $rows)
                                                                        @php
                                                                            $programName = $rows->first()->program_name ?? 'Program';
                                                                            $total = (int) $rows->sum('vote_count');
                                                                            $sorted = $rows->sortByDesc('vote_count')->values();
                                                                        @endphp

                                                                        <div class="accordion-item">
                                                                            <h2 class="accordion-header" id="progAllHead{{ $posId }}-{{ $programId }}">
                                                                                <button class="accordion-button collapsed" type="button"
                                                                                        data-bs-toggle="collapse"
                                                                                        data-bs-target="#progAllCol{{ $posId }}-{{ $programId }}">
                                                                                    <div class="d-flex justify-content-between w-100 align-items-center">
                                                                                        <span class="fw-semibold">{{ $programName }}</span>
                                                                                        <span class="text-muted small">
                                                                                            Votes in program: <strong>{{ number_format($total) }}</strong>
                                                                                        </span>
                                                                                    </div>
                                                                                </button>
                                                                            </h2>

                                                                            <div id="progAllCol{{ $posId }}-{{ $programId }}" class="accordion-collapse collapse"
                                                                                 data-bs-parent="#programAllBreakdown{{ $posId }}">
                                                                                <div class="accordion-body">

                                                                                    @if ($sorted->isEmpty())
                                                                                        <div class="text-muted">No candidates found.</div>
                                                                                    @else
                                                                                        <div class="table-responsive">
                                                                                            <table class="table table-sm table-hover align-middle mb-0">
                                                                                                <thead>
                                                                                                    <tr>
                                                                                                        <th style="width:5%">#</th>
                                                                                                        <th style="width:45%">Candidate</th>
                                                                                                        <th style="width:15%" class="text-end">Votes</th>
                                                                                                        <th style="width:35%">Progress</th>
                                                                                                    </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                    @foreach ($sorted as $i => $r)
                                                                                                        @php
                                                                                                            $pct = $total > 0 ? round(((int)$r->vote_count / $total) * 100, 2) : 0;
                                                                                                            $pctBar = min(100, max(0, (float)$pct));
                                                                                                            $isTop = $i === 0 && (int)$r->vote_count > 0;
                                                                                                        @endphp
                                                                                                        <tr class="{{ $isTop ? 'table-success fw-semibold' : '' }}">
                                                                                                            <td>{{ $i + 1 }}</td>
                                                                                                            <td>{{ $r->candidate_name }}</td>
                                                                                                            <td class="text-end">{{ number_format((int)$r->vote_count) }}</td>
                                                                                                            <td>
                                                                                                                <div class="d-flex align-items-center gap-2">
                                                                                                                    <div class="progress flex-grow-1" style="height: 8px;">
                                                                                                                        <div class="progress-bar" style="width: {{ $pctBar }}%"></div>
                                                                                                                    </div>
                                                                                                                    <span class="small fw-semibold">{{ $pct }}%</span>
                                                                                                                </div>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    @endforeach
                                                                                                </tbody>
                                                                                            </table>
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

                                                {{-- OPTIONAL: keep these turnout tables only when scope is program/faculty --}}
                                                @if ($pos->scope_type === 'program')
                                                    <div class="col-12">
                                                        <div class="card border">
                                                            <div class="card-header bg-light py-2">
                                                                <strong>Turnout by Program (Eligible vs Voters) — Scope Targets</strong>
                                                            </div>
                                                            <div class="card-body">
                                                                @if ($turnoutByProgram->isEmpty())
                                                                    <div class="text-muted">No turnout data.</div>
                                                                @else
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm align-middle mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Program</th>
                                                                                    <th class="text-end">Eligible</th>
                                                                                    <th class="text-end">Voters</th>
                                                                                    <th class="text-end">Turnout</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($turnoutByProgram as $t)
                                                                                    @php
                                                                                        $eligible = (int)$t->eligible_students;
                                                                                        $voters   = (int)$t->voters;
                                                                                        $tp = $eligible > 0 ? round(($voters / $eligible) * 100, 2) : 0;
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td class="fw-semibold">{{ $t->program_name }}</td>
                                                                                        <td class="text-end">{{ number_format($eligible) }}</td>
                                                                                        <td class="text-end">{{ number_format($voters) }}</td>
                                                                                        <td class="text-end"><span class="badge bg-primary">{{ $tp }}%</span></td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @elseif ($pos->scope_type === 'faculty')
                                                    <div class="col-12">
                                                        <div class="card border">
                                                            <div class="card-header bg-light py-2">
                                                                <strong>Turnout by Faculty (Eligible vs Voters) — Scope Targets</strong>
                                                            </div>
                                                            <div class="card-body">
                                                                @if ($turnoutByFaculty->isEmpty())
                                                                    <div class="text-muted">No turnout data.</div>
                                                                @else
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm align-middle mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Faculty</th>
                                                                                    <th class="text-end">Eligible</th>
                                                                                    <th class="text-end">Voters</th>
                                                                                    <th class="text-end">Turnout</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($turnoutByFaculty as $t)
                                                                                    @php
                                                                                        $eligible = (int)$t->eligible_students;
                                                                                        $voters   = (int)$t->voters;
                                                                                        $tp = $eligible > 0 ? round(($voters / $eligible) * 100, 2) : 0;
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td class="fw-semibold">{{ $t->faculty_name }}</td>
                                                                                        <td class="text-end">{{ number_format($eligible) }}</td>
                                                                                        <td class="text-end">{{ number_format($voters) }}</td>
                                                                                        <td class="text-end"><span class="badge bg-primary">{{ $tp }}%</span></td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                            </div>
                                        </div>

                                        {{-- =======================
                                             TAB 3: TRENDS (unchanged)
                                             ======================= --}}
                                        <div class="tab-pane fade" id="trends-{{ $posId }}" role="tabpanel">
                                            <div class="text-muted mb-3">
                                                Shows the strongest support sources (voters’ faculty/program) for each candidate.
                                            </div>

                                            @if ($overallCandidates->isEmpty())
                                                <div class="alert alert-info mb-0">
                                                    No candidates/votes yet for this position.
                                                </div>
                                            @else
                                                <div class="row g-4">
                                                    @foreach ($overallCandidates as $cand)
                                                        @php
                                                            $candId = $cand->candidate_id ?? null;

                                                            $candFac = collect($supportFaculty)
                                                                ->where('candidate_id', $candId)
                                                                ->sortByDesc('votes_from_group')
                                                                ->take(5)
                                                                ->values();

                                                            $candProg = collect($supportProgram)
                                                                ->where('candidate_id', $candId)
                                                                ->sortByDesc('votes_from_group')
                                                                ->take(5)
                                                                ->values();
                                                        @endphp

                                                        <div class="col-12">
                                                            <div class="card border">
                                                                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                                    <div class="fw-semibold">
                                                                        <i class="bi bi-person-circle me-1"></i> {{ $cand->candidate_name }}
                                                                    </div>
                                                                    <div class="text-muted small">
                                                                        Votes: <strong>{{ number_format($cand->vote_count) }}</strong>
                                                                        • % of eligible: <strong>{{ $cand->vote_percent ?? 0 }}%</strong>
                                                                    </div>
                                                                </div>

                                                                <div class="card-body">
                                                                    <div class="row g-3">
                                                                        <div class="col-md-6">
                                                                            <div class="fw-semibold mb-2"><i class="bi bi-buildings me-1"></i> Top Faculties</div>
                                                                            @if ($candFac->isEmpty())
                                                                                <div class="text-muted small">No faculty trend data.</div>
                                                                            @else
                                                                                <ul class="list-group list-group-flush">
                                                                                    @foreach ($candFac as $x)
                                                                                        <li class="list-group-item d-flex justify-content-between px-0">
                                                                                            <span>{{ $x->faculty_name }}</span>
                                                                                            <span class="badge bg-secondary">{{ $x->votes_from_group }}</span>
                                                                                        </li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            @endif
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <div class="fw-semibold mb-2"><i class="bi bi-diagram-3 me-1"></i> Top Programs</div>
                                                                            @if ($candProg->isEmpty())
                                                                                <div class="text-muted small">No program trend data.</div>
                                                                            @else
                                                                                <ul class="list-group list-group-flush">
                                                                                    @foreach ($candProg as $x)
                                                                                        <li class="list-group-item d-flex justify-content-between px-0">
                                                                                            <span>{{ $x->program_name }}</span>
                                                                                            <span class="badge bg-secondary">{{ $x->votes_from_group }}</span>
                                                                                        </li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                    </div> {{-- tab-content --}}
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            @endif
        </div>
    </div>
</div>
@endsection

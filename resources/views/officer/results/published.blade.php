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

                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Scope summary cards --}}
    @php
        $global = $scopes['global'] ?? null;
        $program = $scopes['program'] ?? null;
        $faculty = $scopes['faculty'] ?? null;

        $card = function($title, $icon, $row, $badge) {
            $eligible = (int)($row->eligible_students ?? 0);
            $voters   = (int)($row->voters ?? 0);
            $turnout  = (float)($row->turnout_percent ?? 0);
            $turnout  = min(100, max(0, $turnout));
            $votedPct = (float)($row->voted_percent ?? 0); // optional
            return compact('title','icon','eligible','voters','turnout','badge','votedPct');
        };

        $cards = collect([
            $card('Global Scope',  'bi-globe',      $global,  'bg-success'),
            $card('Program Scope', 'bi-diagram-3',  $program, 'bg-info text-dark'),
            $card('Faculty Scope', 'bi-buildings',  $faculty, 'bg-primary'),
        ]);
    @endphp

    <div class="row g-4 mb-4">
        @foreach($cards as $c)
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-uppercase text-muted small fw-semibold mb-1">{{ $c['title'] }}</div>
                                <span class="badge {{ $c['badge'] }} px-3 py-2">
                                    <i class="bi {{ $c['icon'] }} me-1"></i>
                                    {{ strtoupper(str_replace(' scope','',$c['title'])) }}
                                </span>
                            </div>
                            <div class="text-muted small">
                                Turnout
                                <div class="h4 fw-bold mb-0">{{ $c['turnout'] }}%</div>
                            </div>
                        </div>

                        <div class="mt-3 text-muted">
                            Eligible: <strong class="text-dark">{{ number_format($c['eligible']) }}</strong><br>
                            Voters: <strong class="text-dark">{{ number_format($c['voters']) }}</strong>
                        </div>

                        <div class="progress mt-3" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: {{ $c['turnout'] }}%"></div>
                        </div>

                        @if($c['eligible'] <= 0)
                            <div class="alert alert-warning mt-3 mb-0 py-2 small">
                                No eligible active students detected for this scope.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
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
                                            <span class="badge {{ $scopeBadge }}">{{ strtoupper($pos->scope_type) }}</span>
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

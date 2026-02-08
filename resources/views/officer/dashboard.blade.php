@extends('officer.layouts.app')

@section('title', 'Officer Dashboard')

@section('content')
<div class="page-inner">

    <div class="page-header">
        <h4 class="page-title">Officer Dashboard</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('officer.dashboard') }}"><i class="bi bi-house-door-fill"></i></a>
            </li>
            <li class="separator"><i class="bi bi-chevron-right"></i></li>
            <li class="nav-item"><span>Dashboard</span></li>
        </ul>
    </div>

    {{-- flash --}}
    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2"></i> <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="card-category">Assigned Elections</p>
                            <h4 class="card-title">{{ $stats['assigned'] }}</h4>
                        </div>
                        <div class="icon-big text-center">
                            <i class="bi bi-megaphone-fill"></i>
                        </div>
                    </div>
                    <a href="{{ route('officer.elections.index') }}" class="small text-muted">View elections</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="card-category">Open</p>
                            <h4 class="card-title text-success">{{ $stats['open'] }}</h4>
                        </div>
                        <div class="icon-big text-center text-success">
                            <i class="bi bi-unlock-fill"></i>
                        </div>
                    </div>
                    <span class="small text-muted">Elections currently open</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="card-category">Closed</p>
                            <h4 class="card-title text-warning">{{ $stats['closed'] }}</h4>
                        </div>
                        <div class="icon-big text-center text-warning">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                    </div>
                    <span class="small text-muted">Finished elections</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="card-category">Draft</p>
                            <h4 class="card-title text-secondary">{{ $stats['draft'] }}</h4>
                        </div>
                        <div class="icon-big text-center text-secondary">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                    </div>
                    <span class="small text-muted">Not opened yet</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card card-round">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="card-category mb-1">Positions</p>
                            <h4 class="card-title mb-0">{{ $stats['positions'] }}</h4>
                        </div>
                        <i class="bi bi-diagram-3-fill fs-3"></i>
                    </div>
                    <div class="mt-2 text-muted small">
                        Total positions created in elections you manage.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-round">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="card-category mb-1">Candidates</p>
                            <h4 class="card-title mb-0">{{ $stats['candidates'] }}</h4>
                        </div>
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                    <div class="mt-2 text-muted small">
                        Total candidates in elections you manage.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Assigned elections quick actions --}}
    <div class="row g-3 mb-4">
        <div class="col-md-12">
            <div class="card card-round">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">My Assigned Elections</h4>
                        <small class="text-muted">Quick actions for positions & candidates</small>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('officer.elections.index') }}">
                        View All
                    </a>
                </div>

                <div class="card-body p-0">
                    @if($assignedElections->isEmpty())
                        <div class="p-4 text-center text-muted">
                            No elections assigned yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Election</th>
                                        <th>Status</th>
                                        <th>Window</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assignedElections as $election)
                                        @php
                                            $badge = match($election->status) {
                                                'draft'  => 'secondary',
                                                'open'   => 'success',
                                                'closed' => 'warning',
                                                default  => 'dark',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $election->title }}</td>
                                            <td>
                                                <span class="badge bg-{{ $badge }}">{{ strtoupper($election->status) }}</span>
                                            </td>
                                            <td class="text-muted">
                                                {{ $election->start_date?->format('Y-m-d') ?? '—' }}
                                                →
                                                {{ $election->end_date?->format('Y-m-d') ?? '—' }}
                                            </td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-primary"
                                                   href="{{ route('officer.elections.positions.index', $election) }}">
                                                    <i class="bi bi-diagram-3"></i> Positions
                                                </a>

                                                <a class="btn btn-sm btn-outline-dark"
                                                   href="{{ route('officer.elections.candidates.index', $election) }}">
                                                    <i class="bi bi-person-badge"></i> Candidates
                                                </a>

                                                <a class="btn btn-sm btn-outline-success"
                                                   href="{{ route('officer.results.show', $election) }}">
                                                    <i class="bi bi-bar-chart"></i> Results
                                                </a>
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
    </div>

    {{-- Recent activity --}}
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card card-round">
                <div class="card-header">
                    <h4 class="card-title mb-0">Recent Candidates</h4>
                    <small class="text-muted">Latest added candidates</small>
                </div>
                <div class="card-body">
                    @if($recentCandidates->isEmpty())
                        <div class="text-muted">No candidates yet.</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($recentCandidates as $c)
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $c->student?->first_name }} {{ $c->student?->last_name }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $c->electionPosition?->definition?->name ?? '—' }}
                                            —
                                            {{ $c->student?->faculty?->name ?? '—' }}
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark">
                                        {{ $c->electionPosition?->election?->title ?? 'Election' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-round">
                <div class="card-header">
                    <h4 class="card-title mb-0">Recent Positions</h4>
                    <small class="text-muted">Latest created positions</small>
                </div>
                <div class="card-body">
                    @if($recentPositions->isEmpty())
                        <div class="text-muted">No positions yet.</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($recentPositions as $p)
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold">{{ $p->definition?->name ?? '—' }}</div>
                                        <div class="text-muted small">
                                            Scope: {{ strtoupper($p->scope_type) }} —
                                            Max: {{ $p->max_candidates ?? 'No limit' }}
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark">
                                        {{ $p->election?->title ?? 'Election' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

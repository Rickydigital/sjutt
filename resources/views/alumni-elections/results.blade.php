@extends('components.app-main-layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Election Results</h5>
        <small class="text-muted">{{ $alumniElection->title }}</small>
    </div>
    <a href="{{ route('alumni-elections.show', $alumniElection) }}" class="btn btn-sm btn-outline-secondary">
        ← Back to Election
    </a>
</div>

@if(!in_array($alumniElection->status, ['closed', 'published']))
    <div class="alert alert-warning">
        <i class="fas fa-lock me-2"></i>
        Results are only available once the election is closed or published.
        Current status: <strong>{{ ucfirst(str_replace('_', ' ', $alumniElection->status)) }}</strong>
    </div>
@else
    @php
        $grandTotal = $positions->sum(fn($p) => $p->candidates->sum('votes_count'));
    @endphp

    <div class="alert alert-light border mb-4 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-vote-yea me-2 text-primary"></i>Total votes cast across all positions: <strong>{{ $grandTotal }}</strong></span>
        <span class="badge {{ $alumniElection->status === 'published' ? 'bg-success' : 'bg-warning text-dark' }}">
            {{ ucfirst($alumniElection->status) }}
        </span>
    </div>

    @forelse($positions as $position)
        @php
            $totalVotes = $position->candidates->sum('votes_count');
            $winner = $position->candidates->sortByDesc('votes_count')->first();
        @endphp
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center"
                 style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
                <span class="fw-semibold">{{ $position->name }}</span>
                <span class="badge bg-light text-dark">{{ $totalVotes }} vote{{ $totalVotes !== 1 ? 's' : '' }}</span>
            </div>

            @if($position->candidates->isEmpty())
                <div class="card-body text-muted">No candidates for this position.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">Rank</th>
                                <th>Candidate</th>
                                <th class="text-center">Votes</th>
                                <th width="200">Share</th>
                                <th class="text-center">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($position->candidates->sortByDesc('votes_count')->values() as $i => $candidate)
                                @php
                                    $pct = $totalVotes > 0 ? round($candidate->votes_count / $totalVotes * 100, 1) : 0;
                                    $isWinner = $winner && $candidate->id === $winner->id && $candidate->votes_count > 0;
                                @endphp
                                <tr class="{{ $isWinner ? 'table-success' : '' }}">
                                    <td class="fw-bold text-muted">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $candidate->surname }} {{ $candidate->first_name }} {{ $candidate->middle_name }}
                                        </div>
                                        @if($candidate->alumni)
                                            <small class="text-muted">{{ $candidate->alumni->email }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold fs-5">{{ $candidate->votes_count }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:10px;">
                                                <div class="progress-bar {{ $isWinner ? 'bg-success' : 'bg-primary' }}"
                                                     style="width:{{ $pct }}%"></div>
                                            </div>
                                            <small class="text-muted" style="min-width:40px;">{{ $pct }}%</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($isWinner)
                                            <span class="badge bg-success">
                                                <i class="fas fa-trophy me-1"></i> Winner
                                            </span>
                                        @elseif($alumniElection->status === 'published' && $candidate->votes_count === 0)
                                            <span class="badge bg-secondary">No votes</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div class="alert alert-info">No positions found for this election.</div>
    @endforelse
@endif
@endsection

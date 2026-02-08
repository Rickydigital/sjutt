{{-- resources/views/officer/results/index.blade.php --}}
@extends('officer.layouts.app')

@section('title', 'Election Results')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">Election Results Overview</h5>
                        <a href="{{ route('officer.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if ($elections->isEmpty())
                        <div class="alert alert-info text-center mb-0 py-5">
                            <i class="bi bi-info-circle-fill fs-1 d-block mb-3"></i>
                            <h5>No results available yet</h5>
                            <p class="text-muted mb-0">
                                Results will appear here once elections are closed or published.
                            </p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Election Title</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Voters</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($elections as $election)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $election->title }}</div>
                                                <small class="text-muted">
                                                    {{ $election->description ?? 'No description' }}
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    {{ optional($election->start_date)->format('d M Y') }}
                                                    —
                                                    {{ optional($election->end_date)->format('d M Y') }}
                                                </small>
                                            </td>
                                            <td>
                                                @if ($election->status === 'published')
                                                    <span class="badge bg-success">Published</span>
                                                @elseif ($election->status === 'closed')
                                                    <span class="badge bg-secondary">Closed</span>
                                                @else
                                                    <span class="badge bg-warning">Other</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $voters = \App\Models\ElectionVote::where('election_id', $election->id)
                                                        ->distinct('student_id')
                                                        ->count('student_id');
                                                @endphp
                                                {{ number_format($voters) }}
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('officer.results.show', $election) }}"
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-bar-chart-line me-1"></i> View Results
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            {{ $elections->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
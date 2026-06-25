@extends('officer.layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong class="card-title">My Assigned Elections</strong>
                <small class="text-muted d-block">Open elections when time starts, close elections when time
                    ends.</small>
            </div>
        </div>
    </div>

    <div class="card-body">

        @if ($elections->isEmpty())
        <p class="text-center mb-0">No elections assigned to you.</p>
        @else
        <div class="table-responsive">
            <table class="table table-striped table-sm align-middle">
                <thead class="bg-primary text-white">
                    <tr>
                        <th style="width: 28%">Title</th>
                        <th style="width: 12%">Start</th>
                        <th style="width: 12%">End</th>
                        <th style="width: 10%">Open</th>
                        <th style="width: 10%">Close</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 18%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($elections as $election)
                    @php
                    $badge = match($election->status) {
                    'draft' => 'secondary',
                    'open' => 'success',
                    'closed' => 'warning',
                    default => 'dark'
                    };

                    $canOpen = $election->canBeOpened();
                    $canClose = $election->canBeClosed();
                    @endphp

                    <tr>
                        <td class="fw-semibold">{{ $election->title }}</td>
                        <td>{{ optional($election->start_date)->format('Y-m-d') }}</td>
                        <td>{{ optional($election->end_date)->format('Y-m-d') }}</td>
                        <td>
                            @if($election->open_time)
                            {{ is_string($election->open_time) ? substr($election->open_time, 0, 5) :
                            $election->open_time->format('H:i') }}
                            @else
                            '—'
                            @endif
                            @if($election->start_date)
                            <small class="d-block text-muted">from {{ $election->start_date->format('Y-m-d') }}</small>
                            @endif
                        </td>
                        <td>
                            @if($election->close_time)
                            {{ is_string($election->close_time) ? substr($election->close_time, 0, 5) :
                            $election->close_time->format('H:i') }}
                            @else
                            '—'
                            @endif
                            @if($election->end_date)
                            <small class="d-block text-muted">until {{ $election->end_date->format('Y-m-d') }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $badge }}">{{ strtoupper($election->status) }}</span></td>

                        <td class="text-end">
                            {{-- Manage Positions --}}
                            <a class="action-icon text-primary me-2"
                                href="{{ route('officer.elections.positions.index', $election) }}"
                                title="Manage Positions">
                                <i class="bi bi-diagram-3-fill"></i>
                            </a>

                            {{-- Manage Candidates --}}
                            <a class="action-icon text-info me-2"
                                href="{{ route('officer.elections.candidates.index', $election) }}"
                                title="Manage Candidates">
                                <i class="bi bi-people-fill"></i>
                            </a>

                            {{-- ✅ View Published Results --}}
                            @php $isPublished = (int)($election->result_publishes_count ?? 0) > 0; @endphp

                            @if($isPublished)
                            <a class="action-icon text-success me-2"
                                href="{{ route('officer.results.published', $election) }}"
                                title="View Published Results">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            @else
                            <span class="text-muted me-2" title="Not published yet">
                                <i class="bi bi-eye-fill"></i>
                            </span>
                            @endif

                            {{-- Open --}}
                            @if($canOpen)
                            <form action="{{ route('officer.elections.open', $election) }}" method="POST"
                                class="d-inline open-election-form">
                                @csrf
                                <input type="hidden" name="confirm" value="1">
                                <button type="submit" class="action-icon text-success border-0 bg-transparent me-2"
                                    title="Open now (time window reached)">
                                    <i class="bi bi-unlock-fill"></i>
                                </button>
                            </form>
                            @else
                            <span class="text-muted me-2" title="Open not available yet (wait for time/date)">
                                <i class="bi bi-unlock-fill"></i>
                            </span>
                            @endif

                            {{-- Close --}}
                            @if($canClose)
                            <form action="{{ route('officer.elections.close', $election) }}" method="POST"
                                class="d-inline close-election-form">
                                @csrf
                                <input type="hidden" name="confirm" value="1">
                                <button type="submit" class="action-icon text-warning border-0 bg-transparent"
                                    title="Close Election">
                                    <i class="bi bi-lock-fill"></i>
                                </button>
                            </form>
                            @else
                            <span class="text-muted" title="Close available only when close time reached">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            @endif
                        </td>


                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="my-2">
            {{ $elections->links('vendor.pagination.bootstrap-5') }}
        </div>
        @endif

    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {

    @if(session('success'))
        Swal.fire({ icon:'success', title:'Success', text:@json(session('success')), timer:2000, showConfirmButton:false });
    @endif

    @if(session('error'))
        Swal.fire({ icon:'error', title:'Error', text:@json(session('error')) });
    @endif

    document.querySelectorAll('.open-election-form').forEach(form => {
        form.addEventListener('submit', function(e){
            e.preventDefault();
            Swal.fire({
                title: 'Open this election?',
                text: 'Students will be able to vote within the allowed window.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, open',
                cancelButtonText: 'Cancel'
            }).then(r => { if(r.isConfirmed) form.submit(); });
        });
    });

    document.querySelectorAll('.close-election-form').forEach(form => {
        form.addEventListener('submit', function(e){
            e.preventDefault();
            Swal.fire({
                title: 'Close this election?',
                text: 'Voting will stop immediately.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, close',
                cancelButtonText: 'Cancel'
            }).then(r => { if(r.isConfirmed) form.submit(); });
        });
    });

});
</script>
@endsection
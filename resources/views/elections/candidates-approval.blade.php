@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong class="card-title">Candidate Approvals</strong>
            <small class="text-muted d-block">
                Election: <span class="fw-semibold">{{ $election->title }}</span>
            </small>
        </div>

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('elections.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>

            <form method="POST" action="{{ route('admin.elections.candidates.approveAll', $election) }}">
                @csrf
                @method('PUT')
                <button class="btn btn-success btn-sm"
                        onclick="return confirm('Approve ALL pending candidates for this election?')">
                    <i class="bi bi-check2-all me-1"></i> Approve All
                </button>
            </form>
            <a href="{{ route('admin.elections.candidates.pdf', $election) }}?approved=1"
                class="btn btn-primary btn-sm">
                    <i class="bi bi-filetype-pdf me-1"></i> Export PDF
            </a>

        </div>
    </div>

    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Fix the following:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="p-3 border rounded bg-light">
                    <div class="fw-semibold">Stats</div>
                    <div class="text-muted small">Total: {{ $stats['total'] }} • Approved: {{ $stats['approved'] }} • Pending: {{ $stats['pending'] }}</div>
                </div>
            </div>

            <div class="col-md-8">
                <form class="d-flex gap-2" method="GET">
                    <select name="status" class="form-select form-select-sm" style="max-width:200px">
                        <option value="pending" {{ $status==='pending'?'selected':'' }}>Pending</option>
                        <option value="approved" {{ $status==='approved'?'selected':'' }}>Approved</option>
                        <option value="all" {{ $status==='all'?'selected':'' }}>All</option>
                    </select>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name / reg no">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>

        @forelse($positions as $pos)
            @php
                $list = $candidates->get($pos->id, collect());
            @endphp

            <div class="border rounded mb-3">
                <div class="p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">{{ $pos->definition?->name ?? 'Position' }}</div>
                        <div class="text-muted small">Scope: {{ strtoupper($pos->scope_type) }} • Candidates: {{ $list->count() }}</div>
                    </div>
                </div>

                @if($list->isEmpty())
                    <div class="p-3 text-muted">No candidates found for this position.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%">Photo</th>
                                    <th>Candidate</th>
                                    <th style="width: 20%">Faculty</th>
                                    <th style="width: 20%">Program</th>
                                    <th style="width: 15%">Status</th>
                                    <th class="text-end" style="width: 15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($list as $cand)
                                    @php
                                        $s = $cand->student;
                                        $photo = $cand->photo ? asset('storage/'.$cand->photo) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($photo)
                                                <img src="{{ $photo }}" class="rounded" style="width:44px;height:44px;object-fit:cover;">
                                            @else
                                                <div class="rounded bg-secondary text-white d-flex align-items-center justify-content-center"
                                                     style="width:44px;height:44px;">
                                                    NA
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $s?->first_name }} {{ $s?->last_name }}</div>
                                            <div class="text-muted small">{{ $s?->reg_no }}</div>
                                            @if($cand->description)
                                                <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($cand->description, 120) }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $s?->faculty?->name ?? '—' }}</td>
                                        <td>{{ $s?->program?->name ?? '—' }}</td>
                                        <td>
                                            @if($cand->is_approved)
                                                <span class="badge bg-success">APPROVED</span>
                                            @else
                                                <span class="badge bg-warning text-dark">PENDING</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if(!$cand->is_approved)
                                                <form method="POST" class="d-inline"
                                                      action="{{ route('admin.elections.candidates.approve', [$election, $cand]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <button class="btn btn-success btn-sm">
                                                        <i class="bi bi-check2-circle me-1"></i> Approve
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" class="d-inline"
                                                      action="{{ route('admin.elections.candidates.unapprove', [$election, $cand]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <button class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Undo
                                                    </button>
                                                </form>
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
            <div class="alert alert-info mb-0">No positions found in this election.</div>
        @endforelse

    </div>
</div>
@endsection

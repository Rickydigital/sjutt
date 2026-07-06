@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-white">Alumni Elections</h5>
        @can('manage alumni elections')
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createElectionModal">Create Election</button>
        @endcan
    </div>

    @if(session('success')) <div class="alert alert-success m-3">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger m-3">{{ session('error') }}</div> @endif

    <div class="p-3 border-bottom">
        <form class="row g-2">
            <div class="col-md-5"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search election title"></div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach(['draft','application_open','application_closed','voting_open','closed','published'] as $status)
                    <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Filter</button></div>
            <div class="col-md-2"><a href="{{ route('alumni-elections.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a></div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Title</th><th>Status</th><th>Officer</th><th>Positions</th><th>Candidates</th><th>Votes</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($elections as $election)
                <tr>
                    <td>{{ $loop->iteration + ($elections->currentPage()-1)*$elections->perPage() }}</td>
                    <td><strong>{{ $election->title }}</strong><br><small>{{ $election->created_at->format('d M Y') }}</small></td>
                    <td><span class="badge bg-secondary">{{ ucwords(str_replace('_',' ',$election->status)) }}</span></td>
                    <td>{{ $election->assignedOfficer?->name ?? 'Not assigned' }}</td>
                    <td>{{ $election->positions_count }}</td>
                    <td>{{ $election->candidates_count }}</td>
                    <td>{{ $election->votes_count }}</td>
                    <td>
                        <a href="{{ route('alumni-elections.show', $election) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                        <a href="{{ route('alumni-elections.results', $election) }}" class="btn btn-sm btn-outline-success">Results</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted p-4">No alumni elections found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $elections->links('vendor.pagination.bootstrap-5') }}</div>
</div>

@can('manage alumni elections')
<div class="modal fade" id="createElectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('alumni-elections.store') }}" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white"><h5>Create Alumni Election</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-md-12"><label>Title</label><input name="title" class="form-control" required></div>
                <div class="col-md-12"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
                <div class="col-md-6"><label>Application Start</label><input type="datetime-local" name="application_start_at" class="form-control"></div>
                <div class="col-md-6"><label>Application End</label><input type="datetime-local" name="application_end_at" class="form-control"></div>
                <div class="col-md-6"><label>Voting Start</label><input type="datetime-local" name="voting_start_at" class="form-control"></div>
                <div class="col-md-6"><label>Voting End</label><input type="datetime-local" name="voting_end_at" class="form-control"></div>
                <div class="col-md-12"><label>Assigned Election Officer</label><select name="assigned_officer_id" class="form-select"><option value="">Select officer</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>@endforeach</select></div>
                <input type="hidden" name="is_active" value="1">
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Create</button></div>
        </form>
    </div>
</div>
@endcan
@endsection

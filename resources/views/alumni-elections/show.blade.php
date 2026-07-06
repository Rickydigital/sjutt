@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-primary text-white d-flex justify-content-between">
        <h5 class="mb-0 text-white">{{ $alumniElection->title }}</h5>
        <a href="{{ route('alumni-elections.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
    @if(session('success')) <div class="alert alert-success m-3">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger m-3">{{ session('error') }}</div> @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-secondary">{{ ucwords(str_replace('_',' ',$alumniElection->status)) }}</span></div>
            <div class="col-md-4"><strong>Assigned Officer:</strong> {{ $alumniElection->assignedOfficer?->name ?? 'Not assigned' }}</div>
            <div class="col-md-3"><strong>Votes:</strong> {{ $alumniElection->votes_count }}</div>
            <div class="col-md-2"><strong>Candidates:</strong> {{ $alumniElection->candidates_count }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light"><strong>Change Status</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('alumni-elections.status', $alumniElection) }}" class="d-flex gap-2">
                    @csrf @method('PATCH')
                    <select name="status" class="form-select">
                        @foreach(['draft','application_open','application_closed','voting_open','closed','published'] as $status)
                        <option value="{{ $status }}" @selected($alumniElection->status===$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light"><strong>Assign Election Officer</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('alumni-elections.officers.assign', $alumniElection) }}" class="row g-2">
                    @csrf
                    <div class="col-md-7"><select name="user_id" class="form-select" required><option value="">Select user</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>@endforeach</select></div>
                    <div class="col-md-3"><select name="role" class="form-select"><option value="officer">Officer</option><option value="supervisor">Supervisor</option><option value="verifier">Verifier</option></select></div>
                    <div class="col-md-2"><button class="btn btn-success w-100">Assign</button></div>
                </form>
                <hr>
                @foreach($alumniElection->officers as $officer)
                    <div class="d-flex justify-content-between mb-1"><span>{{ $officer->user?->name }} <small>({{ $officer->role }})</small></span><span class="badge {{ $officer->is_active ? 'bg-success':'bg-danger' }}">{{ $officer->is_active ? 'Active':'Removed' }}</span></div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-light d-flex justify-content-between"><strong>Positions</strong><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPositionModal">Add Position</button></div>
    <div class="table-responsive">
        <table class="table mb-0"><thead><tr><th>Name</th><th>Max Candidates</th><th>Votes/Alumni</th><th>Status</th><th>Candidates</th></tr></thead><tbody>
        @foreach($alumniElection->positions as $position)
            <tr><td>{{ $position->name }}</td><td>{{ $position->max_candidates ?? 'Unlimited' }}</td><td>{{ $position->max_votes_per_alumni }}</td><td>{{ $position->is_enabled ? 'Enabled':'Disabled' }}</td><td>{{ $position->candidates->count() }}</td></tr>
        @endforeach
        </tbody></table>
    </div>
</div>

<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-light d-flex justify-content-between"><strong>Candidate Applications</strong><a href="{{ route('alumni-elections.candidates.index', $alumniElection) }}" class="btn btn-sm btn-outline-primary">Review All</a></div>
</div>

<div class="modal fade" id="addPositionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('alumni-elections.positions.store', $alumniElection) }}" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white"><h5>Add Position</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-12"><label>Name</label><input name="name" class="form-control" required></div>
                <div class="col-12"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
                <div class="col-md-6"><label>Max Candidates</label><input type="number" name="max_candidates" class="form-control"></div>
                <div class="col-md-6"><label>Votes Per Alumni</label><input type="number" name="max_votes_per_alumni" value="1" class="form-control" required></div>
                <input type="hidden" name="is_enabled" value="1">
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>
@endsection

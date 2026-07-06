@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex justify-content-between"><h5 class="mb-0 text-white">Candidate Applications - {{ $alumniElection->title }}</h5><a href="{{ route('alumni-elections.show', $alumniElection) }}" class="btn btn-light btn-sm">Back</a></div>
    @if(session('success')) <div class="alert alert-success m-3">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger m-3">{{ session('error') }}</div> @endif
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Name</th><th>Position</th><th>Phone</th><th>Status</th><th>Sponsors</th><th>Action</th></tr></thead><tbody>
        @foreach($candidates as $candidate)
            <tr>
                <td><strong>{{ $candidate->surname }} {{ $candidate->first_name }} {{ $candidate->middle_name }}</strong><br><small>{{ $candidate->email }}</small></td>
                <td>{{ $candidate->position?->name }}</td>
                <td>{{ $candidate->phone }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($candidate->application_status) }}</span></td>
                <td>{{ $candidate->sponsors->count() }}</td>
                <td class="d-flex gap-1">
                    <form method="POST" action="{{ route('alumni-elections.candidates.approve', $candidate) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Approve</button></form>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#reject{{ $candidate->id }}">Reject</button>
                </td>
            </tr>
            <div class="modal fade" id="reject{{ $candidate->id }}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('alumni-elections.candidates.reject', $candidate) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header bg-danger text-white"><h5>Reject Candidate</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><textarea name="rejection_reason" class="form-control" required placeholder="Reason"></textarea></div><div class="modal-footer"><button class="btn btn-danger">Reject</button></div></form></div></div>
        @endforeach
        </tbody></table>
    </div>
    <div class="card-footer">{{ $candidates->links('vendor.pagination.bootstrap-5') }}</div>
</div>
@endsection

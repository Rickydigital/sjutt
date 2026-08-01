@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-white">Candidate Applications - {{ $alumniElection->title }}</h5>
        <a href="{{ route('alumni-elections.show', $alumniElection) }}" class="btn btn-light btn-sm">Back</a>
    </div>
    @if(session('success')) <div class="alert alert-success m-3">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger m-3">{{ session('error') }}</div> @endif
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Position</th><th>Phone</th><th>Status</th><th>Sponsors</th><th>Actions</th></tr>
            </thead>
            <tbody>
        @forelse($candidates as $candidate)
            @php
                $candidateName = trim(implode(' ', array_filter([
                    $candidate->surname ?: $candidate->alumni?->l_name,
                    $candidate->first_name ?: $candidate->alumni?->f_name,
                    $candidate->middle_name ?: $candidate->alumni?->m_name,
                ])));
                $candidatePhone = $candidate->phone ?: $candidate->alumni?->phone;
                $candidateEmail = $candidate->email ?: $candidate->alumni?->email;
            @endphp
            <tr>
                <td><strong>{{ $candidateName ?: 'Name not provided' }}</strong><br><small class="text-muted">{{ $candidateEmail ?: 'Email not provided' }}</small></td>
                <td>{{ $candidate->position?->name ?: 'Not provided' }}</td>
                <td>{{ $candidatePhone ?: 'Not provided' }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($candidate->application_status) }}</span></td>
                <td>{{ $candidate->sponsors->count() }}</td>
                <td><div class="d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#candidateDetails{{ $candidate->id }}">View details</button>
                    <form method="POST" action="{{ route('alumni-elections.candidates.approve', $candidate) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Approve</button></form>
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#reject{{ $candidate->id }}">Reject</button>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No candidate applications found.</td></tr>
        @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $candidates->links('vendor.pagination.bootstrap-5') }}</div>
</div>

@foreach($candidates as $candidate)
    @php
        $candidateName = trim(implode(' ', array_filter([
            $candidate->surname ?: $candidate->alumni?->l_name,
            $candidate->first_name ?: $candidate->alumni?->f_name,
            $candidate->middle_name ?: $candidate->alumni?->m_name,
        ])));
        $candidatePhone = $candidate->phone ?: $candidate->alumni?->phone;
        $candidateEmail = $candidate->email ?: $candidate->alumni?->email;
        $detailFields = [
            'Position' => $candidate->position?->name,
            'Phone' => $candidatePhone,
            'Email' => $candidateEmail,
            'Date of birth' => $candidate->date_of_birth?->format('d M Y'),
            'Sex' => $candidate->sex,
            'Marital status' => $candidate->marital_status,
            'Education level' => $candidate->education_level,
            'Current position' => $candidate->current_position,
            'Current institution' => $candidate->institution,
            'Address' => $candidate->address,
            'Applicant type' => $candidate->applicant_type ? ucwords(str_replace('_', ' ', $candidate->applicant_type)) : null,
            'Institution attended' => $candidate->institution_attended,
            'Programme studied' => $candidate->programme_studied,
            'Year graduated' => $candidate->year_graduated,
            'Application status' => ucfirst($candidate->application_status),
            'Submitted on' => $candidate->created_at?->format('d M Y, H:i'),
        ];
    @endphp
    <div class="modal fade" id="candidateDetails{{ $candidate->id }}" tabindex="-1" aria-labelledby="candidateDetailsLabel{{ $candidate->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="candidateDetailsLabel{{ $candidate->id }}">Candidate Application Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        @if($candidate->photo_url)
                            <img src="{{ $candidate->photo_url }}" alt="{{ $candidateName }}" class="rounded border" style="width: 90px; height: 90px; object-fit: cover;">
                        @endif
                        <div><h4 class="mb-1">{{ $candidateName ?: 'Name not provided' }}</h4><span class="badge bg-secondary">{{ ucfirst($candidate->application_status) }}</span></div>
                    </div>
                    <div class="row g-3">
                        @foreach($detailFields as $label => $value)
                            <div class="col-md-6"><div class="text-muted small">{{ $label }}</div><div class="fw-semibold">{{ filled($value) ? $value : 'Not provided' }}</div></div>
                        @endforeach
                    </div>

                    <hr class="my-4">
                    <h6>Sponsors ({{ $candidate->sponsors->count() }})</h6>
                    @forelse($candidate->sponsors as $sponsor)
                        <div class="border rounded p-3 mb-2">
                            <div class="row g-2">
                                <div class="col-md-4"><span class="text-muted small d-block">Name</span><strong>{{ $sponsor->name }}</strong></div>
                                <div class="col-md-4"><span class="text-muted small d-block">Faculty / School / Directorate</span>{{ $sponsor->faculty_school_directorate ?: 'Not provided' }}</div>
                                <div class="col-md-4"><span class="text-muted small d-block">Registration number</span>{{ $sponsor->registration_no ?: 'Not provided' }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No sponsors were submitted.</p>
                    @endforelse

                    @if($candidate->rejection_reason)
                        <hr class="my-4"><div class="alert alert-danger mb-0"><strong>Rejection reason:</strong> {{ $candidate->rejection_reason }}</div>
                    @endif
                    @if($candidate->approved_at)
                        <div class="text-muted small mt-3">Reviewed {{ $candidate->approved_at->format('d M Y, H:i') }}{{ $candidate->approver ? ' by '.$candidate->approver->name : '' }}</div>
                    @endif
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reject{{ $candidate->id }}" tabindex="-1" aria-labelledby="rejectLabel{{ $candidate->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('alumni-elections.candidates.reject', $candidate) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header bg-danger text-white"><h5 class="modal-title text-white" id="rejectLabel{{ $candidate->id }}">Reject {{ $candidateName ?: 'Candidate' }}</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body"><label class="form-label" for="rejectionReason{{ $candidate->id }}">Reason</label><textarea id="rejectionReason{{ $candidate->id }}" name="rejection_reason" class="form-control" rows="4" required></textarea></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Reject</button></div>
            </form>
        </div>
    </div>
@endforeach
@endsection

@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex flex-row justify-content-between align-items-center">
            <div>
                <strong class="card-title">Elections</strong>
                <small class="text-muted d-block">Create elections, assign General Officer, and manage election settings.</small>
            </div>

            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createElectionModal">
                <i class="bi bi-plus-circle me-1"></i> New Election
            </button>
        </div>
    </div>

    <div class="card-body">
        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Fix the following:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($elections->isEmpty())
            <p class="text-center mb-0">No elections created yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th style="width: 22%">Title</th>
                            <th style="width: 10%">Start</th>
                            <th style="width: 10%">End</th>
                            <th style="width: 9%">Open</th>
                            <th style="width: 9%">Close</th>
                            <th style="width: 12%">Officer</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 20%" class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($elections as $election)
                            @php
                                $badge = match($election->status) {
                                    'draft'     => 'secondary',
                                    'open'      => 'success',
                                    'closed'    => 'warning',
                                    'published' => 'info',
                                    default     => 'dark'
                                };

                                $now = now();

                                $openTimeStr  = $election->open_time  ? \Carbon\Carbon::parse($election->open_time)->format('H:i:s') : '00:00:00';
                                $closeTimeStr = $election->close_time ? \Carbon\Carbon::parse($election->close_time)->format('H:i:s') : '00:00:00';

                                $startDateStr = $election->start_date ? $election->start_date->format('Y-m-d') : null;
                                $endDateStr   = $election->end_date   ? $election->end_date->format('Y-m-d')   : null;

                                $openAt  = $startDateStr ? \Carbon\Carbon::parse("{$startDateStr} {$openTimeStr}") : null;
                                $closeAt = $endDateStr   ? \Carbon\Carbon::parse("{$endDateStr} {$closeTimeStr}") : null;

                                $isWindowOpen = $openAt && $closeAt && $now->between($openAt, $closeAt);

                                $officer = $election->generalOfficers->first();

                                $positionsCount = $election->positions?->count() ?? 0;
                                $canDelete = ($election->status === 'draft' && $positionsCount === 0);
                            @endphp

                            <tr>
                                <td class="fw-semibold">
                                    {{ $election->title }}
                                    @if ($election->is_active && $election->status === 'open' && $isWindowOpen)
                                        <span class="badge bg-success ms-2">LIVE</span>
                                    @endif
                                </td>

                                <td>{{ optional($election->start_date)->format('Y-m-d') }}</td>
                                <td>{{ optional($election->end_date)->format('Y-m-d') }}</td>
                                <td>{{ $election->open_time ? \Carbon\Carbon::parse($election->open_time)->format('H:i') : '—' }}</td>
                                <td>{{ $election->close_time ? \Carbon\Carbon::parse($election->close_time)->format('H:i') : '—' }}</td>

                                <td>
                                    @if(!$officer)
                                        <span class="text-muted">NULL</span>
                                    @else
                                        <span class="fw-semibold">{{ $officer->first_name }} {{ $officer->last_name }}</span>
                                        @if($officer->pivot?->is_active === 0)
                                            <span class="badge bg-secondary ms-1">INACTIVE</span>
                                        @endif
                                    @endif
                                </td>

                                <td>
                                    <span class="badge bg-{{ $badge }}">{{ strtoupper($election->status) }}</span>
                                </td>

                                <td class="text-end">
                                    {{-- Show --}}
                                    <a href="#"
                                       class="action-icon text-primary me-2"
                                       data-bs-toggle="modal"
                                       data-bs-target="#showElectionModal{{ $election->id }}"
                                       title="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>

                                    {{-- Edit election (draft only) --}}
                                    @if ($election->status === 'draft')
                                        <a href="#"
                                           class="action-icon text-primary me-2"
                                           data-bs-toggle="modal"
                                           data-bs-target="#editElectionModal{{ $election->id }}"
                                           title="Edit Election">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @else
                                        <span class="text-muted me-2" title="Only draft elections can be edited">
                                            <i class="bi bi-pencil-square"></i>
                                        </span>
                                    @endif

                                    {{-- Candidates approval (Admin) --}}
                                    <a href="{{ route('admin.elections.candidates.index', $election) }}"
                                    class="action-icon text-success me-2"
                                    title="Candidates Approval">
                                        <i class="bi bi-people-fill"></i>
                                    </a>

                                    {{-- Results (only CLOSED is clickable) --}}
@if($election->status === 'closed')
    <a href="{{ route('admin.elections.candidates.index', $election) }}"
       class="action-icon text-warning me-2"
       title="Results">
        <i class="bi bi-bar-chart-fill"></i>
    </a>
@else
    <span class="text-muted me-2" title="Results available only when election is CLOSED">
        <i class="bi bi-bar-chart-fill"></i>
    </span>
@endif


                                    {{-- Officer action: Add or Edit --}}
                                    <a href="#"
                                       class="action-icon text-info me-2"
                                       data-bs-toggle="modal"
                                       data-bs-target="#officerModal{{ $election->id }}"
                                       title="{{ $officer ? 'Edit Officer' : 'Add Officer' }}">
                                        <i class="bi bi-person-badge-fill"></i>
                                    </a>

                                    {{-- Delete (only if draft AND no positions) --}}
                                    @if ($canDelete)
                                        <form action="{{ route('elections.destroy', $election) }}"
                                              method="POST"
                                              class="d-inline delete-election-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-icon text-danger border-0 bg-transparent" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted" title="Can delete only Draft elections with no positions">
                                            <i class="bi bi-trash"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>

                            {{-- OFFICER MODAL --}}
                            <div class="modal fade" id="officerModal{{ $election->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $officer ? 'Edit' : 'Add' }} General Election Officer</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        @if(!$officer)
                                            {{-- ADD --}}
                                            <form action="{{ route('elections.officers.add', $election) }}" method="POST" class="officer-save-form">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Select Student Officer</label>
                                                        <select name="student_id" class="form-select officer-select" required>
                                                            <option value="">-- choose student --</option>
                                                            @foreach($activeStudents as $student)
                                                                <option value="{{ $student->id }}">
                                                                    {{ $student->first_name }} {{ $student->last_name }} ({{ $student->reg_no }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="officer_active_create_{{ $election->id }}" checked>
                                                        <label class="form-check-label" for="officer_active_create_{{ $election->id }}">Officer Active</label>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-info text-white">
                                                        <i class="bi bi-check2-circle me-1"></i> Save Officer
                                                    </button>
                                                </div>
                                            </form>
                                        @else
                                            {{-- EDIT (change student + status) --}}
                                            <form action="{{ route('elections.officers.update', [$election, $officer]) }}" method="POST" class="officer-save-form">
                                                @csrf
                                                @method('PUT')

                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Select New Officer</label>
                                                        <select name="student_id" class="form-select officer-select" required>
                                                            @foreach($activeStudents as $student)
                                                                <option value="{{ $student->id }}" {{ (int)$student->id === (int)$officer->id ? 'selected' : '' }}>
                                                                    {{ $student->first_name }} {{ $student->last_name }} ({{ $student->reg_no }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="officer_active_edit_{{ $election->id }}"
                                                               {{ $officer->pivot?->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="officer_active_edit_{{ $election->id }}">Officer Active</label>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-info text-white">
                                                        <i class="bi bi-save me-1"></i> Update Officer
                                                    </button>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- SHOW MODAL --}}
                            <div class="modal fade" id="showElectionModal{{ $election->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                {{ $election->title }}
                                                <span class="badge bg-{{ $badge }} ms-2">{{ strtoupper($election->status) }}</span>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="p-3 border rounded">
                                                        <div class="fw-semibold">Date Range</div>
                                                        <div class="text-muted">
                                                            {{ optional($election->start_date)->format('Y-m-d') }}
                                                            —
                                                            {{ optional($election->end_date)->format('Y-m-d') }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="p-3 border rounded">
                                                        <div class="fw-semibold">Voting Window</div>
                                                        <div class="text-muted">
                                                            {{ optional($election->start_date)->format('Y-m-d') }} {{ $election->open_time ?? '—' }}
                                                            —
                                                            {{ optional($election->end_date)->format('Y-m-d') }} {{ $election->close_time ?? '—' }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="p-3 border rounded">
                                                        <div class="fw-semibold">General Officer</div>
                                                        <div class="text-muted">
                                                            @if(!$officer)
                                                                NULL
                                                            @else
                                                                {{ $officer->first_name }} {{ $officer->last_name }} ({{ $officer->reg_no }})
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="p-3 border rounded">
                                                        <div class="fw-semibold">Current Window Status</div>
                                                        <div class="text-muted">
                                                            {{ $isWindowOpen ? 'Within open/close time' : 'Outside open/close time' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- EDIT ELECTION MODAL --}}
                            @if ($election->status === 'draft')
                                <div class="modal fade" id="editElectionModal{{ $election->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Election</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <form action="{{ route('elections.update', $election) }}" method="POST">
                                                @csrf
                                                @method('PUT')

                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Title</label>
                                                        <input type="text" name="title" class="form-control" value="{{ $election->title }}" required>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Start Date</label>
                                                            <input type="date" name="start_date" class="form-control" value="{{ optional($election->start_date)->format('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">End Date</label>
                                                            <input type="date" name="end_date" class="form-control" value="{{ optional($election->end_date)->format('Y-m-d') }}" required>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Open Time</label>
                                                            <input type="time" name="open_time" class="form-control"
                                                                   value="{{ $election->open_time ? \Carbon\Carbon::parse($election->open_time)->format('H:i') : '' }}" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Close Time</label>
                                                            <input type="time" name="close_time" class="form-control"
                                                                   value="{{ $election->close_time ? \Carbon\Carbon::parse($election->close_time)->format('H:i') : '' }}" required>
                                                        </div>
                                                    </div>

                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active_{{ $election->id }}"
                                                               value="1" {{ $election->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="is_active_{{ $election->id }}">Active</label>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-save me-1"></i> Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif

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

{{-- CREATE MODAL --}}
<div class="modal fade" id="createElectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Election</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('elections.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="2026 Student Election" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Open Time</label>
                            <input type="time" name="open_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Close Time</label>
                            <input type="time" name="close_time" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active_create" value="1" checked>
                        <label class="form-check-label" for="is_active_create">Active</label>
                    </div>

                    <small class="text-muted d-block mt-2">
                        Note: Time is saved separately; it applies automatically on start/end dates.
                    </small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Init Select2 inside each modal properly
            $('.modal').on('shown.bs.modal', function () {
                const $modal = $(this);

                $modal.find('.officer-select').each(function () {
                    const $select = $(this);

                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }

                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Choose student',
                        allowClear: true
                    });
                });
            });

            @if (session('success'))
                Swal.fire({ icon: 'success', title: 'Success', text: @json(session('success')), timer: 2200, showConfirmButton: false, toast: true, position: 'top-end' });
            @endif

            @if (session('error'))
                Swal.fire({ icon: 'error', title: 'Error', text: @json(session('error')), toast: true, position: 'top-end' });
            @endif

            document.querySelectorAll('.delete-election-form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Delete this election?',
                        text: 'This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });
        });
    </script>
@endsection

@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex flex-row justify-content-between align-items-center">
            <strong class="card-title">Position Definitions</strong>

            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDefinitionModal">
                <i class="bi bi-plus-circle me-1"></i> New Definition
            </button>
        </div>
        <small class="text-muted d-block mt-1">
            Define reusable templates like CR (Faculty), FBR (Program), PRESIDENT (Global) to avoid duplicates.
        </small>
    </div>

    <div class="card-body">
        {{-- Server-side validation errors --}}
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

        @if ($definitions->isEmpty())
            <p class="text-center mb-0">No position definitions found.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th style="width: 10%">Code</th>
                            <th style="width: 30%">Name</th>
                            <th style="width: 15%">Scope</th>
                            <th style="width: 15%">Max Votes</th>
                            <th>Description</th>
                            <th style="width: 12%" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($definitions as $def)
                            <tr>
                                <td class="fw-semibold">{{ $def->code }}</td>
                                <td>{{ $def->name }}</td>
                                <td>
                                    @php
                                        $badge = match($def->default_scope_type) {
                                            'faculty' => 'info',
                                            'program' => 'warning',
                                            default => 'success'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">
                                        {{ strtoupper($def->default_scope_type) }}
                                    </span>
                                </td>
                                <td>{{ $def->max_votes_per_voter }}</td>
                                <td>{{ $def->description ?: '—' }}</td>
                                <td class="text-end">
                                    {{-- Show --}}
                                    <a href="#"
                                       class="action-icon text-primary me-2"
                                       data-bs-toggle="modal"
                                       data-bs-target="#showDefinitionModal{{ $def->id }}"
                                       title="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="#"
                                       class="action-icon text-primary me-2"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editDefinitionModal{{ $def->id }}"
                                       title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    {{-- Delete (SweetAlert) --}}
                                    <form action="{{ route('position-definitions.destroy', $def) }}"
                                          method="POST"
                                          class="d-inline delete-definition-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-icon text-danger border-0 bg-transparent" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- =========================
                                 SHOW MODAL
                            ========================== --}}
                            <div class="modal fade" id="showDefinitionModal{{ $def->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                {{ $def->name }} <small class="text-muted">({{ $def->code }})</small>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-2"><strong>Code:</strong> {{ $def->code }}</p>
                                            <p class="mb-2"><strong>Name:</strong> {{ $def->name }}</p>
                                            <p class="mb-2"><strong>Default Scope:</strong> {{ strtoupper($def->default_scope_type) }}</p>
                                            <p class="mb-2"><strong>Max Votes / Voter:</strong> {{ $def->max_votes_per_voter }}</p>
                                            <p class="mb-0"><strong>Description:</strong> {{ $def->description ?: 'None' }}</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- =========================
                                 EDIT MODAL
                            ========================== --}}
                            <div class="modal fade" id="editDefinitionModal{{ $def->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Definition</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <form action="{{ route('position-definitions.update', $def) }}" method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Code</label>
                                                        <input type="text" name="code" class="form-control" value="{{ $def->code }}" required>
                                                    </div>
                                                    <div class="col-md-8 mb-3">
                                                        <label class="form-label">Name</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $def->name }}" required>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Default Scope</label>
                                                        <select name="default_scope_type" class="form-select" required>
                                                            <option value="faculty" {{ $def->default_scope_type === 'faculty' ? 'selected' : '' }}>Faculty</option>
                                                            <option value="program" {{ $def->default_scope_type === 'program' ? 'selected' : '' }}>Program</option>
                                                            <option value="global"  {{ $def->default_scope_type === 'global'  ? 'selected' : '' }}>Global (All Students)</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Max Votes per Voter</label>
                                                        <input type="number" min="1" name="max_votes_per_voter" class="form-control" value="{{ $def->max_votes_per_voter }}">
                                                    </div>
                                                </div>

                                                <div class="mb-0">
                                                    <label class="form-label">Description</label>
                                                    <textarea name="description" class="form-control" rows="3">{{ $def->description }}</textarea>
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- =========================
     CREATE MODAL
========================== --}}
<div class="modal fade" id="createDefinitionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Position Definition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('position-definitions.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" placeholder="CR" required>
                            <small class="text-muted">Short unique code (e.g., CR, FBR, PRES)</small>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Class Representative" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Default Scope</label>
                            <select name="default_scope_type" class="form-select" required>
                                <option value="faculty">Faculty</option>
                                <option value="program">Program</option>
                                <option value="global">Global (All Students)</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max Votes per Voter</label>
                            <input type="number" min="1" name="max_votes_per_voter" class="form-control" value="1">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional"></textarea>
                    </div>
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
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Success / Error toast from session
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: @json(session('success')),
                    timer: 2200,
                    showConfirmButton: false
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: @json(session('error')),
                });
            @endif

            // Delete confirmation (SweetAlert)
            document.querySelectorAll('.delete-definition-form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Delete this definition?',
                        text: 'This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endsection

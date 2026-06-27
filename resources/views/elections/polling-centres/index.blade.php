@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>Polling Centres</strong>
                <small class="text-muted d-block">{{ $election->title }}</small>
            </div>

            <a href="{{ route('elections.index') }}" class="btn btn-secondary btn-sm">
                Back
            </a>
        </div>
    </div>

    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('polling_link'))
            <div class="alert alert-info">
                <strong>Polling Link:</strong>
                <input type="text" class="form-control mt-2" value="{{ session('polling_link') }}" readonly onclick="this.select()">
                <small class="text-muted">Copy and send this link to the polling centre manager.</small>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                    <small>Total Centres</small>
                    <h4>{{ $analytics['total_centres'] }}</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                    <small>Active Centres</small>
                    <h4>{{ $analytics['active_centres'] }}</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                    <small>Completed Sessions</small>
                    <h4>{{ $analytics['completed_sessions'] }}</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                    <small>Total Votes Cast</small>
                    <h4>{{ $analytics['total_votes_cast'] ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createCentreModal">
            <i class="bi bi-plus-circle me-1"></i> Add Polling Centre
        </button>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>Name</th>
                        <th>Manager</th>
                        <th>Status</th>
                        <th>Sessions</th>
                        <th>Votes</th>
                        <th>Last Activity</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($election->pollingCentres as $centre)
                        <tr>
                            <td>
                                <strong>{{ $centre->name }}</strong>
                                <small class="text-muted d-block">{{ $centre->location ?? 'No location' }}</small>
                            </td>

                            <td>
                                {{ $centre->manager_name ?? '—' }}
                                <small class="text-muted d-block">{{ $centre->manager_phone ?? '' }}</small>
                            </td>

                            <td>
                                @if($centre->is_active)
                                    <span class="badge bg-success">ACTIVE</span>
                                @else
                                    <span class="badge bg-secondary">INACTIVE</span>
                                @endif
                            </td>

                            <td>
                                {{ $centre->completed_sessions ?? 0 }} /
                                {{ $centre->total_sessions ?? 0 }}
                                <small class="text-muted d-block">completed / total</small>
                            </td>

                            <td>{{ $centre->total_votes_cast ?? 0 }}</td>

                            <td>
                                {{ $centre->last_activity_at ? \Carbon\Carbon::parse($centre->last_activity_at)->diffForHumans() : '—' }}
                            </td>

                            <td class="text-end">
                                <button class="btn btn-sm btn-info text-white"
                                        data-bs-toggle="modal"
                                        data-bs-target="#analyticsModal{{ $centre->id }}">
                                    Analytics
                                </button>

                                <button class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editCentreModal{{ $centre->id }}">
                                    Edit
                                </button>

                                <form action="{{ route('elections.polling-centres.regenerate-link', [$election, $centre]) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-warning">
                                        New Link
                                    </button>
                                </form>

                                @if($centre->is_active)
                                    <form action="{{ route('elections.polling-centres.deactivate', [$election, $centre]) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-secondary">Deactivate</button>
                                    </form>
                                @else
                                    <form action="{{ route('elections.polling-centres.activate', [$election, $centre]) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success">Activate</button>
                                    </form>
                                @endif

                                <form action="{{ route('elections.polling-centres.destroy', [$election, $centre]) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this polling centre?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="analyticsModal{{ $centre->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Analytics - {{ $centre->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="p-3 border rounded">
                                                    <small>Total Sessions</small>
                                                    <h4>{{ $centre->total_sessions ?? 0 }}</h4>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="p-3 border rounded">
                                                    <small>Completed</small>
                                                    <h4>{{ $centre->completed_sessions ?? 0 }}</h4>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="p-3 border rounded">
                                                    <small>Failed</small>
                                                    <h4>{{ $centre->failed_sessions ?? 0 }}</h4>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="p-3 border rounded">
                                                    <small>Reg Verified</small>
                                                    <h4>{{ $centre->reg_verified_sessions ?? 0 }}</h4>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="p-3 border rounded">
                                                    <small>Identity Verified</small>
                                                    <h4>{{ $centre->identity_verified_sessions ?? 0 }}</h4>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="p-3 border rounded">
                                                    <small>Expired</small>
                                                    <h4>{{ $centre->expired_sessions ?? 0 }}</h4>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="p-3 border rounded">
                                                    <small>Total Votes Cast</small>
                                                    <h4>{{ $centre->total_votes_cast ?? 0 }}</h4>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="p-3 border rounded">
                                                    <small>Last Activity</small>
                                                    <h5>
                                                        {{ $centre->last_activity_at ? \Carbon\Carbon::parse($centre->last_activity_at)->format('Y-m-d H:i') : '—' }}
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="editCentreModal{{ $centre->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <form action="{{ route('elections.polling-centres.update', [$election, $centre]) }}" method="POST">
                                        @csrf
                                        @method('PUT')

                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Polling Centre</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            @include('elections.polling-centres.partials.form', ['centre' => $centre])
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No polling centres created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createCentreModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="{{ route('elections.polling-centres.store', $election) }}" method="POST">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Create Polling Centre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    @include('elections.polling-centres.partials.form', ['centre' => null])
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Create Centre</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
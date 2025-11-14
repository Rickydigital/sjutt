@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <!-- Header: Purple Gradient -->
    <div class="card-header" style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0 text-white">Events Management</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createEventModal">
                Add Event
            </button>
        </div>

        <!-- Search & Filter: White -->
        <div class="bg-white p-3 rounded shadow-sm">
            <form action="{{ route('events.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search title, location..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <select name="filter_creator" class="form-select form-select-sm">
                        <option value="">All Creators</option>
                        @foreach(\App\Models\Event::select('created_by')->distinct()->with('user')->get() as $e)
                            @if($e->user)
                                <option value="{{ $e->created_by }}" {{ request('filter_creator') == $e->created_by ? 'selected' : '' }}>
                                    {{ $e->user->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-0 m-0 py-2 text-center">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-0 rounded-0 m-0 py-2 text-center">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
    <div class="alert alert-danger border-0 rounded-0 m-0 py-2">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

        @if ($events->isEmpty())
            <div class="p-5 text-center text-muted">
                <p class="mt-3">No events found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
                        <tr>
                            <th>#</th>
                            <th>Media</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Time</th>
                            <th>Access</th>
                            <th>Creator</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td>{{ $loop->iteration + ($events->currentPage() - 1) * $events->perPage() }}</td>
                                <td>
                                    @if($event->media)
                                        @if(Str::endsWith($event->media, ['.jpg','.png','.jpeg']))
                                            <img src="{{ Storage::url($event->media) }}" class="rounded" style="width:50px;height:50px;object-fit:cover;">
                                        @else
                                            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
                                                Video
                                            </div>
                                        @endif
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
                                            No Media
                                        </div>
                                    @endif
                                </td>
                                <td><strong>{{ Str::limit($event->title, 25) }}</strong></td>
                                <td>{{ Str::limit($event->location, 20) }}</td>
                                <td>
                                    {{ $event->start_time->format('d M Y, h:i A') }}<br>
                                    <small>to {{ $event->end_time->format('d M Y, h:i A') }}</small>
                                </td>
                                <td>
                                    @php $allowed = $event->user_allowed ?? [] @endphp
                                    @if(in_array('all', $allowed))
                                        <span class="badge bg-primary">All</span>
                                    @else
                                        @if(in_array('staff', $allowed)) <span class="badge bg-warning">Staff</span> @endif
                                        @if(in_array('student', $allowed)) <span class="badge bg-info">Students</span> @endif
                                        @foreach($allowed as $prog)
                                            @if(!in_array($prog, ['all','staff','student']))
                                                <span class="badge bg-secondary">{{ $prog }}</span>
                                            @endif
                                        @endforeach
                                    @endif
                                </td>
                                <td><span class="badge bg-dark">{{ $event->user?->name ?? '—' }}</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#showEvent{{ $event->id }}">
                                        Show
                                    </button>

                                    @php
                                        $canEdit = auth()->id() === $event->created_by || auth()->user()->hasRole('Admin');
                                    @endphp
                                    @if($canEdit)
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editEvent{{ $event->id }}">
                                            Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteEvent{{ $event->id }}">
                                            Delete
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            <!-- ==================== SHOW MODAL ==================== -->
                            <div class="modal fade" id="showEvent{{ $event->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title">{{ $event->title }}</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            @if($event->media)
                                                @if(Str::endsWith($event->media, ['.jpg','.png','.jpeg']))
                                                    <img src="{{ Storage::url($event->media) }}" class="img-fluid rounded shadow-sm mb-3">
                                                @else
                                                    <video controls class="w-100 rounded shadow-sm mb-3">
                                                        <source src="{{ Storage::url($event->media) }}" type="video/mp4">
                                                    </video>
                                                @endif
                                            @endif

                                            <p><strong>Description:</strong></p>
                                            <div class="bg-light p-3 rounded mb-3">{!! nl2br(e($event->description)) !!}</div>

                                            <p><strong>Location:</strong> {{ $event->location }}</p>
                                            <p><strong>Time:</strong>
                                                {{ $event->start_time->format('d M Y, h:i A') }} to
                                                {{ $event->end_time->format('d M Y, h:i A') }}
                                            </p>
                                            <p><strong>Access:</strong>
                                                @if(in_array('all', $event->user_allowed))
                                                    All Users
                                                @else
                                                    @if(in_array('staff', $event->user_allowed)) Staff @endif
                                                    @if(in_array('student', $event->user_allowed)) Students @endif
                                                    @foreach($event->user_allowed as $prog)
                                                        @if(!in_array($prog, ['all','staff','student']))
                                                            {{ $prog }}
                                                        @endif
                                                    @endforeach
                                                @endif
                                            </p>

                                            <hr>
                                            <p class="text-muted small">
                                                Created by: {{ $event->user?->name ?? '—' }}<br>
                                                Created on: {{ $event->created_at->format('d M Y, h:i A') }}
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ==================== EDIT MODAL ==================== -->
                            <div class="modal fade" id="editEvent{{ $event->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <form action="{{ route('events.update', $event) }}" method="POST" enctype="multipart/form-data">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Edit Event</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-12">
                                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                                        <input type="text" name="title" class="form-control" value="{{ old('title', $event->title) }}" required>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label class="form-label">Description <span class="text-danger">*</span></label>
                                                        <textarea name="description" class="form-control" rows="4" required>{{ old('description', $event->description) }}</textarea>
                                                    </div>

                                                    <!-- Location Type -->
                                                    <div class="col-md-12">
                                                        <label class="form-label">Location Type <span class="text-danger">*</span></label>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="location_type" value="venue" id="edit_venue_{{ $event->id }}"
                                                                           {{ old('location_type', \App\Models\Venue::where('name', $event->location)->exists() ? 'venue' : '') === 'venue' ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="edit_venue_{{ $event->id }}">Select Venue</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="location_type" value="custom" id="edit_custom_{{ $event->id }}"
                                                                           {{ old('location_type', !\App\Models\Venue::where('name', $event->location)->exists() ? 'custom' : '') === 'custom' ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="edit_custom_{{ $event->id }}">Type Location</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                   <div class="col-md-6" id="edit_venue_field_{{ $event->id }}">
    <label class="form-label">Venue</label>
    <select name="venue_id" class="form-select" id="edit_venue_select_{{ $event->id }}">
        <option value="">Select Venue</option>
        @foreach(\App\Models\Venue::orderBy('name')->get() as $venue)
            <option value="{{ $venue->id }}" {{ old('venue_id', \App\Models\Venue::where('name', $event->location)->first()?->id) == $venue->id ? 'selected' : '' }}>
                {{ $venue->name }}
            </option>
        @endforeach
    </select>
</div>

                                                    <div class="col-md-6" id="edit_custom_field_{{ $event->id }}" style="display: {{ old('location_type') === 'custom' ? 'block' : 'none' }};">
                                                        <label class="form-label">Custom Location</label>
                                                        <input type="text" name="custom_location" class="form-control" value="{{ old('custom_location', !\App\Models\Venue::where('name', $event->location)->exists() ? $event->location : '') }}">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                                        <input type="datetime-local" name="start_time" class="form-control"
                                                               value="{{ old('start_time', $event->start_time?->format('Y-m-d\TH:i')) }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">End Time <span class="text-danger">*</span></label>
                                                        <input type="datetime-local" name="end_time" class="form-control"
                                                               value="{{ old('end_time', $event->end_time?->format('Y-m-d\TH:i')) }}" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Media</label>
                                                        <input type="file" name="media" class="form-control" accept="image/*,video/*">
                                                        @if($event->media)
                                                            <small class="text-success d-block mt-1">Current: {{ basename($event->media) }}</small>
                                                        @endif
                                                    </div>

                                                    <!-- Access: Checkboxes -->
<div class="col-md-12">
    <label class="form-label">Who can view? <span class="text-danger">*</span></label>
    @php $allowed = $event->user_allowed ?? [] @endphp
    <div class="row g-2">
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="access[]" value="all" id="edit_all_{{ $event->id }}"
                       {{ in_array('all', $allowed) ? 'checked' : '' }}>
                <label class="form-check-label" for="edit_all_{{ $event->id }}">All</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="access[]" value="staff" id="edit_staff_{{ $event->id }}"
                       {{ in_array('staff', $allowed) ? 'checked' : '' }}>
                <label class="form-check-label" for="edit_staff_{{ $event->id }}">Staff</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="access[]" value="student" id="edit_student_{{ $event->id }}"
                       {{ in_array('student', $allowed) ? 'checked' : '' }}>
                <label class="form-check-label" for="edit_student_{{ $event->id }}">Students</label>
            </div>
        </div>
    </div>
</div>

                                                    
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Event</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ==================== DELETE MODAL ==================== -->
                            <div class="modal fade" id="deleteEvent{{ $event->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('events.destroy', $event) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Delete event: <strong>"{{ $event->title }}"</strong>?</p>
                                                <p class="text-danger small">This cannot be undone.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-light border-top-0">
                {{ $events->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<!-- ==================== CREATE MODAL ==================== -->
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('events.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Create New Event</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>

                        <!-- Location Type -->
                        <div class="col-md-12">
                            <label class="form-label">Location Type <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="location_type" value="venue" id="create_venue" checked>
                                        <label class="form-check-label" for="create_venue">Select Venue</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="location_type" value="custom" id="create_custom">
                                        <label class="form-check-label" for="create_custom">Type Location</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" id="create_venue_field">
    <label class="form-label">Venue</label>
    <select name="venue_id" class="form-select" id="create_venue_select">
        <option value="">Select Venue</option>
        @foreach(\App\Models\Venue::orderBy('name')->get() as $venue)
            <option value="{{ $venue->id }}">{{ $venue->name }}</option>
        @endforeach
    </select>
</div>

                        <div class="col-md-6" id="create_custom_field" style="display: none;">
                            <label class="form-label">Custom Location</label>
                            <input type="text" name="custom_location" class="form-control" placeholder="e.g. Off-campus">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="end_time" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Media (Image/Video)</label>
                            <input type="file" name="media" class="form-control" accept="image/*,video/*">
                        </div>

                        <!-- Access: Checkboxes -->
                        
<div class="col-md-12">
    <label class="form-label">Who can view? <span class="text-danger">*</span></label>
    <div class="row g-2">
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="access[]" value="all" id="create_all">
                <label class="form-check-label" for="create_all">All</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="access[]" value="staff" id="create_staff">
                <label class="form-check-label" for="create_staff">Staff</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="access[]" value="student" id="create_student">
                <label class="form-check-label" for="create_student">Students</label>
            </div>
        </div>
    </div>
</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Event</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function toggleLocationFields(modal) {
        const isCreate = modal.id === 'createEventModal';
        const prefix = isCreate ? 'create' : modal.id.replace('editEvent', 'edit_');
        const locationType = modal.querySelector(`input[name="location_type"]:checked`)?.value;

        const venueField = modal.querySelector(`#${prefix}_venue_field`);
        const venueSelect = modal.querySelector(`#${prefix}_venue_select`) || modal.querySelector(`#edit_venue_select_${modal.id.replace('editEvent', '')}`);
        const customField = modal.querySelector(`#${prefix}_custom_field`);

        // Venue
        if (venueField && venueSelect) {
            if (locationType === 'venue') {
                venueField.style.display = 'block';
                venueSelect.removeAttribute('disabled');
                venueSelect.setAttribute('required', 'required');
            } else {
                venueField.style.display = 'none';
                venueSelect.setAttribute('disabled', 'disabled');
                venueSelect.removeAttribute('required');
            }
        }

        // Custom
        if (customField) {
            customField.style.display = locationType === 'custom' ? 'block' : 'none';
            const customInput = customField.querySelector('input');
            if (customInput) {
                if (locationType === 'custom') {
                    customInput.setAttribute('required', 'required');
                } else {
                    customInput.removeAttribute('required');
                }
            }
        }
    }

    // On modal open
    document.querySelectorAll('#createEventModal, [id^="editEvent"]').forEach(modal => {
        modal.addEventListener('shown.bs.modal', () => toggleLocationFields(modal));
    });

    // On radio change
    document.addEventListener('change', function (e) {
        if (e.target.name === 'location_type') {
            const modal = e.target.closest('.modal');
            if (modal) toggleLocationFields(modal);
        }
    });
});
</script>
@endsection

@section('styles')
<style>
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
    .badge { font-size: 0.8em; }
</style>
@endsection
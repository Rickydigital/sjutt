@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
        <h5 class="mb-0 text-white">Alumni Events</h5>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addEventModal">Add Event</button>
    </div>

    @if(session('success')) <div class="alert alert-success m-3">{{ session('success') }}</div> @endif

    <div class="card-body">
        <form class="row g-2 mb-3">
            <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search event"></div>
            <div class="col-md-3"><select name="status" class="form-select form-select-sm"><option value="">All Status</option>@foreach(['draft','published','cancelled','completed'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary">Filter</button></div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Title</th><th>Date</th><th>Venue</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach($events as $event)
                    <tr>
                        <td><strong>{{ $event->title }}</strong></td>
                        <td>{{ $event->starts_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td>{{ $event->venue ?? '—' }}</td>
                        <td><span class="badge bg-secondary">{{ $event->status }}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editEvent{{ $event->id }}">Edit</button>
                            <form method="POST" action="{{ route('alumni.events.destroy', $event) }}" class="d-inline" onsubmit="return confirm('Delete event?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </td>
                    </tr>
                    <div class="modal fade" id="editEvent{{ $event->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" enctype="multipart/form-data" action="{{ route('alumni.events.update', $event) }}" class="modal-content">@csrf @method('PUT') @include('alumni.events.partials.form', ['event' => $event])</form></div></div>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $events->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>

<div class="modal fade" id="addEventModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" enctype="multipart/form-data" action="{{ route('alumni.events.store') }}" class="modal-content">@csrf @include('alumni.events.partials.form', ['event' => null])</form></div></div>
@endsection

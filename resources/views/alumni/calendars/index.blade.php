@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-success text-white">
        <h5 class="mb-0 text-white">Alumni Calendar</h5>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addCalendarModal">Add Calendar Item</button>
    </div>
    @if(session('success')) <div class="alert alert-success m-3">{{ session('success') }}</div> @endif
    <div class="card-body">
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>@foreach($calendars as $item)
                <tr><td><strong>{{ $item->title }}</strong></td><td>{{ $item->calendar_date?->format('d M Y') }}</td><td>{{ $item->start_time ?? '—' }} - {{ $item->end_time ?? '—' }}</td><td>{{ $item->type }}</td><td><span class="badge bg-secondary">{{ $item->status }}</span></td><td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCalendar{{ $item->id }}">Edit</button><form method="POST" action="{{ route('alumni.calendar.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete item?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
                <div class="modal fade" id="editCalendar{{ $item->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('alumni.calendar.update', $item) }}" class="modal-content">@csrf @method('PUT') @include('alumni.calendars.partials.form', ['item' => $item, 'events' => $events])</form></div></div>
            @endforeach</tbody>
        </table></div>
        {{ $calendars->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>
<div class="modal fade" id="addCalendarModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('alumni.calendar.store') }}" class="modal-content">@csrf @include('alumni.calendars.partials.form', ['item' => null, 'events' => $events])</form></div></div>
@endsection

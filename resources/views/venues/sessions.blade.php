{{-- resources/views/venues/sessions.blade.php --}}
@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Venue Session Overview</h5>
        <a href="{{ route('venues.index') }}" class="btn btn-sm btn-outline-secondary">
            Back to Venues List
        </a>
    </div>

    <div class="card-body">

        <form method="GET" action="{{ route('venue.sessions.index') }}" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by venue name or code..."
                       value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit">Search</button>
                @if(request('search'))
                    <a href="{{ route('venue.sessions.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
        @if(empty($venues))
            <p class="text-center text-muted">No venues or no active semester.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Venue</th>
                            <th>Code</th>
                            <th>Building</th>
                            <th>Capacity</th>
                            <th>Type</th>
                            <th class="text-center">Total Sessions</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($venues as $v)
                        <tr>
                            <td><strong>{{ $v->longform }}</strong></td>
                            <td>{{ $v->name }}</td>
                            <td>{{ $v->building?->name ?? '—' }}</td>
                            <td>{{ $v->capacity }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $v->type)) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $v->total_sessions > 0 ? 'danger' : 'success' }}">
                                    {{ $v->total_sessions }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('venue.sessions.show', $v) }}"
                                   class="btn btn-sm btn-info">
                                    View Sessions
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                @if(is_object($venues) && method_exists($venues, 'links'))
                    {{ $venues->links('vendor.pagination.bootstrap-5') }}
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
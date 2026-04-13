{{-- resources/views/venues/sessions.blade.php --}}
@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Venue Session Overview</h5>
        <a href="{{ route('venues.index') }}" class="btn btn-sm btn-outline-secondary">
            Back to Venues List
        </a>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('venue.sessions.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <label for="setup_id" class="form-label">Setup</label>
                    <select name="setup_id" id="setup_id" class="form-select">
                        <option value="">Select setup</option>
                        @foreach($timetableSemesters as $setup)
                            <option value="{{ $setup->id }}" {{ (string) $selectedSetupId === (string) $setup->id ? 'selected' : '' }}>
                                {{ $setup->semester?->name ?? 'N/A' }} - {{ $setup->academic_year }}
                                @if(!empty($setup->is_current)) (Current) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="search" class="form-label">Search Venue</label>
                    <input type="text"
                           name="search"
                           id="search"
                           class="form-control"
                           placeholder="Search by venue name or code..."
                           value="{{ $search }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit">Search</button>
                </div>
            </div>

            @if($selectedSetupId || $search)
                <div class="mt-2">
                    <a href="{{ route('venue.sessions.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                </div>
            @endif
        </form>

        @if(empty($venues) || (is_object($venues) && method_exists($venues, 'count') && $venues->count() === 0))
            <p class="text-center text-muted mb-0">No venues or no selected setup.</p>
        @else
            <div class="mb-3">
                <span class="badge bg-info">
                    Setup:
                    {{ $selectedSetup?->semester?->name ?? 'N/A' }}
                    ({{ $selectedSetup?->academic_year ?? 'N/A' }})
                </span>
            </div>

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
                                    <a href="{{ route('venue.sessions.show', ['venue' => $v->id, 'setup_id' => $selectedSetupId]) }}"
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
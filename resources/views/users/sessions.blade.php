{{-- resources/views/users/sessions.blade.php --}}
@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Lecturer Session Overview</h5>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
            Back to Users
        </a>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('user.sessions.index') }}" class="mb-3">
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
                    <label for="search" class="form-label">Search Lecturer</label>
                    <input type="text"
                           name="search"
                           id="search"
                           class="form-control"
                           placeholder="Search by name or email..."
                           value="{{ $search }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit">Search</button>
                </div>
            </div>

            @if($selectedSetupId || $search)
                <div class="mt-2">
                    <a href="{{ route('user.sessions.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            @endif
        </form>

        @if(empty($users) || (is_object($users) && method_exists($users, 'count') && $users->count() === 0))
            <p class="text-center text-muted">
                {{ $search ? 'No lecturers found for your search.' : 'No lecturers or no selected setup.' }}
            </p>
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
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-center">Total Sessions</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                            <tr>
                                <td><strong>{{ $u->name }}</strong></td>
                                <td>{{ $u->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $u->hasRole('lecturer') ? 'primary' : 'secondary' }}">
                                        {{ $u->roles->pluck('name')->implode(', ') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $u->total_sessions > 0 ? 'danger' : 'success' }}">
                                        {{ $u->total_sessions }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('user.sessions.show', ['user' => $u->id, 'setup_id' => $selectedSetupId]) }}"
                                       class="btn btn-sm btn-info">
                                        View Schedule
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
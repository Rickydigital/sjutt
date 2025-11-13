{{-- resources/views/users/sessions.blade.php --}}
@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Lecturer Session Overview</h5>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
            Back to Users
        </a>
    </div>

    <div class="card-body">
        <!-- Search Box -->
        <form method="GET" action="{{ route('user.sessions.index') }}" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by name or email..."
                       value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit">Search</button>
                @if(request('search'))
                    <a href="{{ route('user.sessions.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        @if($users->isEmpty())
            <p class="text-center text-muted">
                {{ request('search') ? 'No lecturers found for your search.' : 'No lecturers or no active semester.' }}
            </p>
        @else
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
                                <a href="{{ route('user.sessions.show', $u) }}"
                                   class="btn btn-sm btn-info">View Schedule</a>
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
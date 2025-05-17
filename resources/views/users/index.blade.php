@extends('components.app-main-layout')

@section('content')
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex align-items-center justify-content-between">
                <h1 class="font-weight-bold">
                    Users Management
                </h1>
                <a href="{{ route('users.create') }}" class="btn btn-primary"> New User </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header row justify-content-between">
            <div class="card-title col-md-4">Users</div>
            <div class="col-md-4">
                <form method="GET" action="{{ route('users.index') }}">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search users..."
                            value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary mx-2">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            @if ($users->isEmpty())
                <div class="alert alert-info text-center m-3">
                    <i class="fa fa-info-circle mr-2"></i> No user found.
                </div>
            @else
                <table class="table table-striped mt-3">
                    <thead>
                        <tr>
                            <th scope="col"> Name </th>
                            <th scope="col"> Email </th>
                            <th scope="col"> Role </th>
                            <th scope="col"> Status</th>
                            <th scope="col"> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="align-middle">{{ $user->name }}</td>
                                <td class="align-middle">{{ $user->email }}</td>
                                <td class="align-middle">{{ $user->roles->first()->name ?? 'No Role' }}
                                </td>
                                <td class="align-middle">
                                    @if ($user->status == 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="align-middle">

                                    {{-- view --}}
                                    <a href="#" data-toggle="modal" data-target="#viewModal-{{ $user->id }}">
                                        <i data-bs-toggle="tooltip" data-bs-placement="top" title="View user"
                                            class="bi bi-eye-fill action-icon"></i>
                                    </a>

                                    {{-- edit --}}
                                    <a href="{{ route('users.edit', $user) }}">
                                        <i class="bi bi-pencil-square action-icon" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Edit User"></i>
                                    </a>

                                    @if ($user->status == 'active')
                                        <form action="{{ route('users.deactivate', $user) }}" method="POST"
                                            style="display:inline;" onsubmit="return confirm('Deactivate this user?');">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="action-icon" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Deactivate User">
                                                <i class="bi bi-ban text-danger"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('users.activate', $user) }}" method="POST"
                                            style="display:inline;" onsubmit="return confirm('Activate this user?');">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="action-icon" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Activate User">
                                                <i class="bi bi-check action-icon"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- pagination --}}
                <div class=" my-2">
                    {{ $users->links('vendor.pagination.bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
    {{-- <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold">
                            <i class="fa fa-users mr-2"></i> User Management
                        </h1>
                        <a href="{{ route('users.create') }}" class="btn btn-primary"> New User </a>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <form method="GET" action="{{ route('users.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search users..."
                                value="{{ request('search') }}"
                                style="border-radius: 20px 0 0 20px; border-color: #4B2E83;">
                            <div class="input-group-append">
                                <button type="submit" class="btn"
                                    style="background-color: #4B2E83; color: white; border-radius: 0 20px 20px 0;">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">List of Users</strong>
                        </div>
                        <div class="card-body p-0">
                            @if (session('success'))
                                <div class="alert alert-success m-3">{{ session('success') }}</div>
                            @endif
                            @if ($users->isEmpty())
                                <div class="alert alert-info text-center m-3">
                                    <i class="fa fa-info-circle mr-2"></i> No users found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead style="background-color: #4B2E83; color: white;">
                                            <tr>
                                                <th scope="col"><i class="fa fa-user mr-2"></i> Name</th>
                                                <th scope="col"><i class="fa fa-envelope mr-2"></i> Email</th>
                                                <th scope="col"><i class="fa fa-user-tag mr-2"></i> Role</th>
                                                <th scope="col"><i class="fa fa-toggle-on mr-2"></i> Status</th>
                                                <th scope="col"><i class="fa fa-cogs mr-2"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($users as $user)
                                                <tr>
                                                    <td class="align-middle">{{ $user->name }}</td>
                                                    <td class="align-middle">{{ $user->email }}</td>
                                                    <td class="align-middle">{{ $user->roles->first()->name ?? 'No Role' }}
                                                    </td>
                                                    <td class="align-middle">
                                                        @if ($user->status == 'active')
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-danger">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle">
                                                        <a href="#" class="btn btn-sm btn-info action-btn"
                                                            data-toggle="modal" data-target="#viewModal-{{ $user->id }}"
                                                            title="View">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('users.edit', $user) }}"
                                                            class="btn btn-sm btn-warning action-btn" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        @if ($user->status == 'active')
                                                            <form action="{{ route('users.deactivate', $user) }}"
                                                                method="POST" style="display:inline;"
                                                                onsubmit="return confirm('Deactivate this user?');">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-danger action-btn"
                                                                    title="Deactivate">
                                                                    <i class="fa fa-ban"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form action="{{ route('users.activate', $user) }}"
                                                                method="POST" style="display:inline;"
                                                                onsubmit="return confirm('Activate this user?');">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-success action-btn"
                                                                    title="Activate">
                                                                    <i class="fa fa-check"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    </div> --}}
@endsection

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
                                    <a href="#" data-bs-toggle="modal"
                                        data-bs-target="#viewModal-{{ $user->id }}">
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
@endsection

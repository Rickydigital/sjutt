@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold" style="color: #4B2E83;">
                            <i class="fa fa-users mr-2"></i> User Management
                        </h1>
                        <a href="{{ route('users.create') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                            <i class="fa fa-plus mr-1"></i> Create User
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <form method="GET" action="{{ route('users.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search users..." 
                                value="{{ request('search') }}" style="border-radius: 20px 0 0 20px; border-color: #4B2E83;">
                            <div class="input-group-append">
                                <button type="submit" class="btn" style="background-color: #4B2E83; color: white; border-radius: 0 20px 20px 0;">
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
                                                    <td class="align-middle">{{ $user->roles->first()->name ?? 'No Role' }}</td>
                                                    <td class="align-middle">
                                                        @if ($user->status == 'active')
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-danger">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle">
                                                        <a href="#" class="btn btn-sm btn-info action-btn" data-toggle="modal" 
                                                            data-target="#viewModal-{{ $user->id }}" title="View">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('users.edit', $user) }}" 
                                                            class="btn btn-sm btn-warning action-btn" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        @if ($user->status == 'active')
                                                            <form action="{{ route('users.deactivate', $user) }}" method="POST" 
                                                                style="display:inline;" onsubmit="return confirm('Deactivate this user?');">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-sm btn-danger action-btn" title="Deactivate">
                                                                    <i class="fa fa-ban"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form action="{{ route('users.activate', $user) }}" method="POST" 
                                                                style="display:inline;" onsubmit="return confirm('Activate this user?');">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-sm btn-success action-btn" title="Activate">
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

            <!-- Modals -->
            @foreach ($users as $user)
                <div class="modal fade" id="viewModal-{{ $user->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header" style="background-color: #4B2E83; color: white;">
                                <h5 class="modal-title">User Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-4"><strong>Name:</strong></div>
                                    <div class="col-md-8">{{ $user->name }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Email:</strong></div>
                                    <div class="col-md-8">{{ $user->email }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Role:</strong></div>
                                    <div class="col-md-8">{{ $user->roles->first()->name ?? 'No Role' }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Status:</strong></div>
                                    <div class="col-md-8">{{ $user->status }}</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination -->
            @if ($users->hasPages())
                <div class="row mt-4">
                    <div class="col-md-12">
                        {{ $users->appends(['search' => request('search')])->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('[title]').tooltip({ placement: 'top', trigger: 'hover' });
        });
    </script>
@endsection

<style>
    .table-bordered th, .table-bordered td { border: 2px solid #4B2E83 !important; }
    .table-hover tbody tr:hover { background-color: #f1eef9; transition: background-color 0.3s ease; }
    .btn:hover { opacity: 0.85; transform: translateY(-1px); transition: all 0.2s ease; }
    .card { border: none; border-radius: 10px; overflow: hidden; }
    .action-btn { min-width: 36px; padding: 6px; margin: 0 4px; }
    .table th, .table td { vertical-align: middle; }
</style>
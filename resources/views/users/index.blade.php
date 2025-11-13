@extends('components.app-main-layout')

@section('content')
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex align-items-center justify-content-between">
                <h1 class="font-weight-bold">
                    Users Management
                </h1>
                <div>
                    <a href="{{ route('users.create') }}" class="btn btn-primary">New User</a>
                    @if (Auth::user()->hasRole('Admin'))
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">Import Users</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row justify-content-between align-items-center">
                <div class="card-title col-md-4">Users</div>
                <div class="col-md-5">
                    <form method="GET" action="{{ route('users.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by name or email..." 
                                value="{{ request('search') }}" aria-label="Search users">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if ($users->isEmpty())
                <div class="alert alert-info text-center m-3">
                    <i class="bi bi-info-circle mr-2"></i> No user found.
                </div>
            @else
                <table class="table table-striped mt-3">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
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
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#viewModal-{{ $user->id }}"
                                        class="action-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="View user">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="{{ route('users.edit', $user) }}" class="action-icon"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Edit User">
                                        <i class="bi bi-pencil-square"></i>
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
                                                <i class="bi bi-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="my-2">
                    {{ $users->links('vendor.pagination.bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

    @if (Auth::user()->hasRole('Admin'))
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">Import Users</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="file">Upload Excel File</label>
                                <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls" required>
                                @error('file')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <p class="mt-2">
                                <small>Expected columns: name, email, role, phone (optional), gender (optional, values: Male, Female, Other).</small>
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @foreach ($users as $user)
        <div class="modal fade" id="viewModal-{{ $user->id }}" tabindex="-1" aria-labelledby="viewModalLabel-{{ $user->id }}"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewModalLabel-{{ $user->id }}">User Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Name:</strong> {{ $user->name }}</p>
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <p><strong>Role:</strong> {{ $user->roles->first()->name ?? 'No Role' }}</p>
                        <p><strong>Phone:</strong> {{ $user->phone ?? 'N/A' }}</p>
                        <p><strong>Gender:</strong> {{ $user->gender ?? 'N/A' }}</p>
                        <p><strong>Status:</strong> 
                            @if ($user->status == 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
    @endpush
@endsection
@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <!-- Header -->
    <div class="card-header" style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0 text-white">Roles & Permissions Management</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                <i class="bi bi-plus-circle"></i> Add Role
            </button>
        </div>

        <!-- Search -->
        <div class="bg-white p-3 rounded shadow-sm">
            <form action="{{ route('roles.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label class="form-label text-muted small mb-1">Search Role</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Role name..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-0 m-0 py-2 text-center">
                {{ session('success') }}
            </div>
        @endif

        @if ($roles->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-shield-lock display-1"></i>
                <p class="mt-3">No roles found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
                        <tr>
                            <th>#</th>
                            <th>Role Name</th>
                            <th>Permissions</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td>{{ $loop->iteration + ($roles->currentPage()-1)*$roles->perPage() }}</td>
                                <td><strong>{{ $role->name }}</strong></td>
                                <td>
                                    @if($role->permissions->count())
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($role->permissions as $perm)
                                                <span class="badge bg-info text-white">{{ $perm->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#editRoleModal{{ $role->id }}"
                                            title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    @if(!$role->users()->exists())
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#deleteRoleModal{{ $role->id }}"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            {{-- ==================== EDIT MODAL ==================== --}}
                            <div class="modal fade" id="editRoleModal{{ $role->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <form action="{{ route('roles.update', $role) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Edit Role</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Role Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control"
                                                           value="{{ old('name', $role->name) }}" required>
                                                </div>

                                                <label class="form-label">Permissions</label>
                                                <div class="row row-cols-2 row-cols-md-3 g-2">
                                                    @foreach(\Spatie\Permission\Models\Permission::all() as $perm)
                                                        <div class="col">
                                                            <div class="form-check">
                                                                <input class="form-check-input perm-checkbox"
                                                                       type="checkbox" name="permissions[]"
                                                                       value="{{ $perm->id }}"
                                                                       id="perm{{ $perm->id }}_{{ $role->id }}"
                                                                       {{ $role->hasPermissionTo($perm) ? 'checked' : '' }}>
                                                                <label class="form-check-label small"
                                                                       for="perm{{ $perm->id }}_{{ $role->id }}">
                                                                    {{ $perm->name }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Role</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- ==================== DELETE MODAL ==================== --}}
                            @if(!$role->users()->exists())
                            <div class="modal fade" id="deleteRoleModal{{ $role->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Delete role <strong>"{{ $role->name }}"</strong>?</p>
                                                <p class="text-danger small">This action cannot be undone.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-light border-top-0">
                {{ $roles->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

{{-- ==================== CREATE MODAL ==================== --}}
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('roles.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Create New Role</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <label class="form-label">Permissions</label>
                    <div class="row row-cols-2 row-cols-md-3 g-2">
                        @foreach(\Spatie\Permission\Models\Permission::all() as $perm)
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="permissions[]" value="{{ $perm->id }}"
                                           id="perm{{ $perm->id }}">
                                    <label class="form-check-label small" for="perm{{ $perm->id }}">
                                        {{ $perm->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Role</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
    .badge { font-size: .8em; }
</style>
@endsection
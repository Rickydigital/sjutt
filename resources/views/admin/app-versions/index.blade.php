@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <!-- Header -->
    <div class="card-header" style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0 text-white">App Versions Management</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createVersionModal">
                <i class="bi bi-plus-circle"></i> Add Version
            </button>
        </div>

        <!-- Search -->
        <div class="bg-white p-3 rounded shadow-sm">
            <form action="{{ route('admin.app-versions.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label class="form-label text-muted small mb-1">Search Version</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Version name..." value="{{ request('search') }}">
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

        @if ($versions->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-phone display-1"></i>
                <p class="mt-3">No app versions found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
                        <tr>
                            <th>#</th>
                            <th>Platform</th>
                            <th>Version Name</th>
                            <th>Version Code</th>
                            <th>Force Update</th>
                            <th>Whats New</th>
                            <th>Download URL</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($versions as $version)
                            <tr>
                                <td>{{ $loop->iteration + ($versions->currentPage()-1)*$versions->perPage() }}</td>
                                <td><span class="badge bg-{{ $version->platform == 'android' ? 'success' : 'info' }}">{{ strtoupper($version->platform) }}</span></td>
                                <td><strong>{{ $version->version_name }}</strong></td>
                                <td>{{ $version->version_code }}</td>
                                <td>
                                    @if($version->is_force_update)
                                        <span class="badge bg-danger rounded-pill">Yes (Force Update)</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">No</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($version->whats_new, 50) }}</td>
                                <td>
                                    <a href="{{ route('app.download', basename($version->download_url)) }}" 
                                    target="_blank" 
                                    class="text-primary small">
                                        Download
                                    </a>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#editVersionModal{{ $version->id }}"
                                            title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal" data-bs-target="#deleteVersionModal{{ $version->id }}"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            {{-- ==================== EDIT MODAL ==================== --}}
                            <div class="modal fade" id="editVersionModal{{ $version->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <form action="{{ route('admin.app-versions.update', $version) }}" method="POST" enctype="multipart/form-data">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Edit App Version</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                @include('admin.app-versions._form', ['version' => $version, 'isEdit' => true])
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Version</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- ==================== DELETE MODAL ==================== --}}
                            <div class="modal fade" id="deleteVersionModal{{ $version->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('admin.app-versions.destroy', $version) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Delete version <strong>{{ $version->version_name }}</strong> (Code: {{ $version->version_code }})?</p>
                                                <p class="text-danger small">This will also delete the associated APK/IPA file.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-light border-top-0">
                {{ $versions->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

{{-- ==================== CREATE MODAL ==================== --}}
<div class="modal fade" id="createVersionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.app-versions.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Create New App Version</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('admin.app-versions._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Version</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
</style>
@endsection
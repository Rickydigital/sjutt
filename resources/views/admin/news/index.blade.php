@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <!-- Header: Purple Gradient -->
    <div class="card-header" style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0 text-white">News Management</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createNewsModal">
                <i class="bi bi-plus-circle"></i> Add News
            </button>
        </div>

        <!-- Search & Filter: White Background -->
        <div class="bg-white p-3 rounded shadow-sm">
            <form action="{{ route('news.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label text-muted small mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Title or description..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small mb-1">Creator</label>
                    <select name="filter_creator" class="form-select form-select-sm">
                        <option value="">All Creators</option>
                        @foreach(\App\Models\News::select('created_by')->distinct()->with('user')->get() as $n)
                            @if($n->user)
                                <option value="{{ $n->created_by }}"
                                    {{ request('filter_creator') == $n->created_by ? 'selected' : '' }}>
                                    {{ $n->user->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
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

        @if ($news->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-newspaper display-1"></i>
                <p class="mt-3">No news found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <!-- Purple Header -->
                    <thead style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Image</th>
                            <th scope="col">Title</th>
                            <th scope="col">Description</th>
                            <th scope="col">Creator</th>
                            <th scope="col">Media</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($news as $item)
                            <tr>
                                <td>{{ $loop->iteration + ($news->currentPage() - 1) * $news->perPage() }}</td>
                                <td>
                                    @if($item->image)
                                        <img src="{{ Storage::url($item->image) }}"
                                             class="rounded" style="width:50px;height:50px;object-fit:cover;">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                             style="width:50px;height:50px;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ Str::limit($item->title, 30) }}</strong>
                                </td>
                                <td>
                                    <small class="text-muted">{{ Str::limit(strip_tags($item->description), 50) }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">{{ $item->user?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($item->video)
                                        <span class="badge bg-success">Video</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <!-- Show Button (Always Visible) -->
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#showNewsModal{{ $item->id }}"
                                            title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Edit/Delete: Only Owner or Admin -->
                                    @php
                                        $isOwner = Auth::id() === $item->created_by;
                                        $isAdmin = Auth::check() && Auth::user()->hasRole('Admin');
                                        $canEditDelete = $isOwner || $isAdmin;
                                    @endphp

                                    @if($canEditDelete)
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#editNewsModal{{ $item->id }}"
                                                title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#deleteNewsModal{{ $item->id }}"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            <!-- ==================== SHOW MODAL ==================== -->
                            <div class="modal fade" id="showNewsModal{{ $item->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title">{{ $item->title }}</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            @if($item->image)
                                                <div class="text-center mb-3">
                                                    <img src="{{ Storage::url($item->image) }}"
                                                         class="img-fluid rounded shadow-sm" style="max-height: 400px;">
                                                </div>
                                            @endif

                                            <p><strong>Description:</strong></p>
                                            <div class="bg-light p-3 rounded mb-3">{!! nl2br(e($item->description)) !!}</div>

                                            @if($item->video)
                                                <p><strong>Video:</strong></p>
                                                <video controls class="w-100 rounded shadow-sm mb-3" style="max-height: 400px;">
                                                    <source src="{{ Storage::url($item->video) }}" type="video/mp4">
                                                    Your browser does not support video.
                                                </video>
                                            @endif

                                            <hr>
                                            <p class="text-muted small">
                                                <strong>Created by:</strong> {{ $item->user?->name ?? 'Unknown' }}<br>
                                                <strong>Created on:</strong> {{ $item->created_at->format('d M Y, h:i A') }}
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ==================== EDIT MODAL ==================== -->
                            <div class="modal fade" id="editNewsModal{{ $item->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <form action="{{ route('news.update', $item) }}" method="POST" enctype="multipart/form-data">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Edit News</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-12">
                                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                                        <input type="text" name="title" class="form-control"
                                                               value="{{ old('title', $item->title) }}" required>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label class="form-label">Description <span class="text-danger">*</span></label>
                                                        <textarea name="description" class="form-control" rows="5" required>{{ old('description', $item->description) }}</textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Image</label>
                                                        <input type="file" name="image" class="form-control" accept="image/*">
                                                        @if($item->image)
                                                            <small class="text-success d-block mt-1">
                                                                Current: {{ basename($item->image) }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Video</label>
                                                        <input type="file" name="video" class="form-control" accept="video/*">
                                                        @if($item->video)
                                                            <small class="text-success d-block mt-1">
                                                                Current: {{ basename($item->video) }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update News</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ==================== DELETE MODAL ==================== -->
                            <div class="modal fade" id="deleteNewsModal{{ $item->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('news.destroy', $item) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete:</p>
                                                <strong>"{{ $item->title }}"</strong>
                                                <p class="text-danger mt-2"><small>This action cannot be undone.</small></p>
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

            <!-- Pagination -->
            <div class="card-footer bg-light border-top-0">
                {{ $news->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<!-- ==================== CREATE MODAL ==================== -->
<div class="modal fade" id="createNewsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('news.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Create New News</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Video</label>
                            <input type="file" name="video" class="form-control" accept="video/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save News</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    .badge {
        font-size: 0.8em;
    }
</style>
@endsection
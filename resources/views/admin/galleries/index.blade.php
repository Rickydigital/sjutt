@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <!-- Header -->
    <div class="card-header" style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0 text-white">Gallery Management</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createGalleryModal">
                Add Gallery
            </button>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white p-3 rounded shadow-sm">
            <form action="{{ route('gallery.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label text-muted small mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Description..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small mb-1">Creator</label>
                    <select name="filter_creator" class="form-select form-select-sm">
                        <option value="">All Creators</option>
                        @foreach($creators as $id => $name)
                            <option value="{{ $id }}" {{ request('filter_creator') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        Search
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

        @if ($galleries->isEmpty())
            <div class="p-5 text-center text-muted">
                <p class="mt-3">No gallery items found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
                        <tr>
                            <th>#</th>
                            <th>Images</th>
                            <th>Description</th>
                            <th>Creator</th>
                            <th>Count</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($galleries as $gallery)
                            @php
                                $isOwner = Auth::id() === $gallery->created_by;
                                $isAdmin = Auth::check() && Auth::user()->hasRole('Admin');
                                $canEditDelete = $isOwner || $isAdmin;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration + ($galleries->currentPage() - 1) * $galleries->perPage() }}</td>
                                <td>
                                    @if($gallery->media && count($gallery->media) > 0)
                                        <div class="d-flex gap-1 flex-wrap">
                                            @foreach(array_slice($gallery->media, 0, 3) as $img)
                                                <img src="{{ $img }}" class="rounded" style="width:40px;height:40px;object-fit:cover;">
                                            @endforeach
                                            @if(count($gallery->media) > 3)
                                                <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                                     style="width:40px;height:40px;font-size:0.7rem;">
                                                    +{{ count($gallery->media) - 3 }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        No images
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ Str::limit(strip_tags($gallery->description), 50) }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">{{ $gallery->user?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ count($gallery->media) }}</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#showGallery{{ $gallery->id }}">
                                        View
                                    </button>

                                    @if($canEditDelete)
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#editGallery{{ $gallery->id }}">
                                            Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#deleteGallery{{ $gallery->id }}">
                                            Delete
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            <!-- SHOW MODAL (modal-lg) -->
                            <div class="modal fade" id="showGallery{{ $gallery->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title">Gallery Item</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Description:</strong></p>
                                            <div class="bg-light p-3 rounded mb-4">{!! nl2br(e($gallery->description)) !!}</div>

                                            @if($gallery->media && count($gallery->media))
                                                <div id="carousel{{ $gallery->id }}" class="carousel slide" data-bs-ride="carousel">
                                                    <div class="carousel-inner">
                                                        @foreach($gallery->media as $i => $img)
                                                            <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                                                                <img src="{{ $img }}" class="d-block w-100 rounded shadow"
                                                                     style="max-height:400px;object-fit:contain;background:#000;">
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    @if(count($gallery->media) > 1)
                                                        <button class="carousel-control-prev" type="button"
                                                                data-bs-target="#carousel{{ $gallery->id }}" data-bs-slide="prev">
                                                            <span class="carousel-control-prev-icon"></span>
                                                        </button>
                                                        <button class="carousel-control-next" type="button"
                                                                data-bs-target="#carousel{{ $gallery->id }}" data-bs-slide="next">
                                                            <span class="carousel-control-next-icon"></span>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif

                                            <hr>
                                            <p class="text-muted small">
                                                <strong>Created by:</strong> {{ $gallery->user?->name ?? 'Unknown' }}<br>
                                                <strong>Created on:</strong> {{ $gallery->created_at->format('d M Y, h:i A') }}
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- EDIT MODAL -->
                            <div class="modal fade" id="editGallery{{ $gallery->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <form action="{{ route('gallery.update', $gallery) }}" method="POST" enctype="multipart/form-data">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Edit Gallery</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                                    <textarea name="description" class="form-control" rows="4" required>{{ old('description', $gallery->description) }}</textarea>
                                                </div>

                                                <!-- Existing Images -->
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Current Images ({{ count($gallery->media) }})</label>
                                                    <div class="row g-2" id="existing-images-{{ $gallery->id }}">
                                                        @foreach($gallery->media as $img)
                                                            <div class="col-3 position-relative">
                                                                <img src="{{ $img }}" class="img-thumbnail" style="height:80px;object-fit:cover;">
                                                                <div class="form-check position-absolute top-0 end-0 m-1">
                                                                    <input class="form-check-input" type="checkbox" name="keep_images[]" value="{{ $img }}" checked>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <small class="text-muted">Uncheck to remove image.</small>
                                                </div>

                                                <!-- New Images -->
                                                <div class="mb-3">
                                                    <label class="form-label">Add New Images (Max 10 total)</label>
                                                    <div id="edit-image-inputs-{{ $gallery->id }}">
                                                        <div class="input-group mb-2">
                                                            <input type="file" name="new_media[]" class="form-control" accept="image/*"
                                                                   onchange="previewImages(this, 'preview-edit-{{ $gallery->id }}-0')">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="removeInput(this)">
                                                                Remove
                                                            </button>
                                                        </div>
                                                        <div id="preview-edit-{{ $gallery->id }}-0" class="row g-2 mb-2"></div>
                                                    </div>

                                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                                            onclick="addEditInput({{ $gallery->id }})">
                                                        Add more images
                                                    </button>
                                                    <small class="d-block text-muted mt-1">Maximum 10 images total.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- DELETE MODAL -->
                            <div class="modal fade" id="deleteGallery{{ $gallery->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('gallery.destroy', $gallery) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete this gallery item?</p>
                                                <p class="text-danger"><small>All images will be permanently deleted.</small></p>
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
                {{ $galleries->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<!-- CREATE MODAL -->
<div class="modal fade" id="createGalleryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('gallery.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Create Gallery Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Images (1–10) <span class="text-danger">*</span></label>
                        <div id="create-image-inputs">
                            <div class="input-group mb-2">
                                <input type="file" name="media[]" class="form-control" accept="image/*" required
                                       onchange="previewImages(this, 'preview-create-0')">
                                <button type="button" class="btn btn-outline-secondary" onclick="removeInput(this)">
                                    Remove
                                </button>
                            </div>
                            <div id="preview-create-0" class="row g-2 mb-2"></div>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-create-input">
                            Add more images
                        </button>
                        <small class="d-block text-muted mt-1">Maximum 10 images total.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
let createInputIndex = 1;
const editInputIndices = {};

// ──────────────────────────────────────────────────────────────
// Preview images for a single input
// ──────────────────────────────────────────────────────────────
function previewImages(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    const files = input.files;

    Array.from(files).forEach(file => {
        if (file.type.match('image.*')) {
            const reader = new FileReader();
            reader.onload = e => {
                const col = document.createElement('div');
                col.className = 'col-3 position-relative';
                col.innerHTML = `
                    <img src="${e.target.result}" class="img-thumbnail" style="height:80px;object-fit:cover;">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-1"
                            onclick="this.parentElement.remove()"></button>
                `;
                preview.appendChild(col);
            };
            reader.readAsDataURL(file);
        }
    });
}

// ──────────────────────────────────────────────────────────────
// Remove input group
// ──────────────────────────────────────────────────────────────
function removeInput(btn) {
    const inputGroup = btn.closest('.input-group');
    const preview = inputGroup.nextElementSibling;
    if (preview && preview.classList.contains('row')) preview.remove();
    inputGroup.remove();
}

// ──────────────────────────────────────────────────────────────
// Add input for Create
// ──────────────────────────────────────────────────────────────
document.getElementById('add-create-input')?.addEventListener('click', () => {
    const total = document.querySelectorAll('#create-image-inputs input[type=file]').length;
    if (total >= 10) {
        alert('Maximum 10 images allowed.');
        return;
    }

    const wrapper = document.getElementById('create-image-inputs');
    const idx = createInputIndex++;

    const html = `
        <div class="input-group mb-2">
            <input type="file" name="media[]" class="form-control" accept="image/*"
                   onchange="previewImages(this, 'preview-create-${idx}')">
            <button type="button" class="btn btn-outline-secondary" onclick="removeInput(this)">
                Remove
            </button>
        </div>
        <div id="preview-create-${idx}" class="row g-2 mb-2"></div>
    `;
    wrapper.insertAdjacentHTML('beforeend', html);
});

// ──────────────────────────────────────────────────────────────
// Add input for Edit
// ──────────────────────────────────────────────────────────────
function addEditInput(galleryId) {
    if (!editInputIndices[galleryId]) editInputIndices[galleryId] = 1;

    const keepCount = document.querySelectorAll(`#existing-images-${galleryId} input[name="keep_images[]"]:checked`).length;
    const newCount   = document.querySelectorAll(`#edit-image-inputs-${galleryId} input[type=file]`).length;
    if (keepCount + newCount >= 10) {
        alert('Maximum 10 images allowed.');
        return;
    }

    const wrapper = document.getElementById(`edit-image-inputs-${galleryId}`);
    const idx = editInputIndices[galleryId]++;

    const html = `
        <div class="input-group mb-2">
            <input type="file" name="new_media[]" class="form-control" accept="image/*"
                   onchange="previewImages(this, 'preview-edit-${galleryId}-${idx}')">
            <button type="button" class="btn btn-outline-secondary" onclick="removeInput(this)">
                Remove
            </button>
        </div>
        <div id="preview-edit-${galleryId}-${idx}" class="row g-2 mb-2"></div>
    `;
    wrapper.insertAdjacentHTML('beforeend', html);
}

// ──────────────────────────────────────────────────────────────
// **ONLY VALIDATE CREATE MODAL** – Skip Edit
// ──────────────────────────────────────────────────────────────
function validateCreateOnly() {
    document.querySelectorAll('form').forEach(form => {
        // Only apply validation to Create form
        if (form.querySelector('input[name="media[]"]')) {
            form.onsubmit = function (e) {
                const inputs = form.querySelectorAll('input[name="media[]"]');
                let total = 0;

                inputs.forEach(inp => {
                    if (inp.files && inp.files.length > 0) {
                        total += inp.files.length;
                    }
                });

                if (total < 1 || total > 10) {
                    e.preventDefault();
                    alert('Please select between 1 and 10 images.');
                }
            };
        }
        // Edit form: NO client-side validation → rely on server
    });
}

document.addEventListener('DOMContentLoaded', validateCreateOnly);
</script>
@endsection

@section('styles')
<style>
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
    .carousel-item img { background: #000; }
    .form-check-input { transform: scale(0.8); }
    .input-group .btn { min-width: 40px; }
</style>
@endsection
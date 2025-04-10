@extends('layouts.admin')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4 text-center">Edit News</h1>

    <!-- Success Message -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('news.update', $news) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Title -->
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                   id="title" name="title" value="{{ old('title', $news->title) }}" required>
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" 
                      id="description" name="description" rows="5" required>{{ old('description', $news->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Image Upload with Preview -->
        <div class="mb-3">
            <label for="image" class="form-label">Image (Optional)</label>
            @if ($news->image)
                <div class="mb-2">
                    <img src="{{ Storage::url($news->image) }}" alt="Current Image" class="img-thumbnail" style="max-width: 200px;">
                    <small class="d-block text-muted">Current Image</small>
                </div>
            @endif
            <input type="file" class="form-control @error('image') is-invalid @enderror" 
                   id="image" name="image" accept="image/jpeg,image/png,image/jpg">
            @error('image')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Video Upload with Preview -->
        <div class="mb-3">
            <label for="video" class="form-label">Video (Optional)</label>
            @if ($news->video)
                <div class="mb-2">
                    <video controls class="img-thumbnail" style="max-width: 200px;">
                        <source src="{{ Storage::url($news->video) }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <small class="d-block text-muted">Current Video</small>
                </div>
            @endif
            <input type="file" class="form-control @error('video') is-invalid @enderror" 
                   id="video" name="video" accept="video/mp4,video/avi,video/mov">
            @error('video')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="d-flex justify-content-end">
            <a href="{{ route('news.index') }}" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Update News</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
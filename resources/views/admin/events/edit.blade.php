@extends('layouts.admin')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4 text-center">Edit Event</h1>

    <!-- Success/Error Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('events.update', $event) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Title -->
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                   id="title" name="title" value="{{ old('title', $event->title) }}" required>
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" 
                      id="description" name="description" rows="5" required>{{ old('description', $event->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Location -->
        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                   id="location" name="location" value="{{ old('location', $event->location) }}" required>
            @error('location')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Event Time -->
        <div class="mb-3">
            <label for="event_time" class="form-label">Event Date & Time</label>
            <input type="datetime-local" class="form-control @error('event_time') is-invalid @enderror" 
                   id="event_time" name="event_time" 
                   value="{{ old('event_time', $event->event_time->format('Y-m-d\TH:i')) }}" required>
            @error('event_time')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- User Allowed -->
        <div class="mb-3">
            <label for="user_allowed" class="form-label">Allow Users?</label>
            <select class="form-select @error('user_allowed') is-invalid @enderror" 
                    id="user_allowed" name="user_allowed">
                <option value="1" {{ old('user_allowed', $event->user_allowed) ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('user_allowed', $event->user_allowed) ? '' : 'selected' }}>No</option>
            </select>
            @error('user_allowed')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Media Upload with Preview -->
        <div class="mb-3">
            <label for="media" class="form-label">Media (Optional)</label>
            @if ($event->media)
                <div class="mb-2">
                    @if (in_array(pathinfo($event->media, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png']))
                        <img src="{{ Storage::url($event->media) }}" alt="Current Media" class="img-thumbnail" style="max-width: 200px;">
                    @else
                        <video controls class="img-thumbnail" style="max-width: 200px;">
                            <source src="{{ Storage::url($event->media) }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    @endif
                    <small class="d-block text-muted">Current Media</small>
                </div>
            @endif
            <input type="file" class="form-control @error('media') is-invalid @enderror" 
                   id="media" name="media" accept="image/jpeg,image/png,image/jpg,video/mp4,video/avi,video/mov">
            @error('media')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="d-flex justify-content-end">
            <a href="{{ route('events.index') }}" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Event</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
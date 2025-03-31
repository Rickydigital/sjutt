@extends('layouts.admin')

@section('content')
    <h1>Edit Gallery Item</h1>
    <form action="{{ route('admin.galleries.update', $gallery->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" required>{{ $gallery->description }}</textarea>
        </div>
        <div class="form-group">
            <label for="media">Media (Image/Video URL)</label>
            <input type="text" name="media" id="media" class="form-control" value="{{ $gallery->media }}" required>
        </div>
        <button type="submit" class="btn btn-success">Update Gallery Item</button>
    </form>
@endsection

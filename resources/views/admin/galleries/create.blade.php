@extends('layouts.admin')

@section('content')
    <h1>Create Gallery Item</h1>
    <form action="{{ route('admin.galleries.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" required></textarea>
        </div>
        <div class="form-group">
            <label for="media">Media (Image/Video URL)</label>
            <input type="text" name="media" id="media" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Create Gallery Item</button>
    </form>
@endsection

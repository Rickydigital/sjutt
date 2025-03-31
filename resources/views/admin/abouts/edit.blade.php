@extends('layouts.admin')

@section('content')
    <h1>Edit About Us</h1>
    <form action="{{ route('admin.abouts.update', $about->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ $about->title }}" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" required>{{ $about->description }}</textarea>
        </div>
        <button type="submit" class="btn btn-success">Update About Us</button>
    </form>
@endsection

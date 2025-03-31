@extends('layouts.admin')

@section('content')
    <h1>Create About Us</h1>
    <form action="{{ route('about.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-success">Create About Us</button>
    </form>
@endsection

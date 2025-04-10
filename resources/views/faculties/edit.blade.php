@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="mb-4" style="color: #4B2E83;"><i class="fa fa-edit mr-2"></i>Edit Faculty</h1>
    <form action="{{ route('faculties.update', $faculty->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Faculty Name</label>
            <input type="text" class="form-control" name="name" value="{{ $faculty->name }}" required>
        </div>
        <button type="submit" class="btn btn-primary mt-2">
            <i class="fa fa-save mr-1"></i> Update Faculty
        </button>
    </form>
</div>
@endsection

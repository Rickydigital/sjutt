@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="mb-4" style="color: #4B2E83;"><i class="fa fa-edit mr-2"></i>Edit Venue</h1>
    <form action="{{ route('venues.update', $venue->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Venue Name</label>
            <input type="text" class="form-control" name="name" value="{{ $venue->name }}" required>
        </div>
        <button type="submit" class="btn btn-primary mt-2">
            <i class="fa fa-save mr-1"></i> Update Venue
        </button>
    </form>
</div>
@endsection

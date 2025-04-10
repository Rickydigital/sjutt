@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="mb-4" style="color: #4B2E83;"><i class="fa fa-plus mr-2"></i>Create Venue</h1>
    <form action="{{ route('venues.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Venue Name</label>
            <input type="text" class="form-control" name="name" required placeholder="e.g. Main Hall">
        </div>
        <button type="submit" class="btn btn-success mt-2">
            <i class="fa fa-check mr-1"></i> Save Venue
        </button>
    </form>
</div>
@endsection

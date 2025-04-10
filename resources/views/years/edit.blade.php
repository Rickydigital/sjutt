@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="mb-4" style="color: #4B2E83;"><i class="fa fa-edit mr-2"></i>Edit Year</h1>
    <form action="{{ route('years.update', $year->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="year">Year</label>
            <input type="text" class="form-control" name="year" value="{{ $year->year }}" required>
        </div>
        <button type="submit" class="btn btn-primary mt-2">
            <i class="fa fa-save mr-1"></i> Update Year
        </button>
    </form>
</div>
@endsection

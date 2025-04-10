@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="mb-4" style="color: #4B2E83;"><i class="fa fa-plus mr-2"></i>Create Year</h1>
    <form action="{{ route('years.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="year">Year</label>
            <input type="text" class="form-control" name="year" required placeholder="e.g. 1st ">
        </div>
        <button type="submit" class="btn btn-success mt-2">
            <i class="fa fa-check mr-1"></i> Save Year
        </button>
    </form>
</div>
@endsection

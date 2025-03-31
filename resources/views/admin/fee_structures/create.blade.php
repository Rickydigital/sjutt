@extends('layouts.admin')

@section('content')
    <h1>Create Fee Structure</h1>
    <form action="{{ route('admin.fee_structures.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="program_type">Program Type</label>
            <input type="text" name="program_type" id="program_type" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="program_name">Program Name</label>
            <input type="text" name="program_name" id="program_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="first_year">First Year Fee</label>
            <input type="number" name="first_year" id="first_year" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="continuing_year">Continuing Year Fee</label>
            <input type="number" name="continuing_year" id="continuing_year" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="final_year">Final Year Fee</label>
            <input type="number" name="final_year" id="final_year" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Create Fee Structure</button>
    </form>
@endsection

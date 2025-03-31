@extends('layouts.admin')

@section('content')
    <h1>Edit Fee Structure</h1>
    <form action="{{ route('admin.fee_structures.update', $feeStructure->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="program_type">Program Type</label>
            <input type="text" name="program_type" id="program_type" class="form-control" value="{{ $feeStructure->program_type }}" required>
        </div>
        <div class="form-group">
            <label for="program_name">Program Name</label>
            <input type="text" name="program_name" id="program_name" class="form-control" value="{{ $feeStructure->program_name }}" required>
        </div>
        <div class="form-group">
            <label for="first_year">First Year Fee</label>
            <input type="number" name="first_year" id="first_year" class="form-control" value="{{ $feeStructure->first_year }}" required>
        </div>
        <div class="form-group">
            <label for="continuing_year">Continuing Year Fee</label>
            <input type="number" name="continuing_year" id="continuing_year" class="form-control" value="{{ $feeStructure->continuing_year }}" required>
        </div>
        <div class="form-group">
            <label for="final_year">Final Year Fee</label>
            <input type="number" name="final_year" id="final_year" class="form-control" value="{{ $feeStructure->final_year }}" required>
        </div>
        <button type="submit" class="btn btn-success">Update Fee Structure</button>
    </form>
@endsection

@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h1 class="font-weight-bold" style="color: #4B2E83;">
                        <i class="fa fa-plus-circle mr-2"></i> Create Fee Structure
                    </h1>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">New Fee Structure</strong>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('fee_structures.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="program_type" class="form-label">Fee Type</label>
                                    <select class="form-select" id="program_type" name="program_type" required>
                                        <option value="">Select Fee Type</option>
                                        <option value="TUITION_FEE_UNDERGRADUATE">Tuition Fee (Undergraduate)</option>
                                        <option value="TUITION_FEE_NON_DEGREE">Tuition Fee (Non-Degree)</option>
                                        <option value="TUITION_FEE_POSTGRADUATE">Tuition Fee (Postgraduate)</option>
                                        <option value="COMPULSORY_CHARGE_DIPLOMA">Compulsory Charge (Diploma)</option>
                                        <option value="COMPULSORY_CHARGE_CERTIFICATE">Compulsory Charge (Certificate)</option>
                                        <option value="COMPULSORY_CHARGE_BACHELOR">Compulsory Charge (Bachelor)</option>
                                        <option value="COMPULSORY_CHARGE_POSTGRADUATE">Compulsory Charge (Postgraduate)</option>
                                    </select>
                                    @error('program_type')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="program_name" class="form-label">Program Name</label>
                                    <input type="text" class="form-control" id="program_name" name="program_name" value="{{ old('program_name') }}" required>
                                    @error('program_name')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="first_year" class="form-label">First Year (TZS)</label>
                                    <input type="number" class="form-control" id="first_year" name="first_year" value="{{ old('first_year') }}" required>
                                    @error('first_year')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="continuing_year" class="form-label">Continuing Year (TZS)</label>
                                    <input type="number" class="form-control" id="continuing_year" name="continuing_year" value="{{ old('continuing_year') }}" required>
                                    @error('continuing_year')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="final_year" class="form-label">Final Year (TZS)</label>
                                    <input type="number" class="form-control" id="final_year" name="final_year" value="{{ old('final_year') }}" required>
                                    @error('final_year')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="text-end">
                                    <a href="{{ route('fee_structures.index') }}" class="btn btn-secondary" style="border-radius: 25px;">Cancel</a>
                                    <button type="submit" class="btn btn-primary" style="background-color: #4B2E83; border-color: #4B2E83; border-radius: 25px;">
                                        <i class="fa fa-save mr-1"></i> Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('style')
        <style>
            .form-control, .form-select {
                border-radius: 10px;
                border-color: #4B2E83;
            }
            .form-control:focus, .form-select:focus {
                border-color: #4B2E83;
                box-shadow: 0 0 5px rgba(75, 46, 131, 0.5);
            }
            .btn:hover {
                opacity: 0.85;
                transform: translateY(-1px);
                transition: all 0.2s ease;
            }
            .card {
                border-radius: 10px;
                overflow: hidden;
            }
        </style>
    @endsection
@endsection
@extends('components.app-main-layout')

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="fw-bold" style="color: #4B2E83;">
                    <i class="fas fa-cog me-2"></i> Calendar Setup
                </h1>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0 text-white">Configure Calendar</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('calendar.setup.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date', $setup->start_date ? $setup->start_date->format('Y-m-d') : '') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date', $setup->end_date ? $setup->end_date->format('Y-m-d') : '') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                    <select class="form-control" id="academic_year_id" name="academic_year_id" required>
                                        @foreach (AcademicYear::all() as $year)
                                            <option value="{{ $year->id }}" {{ ($setup && $setup->academic_year_id == $year->id) ? 'selected' : '' }}>{{ $year->year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Setup</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
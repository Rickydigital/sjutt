@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="animated fadeIn">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex align-items-center justify-content-between">
                    <h1 class="font-weight-bold" style="color: #4B2E83;">
                        <i class="fa fa-plus mr-2"></i> Create Examination Timetable
                    </h1>
                    <a href="{{ route('timetables.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('timetables.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="timetable_type" class="form-label">Timetable Type</label>
                            <select name="timetable_type" id="timetable_type" class="form-control" required>
                                <option value="Examination" selected>Examination</option>
                                <option value="Class">Class</option>
                                <option value="Special">Special</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="program" class="form-label">Program</label>
                            <input type="text" class="form-control" id="program" name="program" 
                                   value="{{ old('program') }}" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <input type="text" class="form-control" id="semester" name="semester" 
                                   value="{{ old('semester') }}" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="course_code" class="form-label">Course Code</label>
                            <input type="text" class="form-control" id="course_code" name="course_code" 
                                   value="{{ old('course_code') }}" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="faculty_id" class="form-label">Faculty</label>
                            <select name="faculty_id" id="faculty_id" class="form-control" required>
                                @foreach($faculties as $faculty)
                                    <option value="{{ $faculty->id }}" {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}>
                                        {{ $faculty->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="year_id" class="form-label">Year</label>
                            <select name="year_id" id="year_id" class="form-control" required>
                                @foreach($years as $year)
                                    <option value="{{ $year->id }}" {{ old('year_id') == $year->id ? 'selected' : '' }}>
                                        {{ $year->year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="exam_date" class="form-label">Exam Date</label>
                            <input type="date" class="form-control" id="exam_date" name="exam_date" 
                                   value="{{ old('exam_date') }}" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="venue_id" class="form-label">Venue</label>
                            <select name="venue_id" id="venue_id" class="form-control" required>
                                @foreach($venues as $venue)
                                    <option value="{{ $venue->id }}" {{ old('venue_id') == $venue->id ? 'selected' : '' }}>
                                        {{ $venue->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" 
                                   value="{{ old('start_time') }}" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" 
                                   value="{{ old('end_time') }}" required>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn" style="background-color: #4B2E83; color: white;">
                            <i class="fa fa-save mr-1"></i> Save Timetable
                        </button>
                        <a href="{{ route('timetables.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
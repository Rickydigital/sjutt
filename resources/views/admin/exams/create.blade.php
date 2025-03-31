@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h1 class="font-weight-bold" style="color: #4B2E83;">
                        <i class="fa fa-plus mr-2"></i> Create Examination Timetable
                    </h1>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('timetables.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="timetable_type">Timetable Type</label>
                                <input type="text" name="timetable_type" class="form-control" value="{{ old('timetable_type', 'Examination') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="program">Program</label>
                                <input type="text" name="program" class="form-control" value="{{ old('program') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="semester">Semester</label>
                                <input type="text" name="semester" class="form-control" value="{{ old('semester') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="course_code">Course Code</label>
                                <input type="text" name="course_code" class="form-control" value="{{ old('course_code') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="faculty">Faculty</label>
                                <input type="text" name="faculty" class="form-control" value="{{ old('faculty') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="year">Year</label>
                                <input type="number" name="year" class="form-control" min="1" max="4" value="{{ old('year') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="exam_date">Exam Date</label>
                                <input type="date" name="exam_date" class="form-control" value="{{ old('exam_date') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="start_time">Start Time</label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_time">End Time</label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="venue">Venue</label>
                                <input type="text" name="venue" class="form-control" value="{{ old('venue') }}" required>
                            </div>
                        </div>
                        <button type="submit" class="btn" style="background-color: #4B2E83; color: white; border-radius: 20px;">
                            <i class="fa fa-save mr-1"></i> Save
                        </button>
                        <a href="{{ route('timetables.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
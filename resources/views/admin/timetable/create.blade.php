@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <strong class="card-title" style="color: #4B2E83;">
                                <i class="fa fa-plus-circle mr-2"></i> Create New Timetable Entry
                            </strong>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('timetable.store') }}" method="POST">
                                @csrf
                                
                                <div class="form-group">
                                    <label for="day">Day</label>
                                    <select name="day" id="day" class="form-control" required>
                                        <option value="">Select Day</option>
                                        @foreach($days as $day)
                                            <option value="{{ $day }}" {{ old('day') == $day ? 'selected' : '' }}>
                                                {{ $day }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('day')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="faculty_id">Faculty</label>
                                    <select name="faculty_id" id="faculty_id" class="form-control" required>
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties as $faculty)
                                            <option value="{{ $faculty->id }}" {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}>
                                                {{ $faculty->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('faculty_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="year_id">Year</label>
                                    <select name="year_id" id="year_id" class="form-control" required>
                                        <option value="">Select Year</option>
                                        @foreach($years as $year)
                                            <option value="{{ $year->id }}" {{ old('year_id') == $year->id ? 'selected' : '' }}>
                                                {{ $year->year }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('year_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="time_start">Start Time</label>
                                            <input type="time" name="time_start" id="time_start" 
                                                   class="form-control" value="{{ old('time_start') }}" required>
                                            @error('time_start')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="time_end">End Time</label>
                                            <input type="time" name="time_end" id="time_end" 
                                                   class="form-control" value="{{ old('time_end') }}" required>
                                            @error('time_end')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="course_code">Course Code</label>
                                    <input type="text" name="course_code" id="course_code" 
                                           class="form-control" value="{{ old('course_code') }}" required>
                                    @error('course_code')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="activity">Activity</label>
                                    <input type="text" name="activity" id="activity" 
                                           class="form-control" value="{{ old('activity') }}" required>
                                    @error('activity')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="venue_id">Venue</label>
                                    <select name="venue_id" id="venue_id" class="form-control" required>
                                        <option value="">Select Venue</option>
                                        @foreach($venues as $venue)
                                            <option value="{{ $venue->id }}" {{ old('venue_id') == $venue->id ? 'selected' : '' }}>
                                                {{ $venue->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('venue_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn" style="background-color: #4B2E83; color: white;">
                                        <i class="fa fa-save mr-1"></i> Create Timetable Entry
                                    </button>
                                    <a href="{{ route('timetable.index') }}" class="btn btn-secondary">
                                        <i class="fa fa-times mr-1"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .form-control {
        border-color: #4B2E83;
    }
    .form-control:focus {
        border-color: #4B2E83;
        box-shadow: 0 0 5px rgba(75, 46, 131, 0.5);
    }
    .btn:hover {
        opacity: 0.85;
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }
</style>
@endsection
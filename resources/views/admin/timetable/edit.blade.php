@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <strong class="card-title" style="color: #4B2E83;">
                                <i class="fa fa-edit mr-2"></i> Edit Timetable Entry
                            </strong>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('timetable.update', $timetable->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="form-group">
                                    <label for="day">Day</label>
                                    <select name="day" id="day" class="form-control" required>
                                        <option value="">Select Day</option>
                                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                            <option value="{{ $day }}" {{ $timetable->day == $day ? 'selected' : '' }}>
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
                                            <option value="{{ $faculty->id }}" {{ $timetable->faculty_id == $faculty->id ? 'selected' : '' }}>
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
                                            <option value="{{ $year->id }}" {{ $timetable->year_id == $year->id ? 'selected' : '' }}>
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
                                                   class="form-control" value="{{ old('time_start', $timetable->time_start) }}" required>
                                            @error('time_start')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="time_end">End Time</label>
                                            <input type="time" name="time_end" id="time_end" 
                                                   class="form-control" value="{{ old('time_end', $timetable->time_end) }}" required>
                                            @error('time_end')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="course_code">Course Code</label>
                                    <input type="text" name="course_code" id="course_code" 
                                           class="form-control" value="{{ old('course_code', $timetable->course_code) }}" required>
                                    @error('course_code')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="activity">Activity</label>
                                    <input type="text" name="activity" id="activity" 
                                           class="form-control" value="{{ old('activity', $timetable->activity) }}" required>
                                    @error('activity')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="venue_id">Venue</label>
                                    <select name="venue_id" id="venue_id" class="form-control" required>
                                        <option value="">Select Venue</option>
                                        @foreach($venues as $venue)
                                            <option value="{{ $venue->id }}" {{ $timetable->venue_id == $venue->id ? 'selected' : '' }}>
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
                                        <i class="fa fa-save mr-1"></i> Update Timetable
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
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #4B2E83;
    }
</style>
@endsection
@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h1 class="font-weight-bold" style="color: #4B2E83;">
                        <i class="fa fa-edit mr-2"></i> Edit Timetable
                    </h1>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">Update Timetable Entry</strong>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('timetable.update', $timetable->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <label for="day">Day</label>
                                    <select name="day" id="day" class="form-control" style="border-color: #4B2E83;" required>
                                        <option value="Monday" {{ $timetable->day == 'Monday' ? 'selected' : '' }}>Monday</option>
                                        <option value="Tuesday" {{ $timetable->day == 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                                        <option value="Wednesday" {{ $timetable->day == 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                                        <option value="Thursday" {{ $timetable->day == 'Thursday' ? 'selected' : '' }}>Thursday</option>
                                        <option value="Friday" {{ $timetable->day == 'Friday' ? 'selected' : '' }}>Friday</option>
                                        <option value="Saturday" {{ $timetable->day == 'Saturday' ? 'selected' : '' }}>Saturday</option>
                                        <option value="Sunday" {{ $timetable->day == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                                    </select>
                                    @error('day')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="faculty">Faculty</label>
                                    <input type="text" name="faculty" id="faculty" class="form-control" 
                                        value="{{ old('faculty', $timetable->faculty) }}" style="border-color: #4B2E83;" required>
                                    @error('faculty')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="year">Year</label>
                                    <input type="text" name="year" id="year" class="form-control" 
                                        value="{{ old('year', $timetable->year) }}" style="border-color: #4B2E83;" required>
                                    @error('year')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="time_start">Start Time (e.g., 08:00 or 14:30)</label>
                                    <input type="time" name="time_start" id="time_start" class="form-control" 
                                        value="{{ old('time_start', $timetable->time_start) }}" style="border-color: #4B2E83;" required>
                                    @error('time_start')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="time_end">End Time (e.g., 09:00 or 15:30)</label>
                                    <input type="time" name="time_end" id="time_end" class="form-control" 
                                        value="{{ old('time_end', $timetable->time_end) }}" style="border-color: #4B2E83;" required>
                                    @error('time_end')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="course_code">Course Code</label>
                                    <input type="text" name="course_code" id="course_code" class="form-control" 
                                        value="{{ old('course_code', $timetable->course_code) }}" style="border-color: #4B2E83;" required>
                                    @error('course_code')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="activity">Activity</label>
                                    <input type="text" name="activity" id="activity" class="form-control" 
                                        value="{{ old('activity', $timetable->activity) }}" style="border-color: #4B2E83;" required>
                                    @error('activity')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="venue">Venue</label>
                                    <input type="text" name="venue" id="venue" class="form-control" 
                                        value="{{ old('venue', $timetable->venue) }}" style="border-color: #4B2E83;" required>
                                    @error('venue')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn" style="background-color: #4B2E83; color: white; border-radius: 20px;">
                                        <i class="fa fa-save mr-1"></i> Update Timetable
                                    </button>
                                    <a href="{{ route('timetable.index') }}" class="btn btn-secondary" style="border-radius: 20px;">
                                        <i class="fa fa-arrow-left mr-1"></i> Cancel
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

<style>
    .btn:hover { opacity: 0.85; transform: translateY(-1px); transition: all 0.2s ease; }
    .card { border: none; border-radius: 10px; overflow: hidden; }
    .form-control:focus { border-color: #4B2E83; box-shadow: 0 0 5px rgba(75, 46, 131, 0.5); }
</style>
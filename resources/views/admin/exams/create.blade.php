@extends('layouts.admin')

@section('content')
    <div class="content">
        <h1 class="font-weight-bold" style="color: #4B2E83;">
            <i class="fa fa-calendar mr-2"></i> Create Exam Timetable
        </h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('timetables.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="faculty_id">Faculty</label>
                        <select name="faculty_id" id="faculty_id" class="form-control" required>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}" {{ $facultyId == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="year_id">Year</label>
                        <select name="year_id" id="year_id" class="form-control" required>
                            @foreach ($years as $year)
                                <option value="{{ $year->id }}">{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="course_code">Course Code</label>
                        <select name="course_code" id="course_code" class="form-control select2" required>
                            @foreach ($courses as $course)
                                <option value="{{ $course->course_code }}">{{ $course->name }} ({{ $course->course_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="exam_date">Exam Date</label>
                        <select name="exam_date" id="exam_date" class="form-control" required>
                            @foreach ($dates as $examDate)
                                <option value="{{ $examDate }}" {{ $date == $examDate ? 'selected' : '' }}>{{ \Carbon\Carbon::parse($examDate)->format('M d, Y') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="start_time">Start Time</label>
                        <select name="start_time" id="start_time" class="form-control" required>
                            @foreach ($timeSlots as $slot)
                                <option value="{{ $slot['start_time'] }}" {{ $timeSlot && $timeSlot['start_time'] == $slot['start_time'] ? 'selected' : '' }}>{{ $slot['start_time'] }} ({{ $slot['name'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="end_time">End Time</label>
                        <select name="end_time" id="end_time" class="form-control" required>
                            @foreach ($timeSlots as $slot)
                                <option value="{{ $slot['end_time'] }}" {{ $timeSlot && $timeSlot['end_time'] == $slot['end_time'] ? 'selected' : '' }}>{{ $slot['end_time'] }} ({{ $slot['name'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="venue_id">Venue</label>
                        <select name="venue_id" id="venue_id" class="form-control" required>
                            @foreach ($venues as $venue)
                                <option value="{{ $venue->id }}">{{ $venue->name }} (Capacity: {{ $venue->capacity }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="group_selection">Group Selection</label>
                        <select name="group_selection[]" id="group_selection" class="form-control select2" multiple required>
                            <!-- Populated via AJAX based on faculty_id and year_id -->
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="lecturer_ids">Lecturers</label>
                        <select name="lecturer_ids[]" id="lecturer_ids" class="form-control select2" multiple required>
                            <!-- Populated via AJAX based on course_code -->
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="background-color: #4B2E83; border-color: #4B2E83;">
                        Create Timetable
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            $('#faculty_id, #year_id').change(function() {
                const facultyId = $('#faculty_id').val();
                const yearId = $('#year_id').val();
                if (facultyId && yearId) {
                    $.get('{{ route('timetables.getFacultyGroups') }}', { faculty_id: facultyId, year_id: yearId }, function(data) {
                        $('#group_selection').empty().append('<option value="All Groups">All Groups</option>');
                        data.groups.forEach(group => {
                            $('#group_selection').append(`<option value="${group.group_name}">${group.group_name} (${group.student_count} students)</option>`);
                        });
                    });
                }
            });

            $('#course_code').change(function() {
                const courseCode = $(this).val();
                if (courseCode) {
                    $.get('{{ route('timetables.getCourseLecturers') }}', { course_code: courseCode }, function(data) {
                        $('#lecturer_ids').empty();
                        data.lecturers.forEach(lecturer => {
                            $('#lecturer_ids').append(`<option value="${lecturer.id}">${lecturer.name}</option>`);
                        });
                    });
                }
            });

            // Trigger initial population if pre-filled
            if ($('#faculty_id').val() && $('#year_id').val()) {
                $('#faculty_id, #year_id').trigger('change');
            }
            if ($('#course_code').val()) {
                $('#course_code').trigger('change');
            }
        });
    </script>
@endsection

<style>
    .card { border: none; border-radius: 10px; overflow: hidden; }
    .form-control:focus { border-color: #4B2E83; box-shadow: 0 0 5px rgba(75, 46, 131, 0.5); }
</style>
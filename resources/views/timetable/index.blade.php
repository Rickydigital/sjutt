
@extends('components.app-main-layout')

@section('content')
    <style>
        tr {
            min-height: 100px !important;
        }
    </style>
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h1 class="font-weight-bold" style="color: #4B2E83;">
                <i class="fa fa-clock mr-2"></i> Timetable
            </h1>
            <div>
                <a href="#" class="btn btn-primary mr-2" data-toggle="modal" data-target="#importTimetableModal">
                    <i class="fa fa-upload mr-1"></i> Import
                </a>
                <a href="{{ route('timetable.export') }}" class="btn btn-success">
                    <i class="fa fa-download mr-1"></i> Export
                </a>
            </div>
        </div>
    </div>

    <!-- Faculty Filter -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form method="GET" action="{{ route('timetable.index') }}">
                <div class="form-group">
                    <label for="faculty">Select Faculty</label>
                    <select name="faculty" id="faculty" class="form-control" onchange="this.form.submit()">
                        <option value="">Select a Faculty</option>
                        @foreach ($faculties as $id => $name)
                            <option value="{{ $id }}" {{ $facultyId == $id ? 'selected' : '' }}>
                                {{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Timetable Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong class="card-title">
                        {{ $facultyId ? $faculties[$facultyId] : 'Timetable Overview' }}
                    </strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Time</th>
                                    @foreach ($days as $day)
                                        <th>{{ $day }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $activitiesByDay = $timetables->groupBy('day')->map(function ($group) {
                                        return $group->sortBy('time_start');
                                    });
                                    $occupiedUntil = array_fill_keys($days, -1);
                                @endphp
                                @foreach ($timeSlots as $i => $slotStart)
                                    <tr>
                                        <td>{{ $slotStart }}-{{ date('H:i', strtotime($slotStart) + 3600) }}</td>
                                        @foreach ($days as $day)
                                            @if ($i > $occupiedUntil[$day])
                                                @php
                                                    $activitiesForDay = $activitiesByDay->get($day, collect());
                                                    $activity = $activitiesForDay->first(function ($act) use (
                                                        $slotStart,
                                                    ) {
                                                        return substr($act->time_start, 0, 5) == $slotStart;
                                                    });
                                                @endphp
                                                @if ($activity)
                                                    @php
                                                        $startTime = strtotime($activity->time_start);
                                                        $endTime = strtotime($activity->time_end);
                                                        $duration = ($endTime - $startTime) / 3600;
                                                        $rowspan = ceil($duration);
                                                        $occupiedUntil[$day] = $i + $rowspan - 1;
                                                    @endphp
                                                    <td rowspan="{{ $rowspan }}" class="course-cell">
                                                        <p class=" text-center"><strong>{{ $activity->course_code }}
                                                            </strong></p>
                                                        <p class=" text-center">{{ $activity->activity }} </p>
                                                        <p class=" text-center">{{ $activity->group_selection }} </p>
                                                        <p class=" text-center">{{ $activity->venue->name }} </p>
                                                        <div class="timetable-icons d-flex flex-row justify-content-center">
                                                            <a href="#" class="action-icon" data-bs-toggle="tooltip"
                                                                data-bs-placement="top" data-id="{{ $activity->id }}"
                                                                title="Show Details">
                                                                <i class="bi bi-eye-fill text-primary"></i>
                                                            </a>
                                                            <a href="{{ route('timetable.edit', $activity->id) }}"
                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                class="action-icon" title="Edit">
                                                                <i class="bi bi-pencil-square text-primary"></i>
                                                            </a>
                                                            <form action="{{ route('timetable.destroy', $activity->id) }}"
                                                                method="POST" style="display:inline;"
                                                                onsubmit="return confirm('Are you sure you want to delete this timetable?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top" class="action-icon"
                                                                    title="Delete">
                                                                    <i class="bi bi-trash text-danger"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                @else
                                                    <td class="add-timetable empty-cell" data-day="{{ $day }}"
                                                        data-time="{{ $slotStart }}"
                                                        data-faculty="{{ $facultyId }}">
                                                        <i class="bi bi-plus" style="margin-left: 40%"></i>
                                                    </td>
                                                @endif
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Timetable Modal -->
    <div class="modal fade" id="addTimetableModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Timetable Entry</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="addTimetableForm" method="POST" action="{{ route('timetable.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="faculty_id" id="modal_faculty_id">
                        <div class="form-group">
                            <label for="modal_day">Day</label>
                            <input type="text" name="day" id="modal_day" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="modal_time_start">Start Time</label>
                            <input type="text" name="time_start" id="modal_time_start" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="modal_time_end">End Time <span class="text-danger">*</span></label>
                            <select name="time_end" id="modal_time_end" class="form-control" required>
                                <!-- Populated dynamically via JS -->
                            </select>
                            @error('time_end')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class=" form-group d-flex flex-column ">
                            <label for="modal_course_code">Course Code <span class="text-danger">*</span></label>
                            <select name="course_code" id="modal_course_code" class="select2 form-control " required>
                                <option value="">Select Course Code</option>
                                <!-- Populated via AJAX -->
                            </select>
                            @error('course_code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group d-flex flex-column ">
                            <label for="modal_lecturer_id">Lecturer <span class="text-danger">*</span></label>
                            <select name="lecturer_id" id="modal_lecturer_id" class="form-control select2" required>
                                <option value="">Select Lecturer</option>
                                <!-- Populated via AJAX -->
                            </select>
                            @error('lecturer_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="modal_activity">Activity <span class="text-danger">*</span></label>
                            <input type="text" name="activity" id="modal_activity" class="form-control"
                                value="Lecture" required>
                            @error('activity')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group d-flex flex-column ">
                            <label for="modal_venue_id">Venue <span class="text-danger">*</span></label>
                            <select name="venue_id" id="modal_venue_id" class="form-control select2" required>
                                @foreach ($venues as $venue)
                                    <option value="{{ $venue->id }}" data-capacity="{{ $venue->capacity }}">
                                        {{ $venue->name }} (Capacity: {{ $venue->capacity }})</option>
                                @endforeach
                            </select>
                            @error('venue_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group d-flex flex-column">
                            <label for="modal_group_selection">Group Selection <span class="text-danger">*</span></label>
                            <select name="group_selection[]" id="modal_group_selection" class="select2" multiple
                                required>
                                <!-- Populated via AJAX -->
                            </select>
                            @error('group_selection')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Show Timetable Modal -->
    <div class="modal fade" id="showTimetableModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #4B2E83; color: white;">
                    <h5 class="modal-title">Timetable Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Course Code:</strong> <span id="show_course_code"></span></p>
                    <p><strong>Course Name:</strong> <span id="show_course_name"></span></p>
                    <p><strong>Activity:</strong> <span id="show_activity"></span></p>
                    <p><strong>Day:</strong> <span id="show_day"></span></p>
                    <p><strong>Time:</strong> <span id="show_time_start"></span> - <span id="show_time_end"></span></p>
                    <p><strong>Venue:</strong> <span id="show_venue"></span> (Capacity: <span
                            id="show_venue_capacity"></span>)</p>
                    <p><strong>Groups:</strong> <span id="show_group_details"></span></p>
                    <p><strong>Lecturer:</strong> <span id="show_lecturer"></span></p>
                    <p><strong>Faculty:</strong> <span id="show_faculty"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Timetable Modal -->
    <div class="modal fade" id="importTimetableModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #4B2E83; color: white;">
                    <h5 class="modal-title">Import Timetable</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="importTimetableForm" method="POST" action="{{ route('timetable.import') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="import_file">Upload Excel File <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="import_file" class="form-control-file"
                                accept=".xlsx,.xls,.csv" required>
                            @error('file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            // $('.select2').select2({
            //     placeholder: 'Select an option',
            //     allowClear: true,
            //     width: '100%'
            // });

            // Handle "+" click to open add modal
            $(document).on('click', '.add-timetable', function(e) {
                e.preventDefault();
                console.log('Add timetable clicked');
                var facultyId = $(this).data('faculty');
                console.log('Faculty ID:', facultyId);
                if (!facultyId) {
                    alert('Please select a faculty first.');
                    return;
                }
                var day = $(this).data('day');
                var timeStart = $(this).data('time');
                console.log('Day:', day, 'Time Start:', timeStart);

                // Populate modal fields
                $('#modal_faculty_id').val(facultyId);
                $('#modal_day').val(day);
                $('#modal_time_start').val(timeStart);

                // Populate end time options
                var timeSlots = @json($timeSlots);
                var startIndex = timeSlots.indexOf(timeStart);
                var endOptions = timeSlots.slice(startIndex + 1);
                $('#modal_time_end').empty().append('<option value="">Select End Time</option>');
                endOptions.forEach(function(time) {
                    $('#modal_time_end').append(new Option(time, time));
                });

                // Fetch courses
                $.ajax({
                    url: '{{ route('timetables.getCourses') }}',
                    method: 'GET',
                    data: {
                        faculty_id: facultyId
                    },
                    success: function(response) {
                        $('#modal_course_code').empty().append(
                            '<option value="">Select Course Code</option>');
                        response.course_codes.forEach(function(course) {
                            $('#modal_course_code').append(new Option(course
                                .course_code, course.course_code));
                        });
                        $('#modal_course_code').trigger('change');
                    },
                    error: function(xhr) {
                        console.error('Error fetching courses:', xhr.responseText);
                    }
                });

                // Fetch groups
                $.ajax({
                    url: '{{ route('timetables.getGroups') }}',
                    method: 'GET',
                    data: {
                        faculty_id: facultyId
                    },
                    success: function(response) {
                        $('#modal_group_selection').empty();
                        response.groups.forEach(function(group) {
                            $('#modal_group_selection').append(new Option(group
                                .group_name, group.group_name));
                        });
                        $('#modal_group_selection').trigger('change');
                    },
                    error: function(xhr) {
                        console.error('Error fetching groups:', xhr.responseText);
                    }
                });

                // Clear lecturer field
                $('#modal_lecturer_id').empty().append('<option value="">Select Lecturer</option>').trigger(
                    'change');

                // Show the modal
                $('#addTimetableModal').modal('show');
            });

            // Handle course code change to fetch lecturers
            $('#modal_course_code').on('change', function() {
                var courseCode = $(this).val();
                var $lecturerSelect = $('#modal_lecturer_id');
                $lecturerSelect.empty().append('<option value="">Select Lecturer</option>').trigger(
                    'change');

                if (!courseCode) {
                    $lecturerSelect.prop('disabled', true);
                    return;
                }

                $.ajax({
                    url: '{{ route('timetables.getLecturers') }}',
                    method: 'GET',
                    data: {
                        course_code: courseCode
                    },
                    success: function(response) {
                        $lecturerSelect.empty().append(
                            '<option value="">Select Lecturer</option>');
                        response.lecturers.forEach(function(lecturer) {
                            $lecturerSelect.append(new Option(lecturer.name, lecturer
                                .id));
                        });
                        $lecturerSelect.prop('disabled', response.lecturers.length === 0);
                    },
                    error: function(xhr) {
                        console.error('Error fetching lecturers:', xhr.responseText);
                    }
                });
            });

            // Validate venue capacity on form submission
            $('#addTimetableForm').on('submit', function(e) {
                var venueId = $('#modal_venue_id').val();
                var venueCapacity = parseInt($('#modal_venue_id option:selected').data('capacity')) || 0;
                var groups = $('#modal_group_selection').val();
                if (groups.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one group.');
                    return false;
                }

                $.ajax({
                    url: '{{ route('timetables.getStudentCount') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        faculty_id: $('#modal_faculty_id').val(),
                        groups: groups
                    },
                    async: false,
                    success: function(response) {
                        var studentCount = response.student_count;
                        if (studentCount > venueCapacity) {
                            e.preventDefault();
                            alert(
                                `Venue capacity (${venueCapacity}) is insufficient for selected groups (${studentCount} students).`
                            );
                        }
                    },
                    error: function(xhr) {
                        e.preventDefault();
                        console.error('Error fetching student count:', xhr.responseText);
                        alert('Error validating capacity. Please try again.');
                    }
                });
            });

            // Handle show timetable click
            $(document).on('click', '.show-timetable', function(e) {
                e.preventDefault();
                var timetableId = $(this).data('id');
                $.ajax({
                    url: '{{ route('timetable.show', ':id') }}'.replace(':id', timetableId),
                    method: 'GET',
                    success: function(data) {
                        $('#show_course_code').text(data.course_code);
                        $('#show_course_name').text(data.course_name || 'N/A');
                        $('#show_activity').text(data.activity);
                        $('#show_day').text(data.day);
                        $('#show_time_start').text(data.time_start);
                        $('#show_time_end').text(data.time_end);
                        $('#show_venue').text(data.venue?.name || 'N/A');
                        $('#show_venue_capacity').text(data.venue?.capacity || 'N/A');
                        $('#show_group_details').text(data.group_details || 'N/A');
                        $('#show_lecturer').text(data.lecturer?.name || 'N/A');
                        $('#show_faculty').text(data.faculty?.name || 'N/A');
                        $('#showTimetableModal').modal('show');
                    },
                    error: function(xhr) {
                        console.error('Error fetching timetable:', xhr.responseText);
                        alert('Failed to load timetable details.');
                    }
                });
            });
        });
    </script>
@endsection

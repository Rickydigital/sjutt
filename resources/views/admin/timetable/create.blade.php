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
                            <form action="{{ route('timetable.store') }}" method="POST" id="timetable-form">
                                @csrf
                                <div class="form-group">
                                    <label for="day">Day <span class="text-danger">*</span></label>
                                    <select name="day" id="day" class="form-control select2" required>
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
                                    <label for="program_id">Program <span class="text-danger">*</span></label>
                                    <select name="program_id" id="program_id" class="form-control select2" required>
                                        <option value="">Select Program</option>
                                        @foreach($programs as $program)
                                            <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>
                                                {{ $program->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('program_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="faculty_id">Faculty <span class="text-danger">*</span></label>
                                    <select name="faculty_id" id="faculty_id" class="form-control select2" required>
                                        <option value="">Select Faculty</option>
                                        <!-- Populated via AJAX -->
                                    </select>
                                    @error('faculty_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="time_start">Start Time <span class="text-danger">*</span></label>
                                    <select name="time_start" id="time_start" class="form-control select2" required>
                                        <option value="">Select Start Time</option>
                                        @php
                                            $start = strtotime('08:00');
                                            $end = strtotime('21:00');
                                            while ($start <= $end) {
                                                $time = date('H:i', $start);
                                                $selected = old('time_start', '08:00') == $time ? 'selected' : '';
                                                echo "<option value='$time' $selected>$time</option>";
                                                $start = strtotime('+30 minutes', $start);
                                            }
                                        @endphp
                                    </select>
                                    @error('time_start')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="time_end">End Time <span class="text-danger">*</span></label>
                                    <select name="time_end" id="time_end" class="form-control select2" required>
                                        <option value="">Select End Time</option>
                                        @php
                                            $start = strtotime('08:00');
                                            $end = strtotime('21:00');
                                            while ($start <= $end) {
                                                $time = date('H:i', $start);
                                                $selected = old('time_end', '10:00') == $time ? 'selected' : '';
                                                echo "<option value='$time' $selected>$time</option>";
                                                $start = strtotime('+30 minutes', $start);
                                            }
                                        @endphp
                                    </select>
                                    @error('time_end')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="course_code">Course Code <span class="text-danger">*</span></label>
                                    <select name="course_code" id="course_code" class="form-control select2" required>
                                        <option value="">Select Course Code</option>
                                        <!-- Populated via AJAX -->
                                    </select>
                                    @error('course_code')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="lecturer_id">Lecturer</label>
                                    <select name="lecturer_id" id="lecturer_id" class="form-control select2">
                                        <option value="">Select Lecturer</option>
                                        <!-- Populated via AJAX -->
                                    </select>
                                    @error('lecturer_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group" id="group_selection_container" style="display: none;">
                                    <label for="group_selection">Group Selection <span class="text-danger">*</span></label>
                                    <select name="group_selection" id="group_selection" class="form-control select2" multiple required>
                                        <!-- Populated via AJAX -->
                                    </select>
                                    @error('group_selection')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="activity">Activity <span class="text-danger">*</span></label>
                                    <input type="text" name="activity" id="activity" class="form-control" value="{{ old('activity', 'Lecture') }}" required>
                                    @error('activity')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="venue_id">Venue <span class="text-danger">*</span></label>
                                    <select name="venue_id" id="venue_id" class="form-control select2" required>
                                        <option value="">Select Venue</option>
                                        @foreach($venues as $venue)
                                            <option value="{{ $venue->id }}" {{ old('venue_id') == $venue->id ? 'selected' : '' }}>
                                                {{ $venue->name }} (Capacity: {{ $venue->capacity }})
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .form-control, .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            border-color: #4B2E83;
        }
        .form-control:focus, .select2-container--default .select2-selection--single:focus,
        .select2-container--default .select2-selection--multiple:focus {
            border-color: #4B2E83;
            box-shadow: 0 0 5px rgba(75, 46, 131, 0.5);
        }
        .btn:hover {
            opacity: 0.85;
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
        .select2-container {
            width: 100% !important;
        }
        .text-danger {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script>
        (function($j) {
            // Initialize Select2
            $j('.select2').select2({
                placeholder: function() {
                    return $j(this).data('placeholder') || 'Select an option';
                },
                allowClear: true,
                width: '100%'
            });

            // Update end time options based on start time
            $j('#time_start').on('change', function() {
                const startTime = $j(this).val();
                const $endTimeSelect = $j('#time_end');
                const endTime = $endTimeSelect.val();

                // Store current end time to preserve it if valid
                const currentEndTime = endTime || '10:00';

                // Get all end time options
                const $endOptions = $endTimeSelect.find('option').filter(function() {
                    return this.value !== '';
                });

                // Enable all options initially
                $endOptions.prop('disabled', false);

                // Disable options that are not after start time
                if (startTime) {
                    $endOptions.each(function() {
                        if (this.value <= startTime) {
                            $j(this).prop('disabled', true);
                        }
                    });
                }

                // Rebuild Select2 to reflect disabled options
                $endTimeSelect.val(null).trigger('change');

                // Set end time: preserve if valid, else set to 2 hours after start
                if (startTime && (!endTime || endTime <= startTime)) {
                    const startMoment = moment(startTime, 'HH:mm');
                    const newEndTime = startMoment.add(2, 'hours').format('HH:mm');
                    // Ensure newEndTime exists in options
                    if ($endTimeSelect.find(`option[value="${newEndTime}"]`).length) {
                        $endTimeSelect.val(newEndTime).trigger('change');
                    } else {
                        // Fallback to next available time
                        const nextTime = $endOptions.filter(function() {
                            return this.value > startTime && !this.disabled;
                        }).first().val();
                        $endTimeSelect.val(nextTime || currentEndTime).trigger('change');
                    }
                } else {
                    $endTimeSelect.val(currentEndTime).trigger('change');
                }
            });

            // Set up CSRF token
            $j.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $j('meta[name="csrf-token"]').attr('content')
                }
            });

            // Program change: Update faculty dropdown
            $j('#program_id').on('change', function() {
                const programId = $j(this).val();
                const $facultySelect = $j('#faculty_id');
                const $courseSelect = $j('#course_code');
                const $groupSelect = $j('#group_selection');
                const $groupContainer = $j('#group_selection_container');
                const $lecturerSelect = $j('#lecturer_id');

                // Clear dependent fields
                $facultySelect.empty().append('<option value="">Select Faculty</option>').trigger('change');
                $courseSelect.empty().append('<option value="">Select Course Code</option>').trigger('change');
                $groupSelect.empty().trigger('change');
                $groupContainer.hide();
                $lecturerSelect.empty().append('<option value="">Select Lecturer</option>').trigger('change');

                if (!programId) {
                    $facultySelect.prop('disabled', true);
                    return;
                }

                // Fetch faculties for program
                $j.ajax({
                    url: '{{ route("timetables.getFacultiesByProgram") }}',
                    method: 'GET',
                    data: { program_id: programId },
                    success: function(response) {
                        $facultySelect.empty().append('<option value="">Select Faculty</option>');
                        $j.each(response.faculties, function(id, name) {
                            $facultySelect.append(new Option(name, id));
                        });
                        $facultySelect.prop('disabled', false).trigger('change');
                    },
                    error: function(xhr) {
                        console.error('Error fetching faculties:', xhr.responseText);
                        $facultySelect.append('<option value="" disabled>Error loading faculties</option>');
                    }
                });
            });

            // Faculty change: Update courses, groups
            $j('#faculty_id').on('change', function() {
                const facultyId = $j(this).val();
                const $courseSelect = $j('#course_code');
                const $groupSelect = $j('#group_selection');
                const $groupContainer = $j('#group_selection_container');
                const $lecturerSelect = $j('#lecturer_id');

                // Clear dependent fields
                $courseSelect.empty().append('<option value="">Select Course Code</option>').trigger('change');
                $groupSelect.empty().trigger('change');
                $lecturerSelect.empty().append('<option value="">Select Lecturer</option>').trigger('change');
                $groupContainer.hide();

                if (!facultyId) {
                    return;
                }

                // Fetch courses
                $j.ajax({
                    url: '{{ route("timetables.getCourses") }}',
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: function(response) {
                        $courseSelect.empty().append('<option value="">Select Course Code</option>');
                        response.course_codes.forEach(function(code) {
                            $courseSelect.append(new Option(code, code));
                        });
                        $courseSelect.prop('disabled', false).trigger('change');
                    },
                    error: function(xhr) {
                        console.error('Error fetching courses:', xhr.responseText);
                        $courseSelect.append('<option value="" disabled>Error loading courses</option>');
                    }
                });

                // Fetch groups
                $j.ajax({
                    url: '{{ route("timetables.getGroups") }}',
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: function(response) {
                        $groupSelect.empty();
                        if (response.groups.length > 1) {
                            response.groups.forEach(function(group) {
                                $groupSelect.append(new Option(group, group));
                            });
                            $groupContainer.show();
                            $groupSelect.prop('required', true);
                        } else {
                            $groupContainer.hide();
                            $groupSelect.prop('required', false);
                            $groupSelect.append(new Option('All Groups', 'All Groups', true, true));
                        }
                        $groupSelect.trigger('change');
                    },
                    error: function(xhr) {
                        console.error('Error fetching groups:', xhr.responseText);
                        $groupContainer.hide();
                    }
                });
            });

            // Course change: Update lecturers
            $j('#course_code').on('change', function() {
                const courseCode = $j(this).val();
                const $lecturerSelect = $j('#lecturer_id');

                $lecturerSelect.empty().append('<option value="">Select Lecturer</option>').trigger('change');

                if (!courseCode) {
                    $lecturerSelect.prop('disabled', true);
                    return;
                }

                $j.ajax({
                    url: '{{ route("timetables.getLecturers") }}',
                    method: 'GET',
                    data: { course_code: courseCode },
                    success: function(response) {
                        $lecturerSelect.empty().append('<option value="">Select Lecturer</option>');
                        response.lecturers.forEach(function(lecturer) {
                            $lecturerSelect.append(new Option(lecturer.name, lecturer.id));
                        });
                        $lecturerSelect.prop('disabled', response.lecturers.length <= 0);
                        if (response.lecturers.length === 1) {
                            $lecturerSelect.val(response.lecturers[0].id).trigger('change');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching lecturers:', xhr.responseText);
                        $lecturerSelect.append('<option value="" disabled>Error loading lecturers</option>');
                    }
                });
            });

            // Form submission validation
            $j('#timetable-form').on('submit', function(e) {
                const timeStart = $j('#time_start').val();
                const timeEnd = $j('#time_end').val();

                // Validate time_end is after time_start
                if (timeStart && timeEnd && timeEnd <= timeStart) {
                    e.preventDefault();
                    alert('End time must be after start time.');
                    $j('#time_end').focus();
                    return false;
                }
            });

            // Initialize on page load
            if ($j('#program_id').val()) {
                $j('#program_id').trigger('change');
            }

            // Trigger time_start change to initialize end time options
            $j('#time_start').trigger('change');
        })(jQuery);
    </script>
@endsection
@extends('components.app-main-layout')

@section('content')
    <div class="col-md-8 offset-md-2">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong class="card-title">Create Faculty</strong>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                @endif
                <form action="{{ route('faculties.store') }}" method="POST" id="facultyForm">
                    @csrf
                    <div class="form-group">
                        <label for="program_id">Program <span class="text-danger">*</span></label>
                        <select name="program_id" id="program_id" class="form-control select2" required>
                            <option value="">Select Program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}"
                                    {{ old('program_id') == $program->id ? 'selected' : '' }}>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('program_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="name">Faculty Name <span class="text-danger">*</span></label>
                        <select name="name" id="name" class="form-control select2" required>
                            <option value="">Select Faculty Name</option>
                            <!-- Populated via AJAX -->
                        </select>
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="total_students_no">Total Students <span class="text-danger">*</span></label>
                        <input type="number" name="total_students_no" id="total_students_no" class="form-control"
                            value="{{ old('total_students_no') }}" min="0" required>
                        @error('total_students_no')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4" placeholder="Faculty description">{{ old('description') }}</textarea>
                        @error('description')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="course_ids">Courses</label>
                        <div class="input-group">
                            <select name="course_ids[]" id="course_ids" class="form-control select2" multiple>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}"
                                        {{ in_array($course->id, old('course_ids', session('new_course_id') ? [session('new_course_id')] : [])) ? 'selected' : '' }}>
                                        {{ $course->name }} <b>({{ $course->course_code }})</b>
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group my-2">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#addCourseModal">
                                    <i class="bi bi-plus" style="font-size: 20px"></i> Add Course
                                </button>
                            </div>
                        </div>
                        @error('course_ids')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="group_names">Group Names (comma-separated)</label>
                        <input type="text" name="group_names" id="group_names" class="form-control"
                            value="{{ old('group_names') }}" placeholder="e.g., Group A, Group B">
                        @error('group_names')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class=" d-flex flex-row justify-content-end my-2">
                        <a href="{{ route('faculties.index') }}" class="btn btn-outline-danger">Cancel</a>
                        <button type="submit" class="btn btn-primary mx-2">Save
                            Faculty</button>
                    </div>
                </form>

                <!-- Add Course Modal -->
                <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addCourseModalLabel">Add New Course</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form action="{{ route('faculties.storeCourse') }}" method="POST" id="addCourseForm">
                                @csrf
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="course_code">Course Code <span class="text-danger">*</span></label>
                                        <input type="text" name="course_code" id="course_code" class="form-control"
                                            value="{{ old('course_code') }}" required>
                                        @error('course_code')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="course_name">Course Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="course_name" class="form-control"
                                            value="{{ old('name') }}" required>
                                        @error('name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="course_description">Description</label>
                                        <textarea name="description" id="course_description" class="form-control" rows="4">{{ old('description') }}</textarea>
                                        @error('description')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="credits">Credits <span class="text-danger">*</span></label>
                                        <input type="number" name="credits" id="credits" class="form-control"
                                            value="{{ old('credits') }}" min="1" required>
                                        @error('credits')
                                            NSW <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="lecturer_ids">Lecturers</label>
                                        <select name="lecturer_ids[]" id="lecturer_ids" class="form-control select2"
                                            multiple>
                                            @foreach ($lecturers as $lecturer)
                                                <option value="{{ $lecturer->id }}"
                                                    {{ in_array($lecturer->id, old('lecturer_ids', [])) ? 'selected' : '' }}>
                                                    {{ $lecturer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('lecturer_ids')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn"
                                        style="background-color: #4B2E83; color: white;">Save Course</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Set up CSRF token for AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Faculty Name Dropdown
            $('#program_id').on('change', function() {
                const programId = $(this).val();
                const $nameSelect = $('#name');
                console.log('Program changed, program_id:', programId);

                $nameSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>')
                    .trigger('change.select2');

                if (programId) {
                    $.ajax({
                        url: '{{ route('faculties.getFacultyNames') }}',
                        method: 'GET',
                        data: {
                            program_id: programId
                        },
                        success: function(response) {
                            console.log('AJAX Response:', response);
                            $nameSelect.empty().append(
                                '<option value="">Select Faculty Name</option>');

                            if (response.faculty_names && Array.isArray(response
                                    .faculty_names) && response.faculty_names.length > 0) {
                                response.faculty_names.forEach(function(name) {
                                    $nameSelect.append(new Option(name, name));
                                });
                            } else {
                                const errorMsg = response.error || 'No available faculty names';
                                $nameSelect.append('<option value="" disabled>' + errorMsg +
                                    '</option>');
                            }

                            $nameSelect.prop('disabled', false).trigger('change.select2');
                        },
                        error: function(xhr) {
                            console.error('AJAX Error:', xhr.status, xhr.responseText);
                            $nameSelect.empty()
                                .append('<option value="">Select Faculty Name</option>')
                                .append(
                                    '<option value="" disabled>Error loading names</option>')
                                .prop('disabled', false)
                                .trigger('change.select2');
                        }
                    });
                } else {
                    $nameSelect.empty()
                        .append('<option value="">Select Faculty Name</option>')
                        .prop('disabled', true)
                        .trigger('change.select2');
                }
            });

            // Trigger change if program_id is pre-selected
            const initialProgramId = $('#program_id').val();
            if (initialProgramId) {
                console.log('Triggering initial change for program_id:', initialProgramId);
                $('#program_id').trigger('change');
            }

            // Handle new course addition
            @if (session('new_course_id') && session('success') === 'Course created successfully.')
                const newCourseId = '{{ session('new_course_id') }}';
                const newCourseName = $('select#course_ids option[value="' + newCourseId + '"]').text() ||
                    'New Course';
                console.log('New course added, id:', newCourseId, 'name:', newCourseName);
                if (!$('select#course_ids option[value="' + newCourseId + '"]').length) {
                    $('select#course_ids').append(new Option(newCourseName, newCourseId, true, true));
                }
                $('select#course_ids').trigger('change.select2');
            @endif
        });
    </script>
@endsection

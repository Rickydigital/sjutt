@extends('components.app-main-layout')

@section('content')
<div class="col-md-8 offset-md-2">
    <div class="card shadow-sm">
        <div class="card-header">
            <strong class="card-title">Create Course</strong>
        </div>

        <div class="card-body">
            <form action="{{ route('courses.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label>Course Code <span class="text-danger">*</span></label>
                    <input type="text" name="course_code" class="form-control" value="{{ old('course_code') }}" required>
                    @error('course_code') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    <small class="text-muted">Maximum 5 words</small>
                    @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="5">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Credits <span class="text-danger">*</span></label>
                    <input type="number" name="credits" class="form-control" value="{{ old('credits') }}" min="1" required>
                </div>

                <div class="form-group">
                    <label>Hours <span class="text-danger">*</span></label>
                    <input type="number" name="hours" class="form-control" value="{{ old('hours') }}" min="1" required>
                </div>

                <div class="form-group">
                    <label>Practical Hours</label>
                    <input type="number" name="practical_hrs" class="form-control" value="{{ old('practical_hrs') }}" min="0">
                </div>

                <div class="form-group">
                    <label>Session <span class="text-danger">*</span></label>
                    <input type="number" name="session" class="form-control" value="{{ old('session') }}" min="1" required>
                </div>

                <div class="form-group">
                    <label>Semester <span class="text-danger">*</span></label>
                    <select name="semester_id" id="semester_id" class="form-control select2" required>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected(old('semester_id') == $semester->id)>
                                {{ $semester->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="cross_catering" value="1" @checked(old('cross_catering'))>
                        Cross Catering
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_workshop" value="1" @checked(old('is_workshop'))>
                        Is Workshop
                    </label>
                </div>

                <div class="form-group">
                    <label>Lecturers <span class="text-danger">*</span></label>
                    <select name="lecturer_ids[]" id="lecturer_ids" class="form-control select2" multiple required>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}" @selected(in_array($lecturer->id, old('lecturer_ids', [])))>
                                {{ $lecturer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Faculties <span class="text-danger">*</span></label>
                    <select name="faculty_ids[]" id="faculty_ids" class="form-control select2" multiple required>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}"
                                    data-name="{{ $faculty->name }}"
                                    @selected(in_array($faculty->id, old('faculty_ids', [])))>
                                {{ $faculty->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="facultyStudentCountsBox" class="mt-3"></div>

                <div class="d-flex justify-content-end my-2">
                    <a href="{{ route('courses.index') }}" class="btn btn-outline-danger">Cancel</a>
                    <button type="submit" class="btn btn-primary mx-2">Save Course</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    const oldCounts = @json(old('student_counts', []));

    function renderFacultyStudentCounts() {
        const selected = $('#faculty_ids').select2('data');
        const box = $('#facultyStudentCountsBox');

        box.empty();

        if (!selected.length) return;

        box.append(`
            <div class="alert alert-info py-2">
                Enter student number for each selected faculty. Default is 0.
            </div>
        `);

        selected.forEach(item => {
            const id = item.id;
            const name = item.text.trim();
            const value = oldCounts[id] ?? 0;

            box.append(`
                <div class="form-group mb-2">
                    <label>${name} Students</label>
                    <input type="number"
                           name="student_counts[${id}]"
                           class="form-control"
                           min="0"
                           value="${value}">
                </div>
            `);
        });
    }

    $('#faculty_ids').on('change', renderFacultyStudentCounts);

    renderFacultyStudentCounts();
});
</script>
@endsection
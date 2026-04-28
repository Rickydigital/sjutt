@extends('components.app-main-layout')

@section('content')
@php
    $selectedFacultyIds = old('faculty_ids', $course->faculties->pluck('id')->toArray());

    $studentCounts = old('student_counts', $course->faculties
        ->mapWithKeys(fn ($faculty) => [
            $faculty->id => $faculty->pivot->student_count ?? 0
        ])
        ->toArray()
    );
@endphp

<div class="col-md-8 offset-md-2">
    <div class="card shadow-sm">
        <div class="card-header">
            <strong class="card-title">Edit Course</strong>
        </div>

        <div class="card-body">
            <form action="{{ route('courses.update', $course->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Course Code <span class="text-danger">*</span></label>
                    <input type="text" name="course_code" class="form-control"
                           value="{{ old('course_code', $course->course_code) }}" required>
                    @error('course_code') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $course->name) }}" required>
                    <small class="text-muted">Maximum 5 words</small>
                    @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="5">{{ old('description', $course->description) }}</textarea>
                    @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Credits <span class="text-danger">*</span></label>
                    <input type="number" name="credits" class="form-control"
                           value="{{ old('credits', $course->credits) }}" min="1" required>
                    @error('credits') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Hours <span class="text-danger">*</span></label>
                    <input type="number" name="hours" class="form-control"
                           value="{{ old('hours', $course->hours) }}" min="1" required>
                    @error('hours') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Practical Hours</label>
                    <input type="number" name="practical_hrs" class="form-control"
                           value="{{ old('practical_hrs', $course->practical_hrs) }}" min="0" step="1">
                    <small class="text-muted">Optional. Must not exceed total hours.</small>
                    @error('practical_hrs') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Session <span class="text-danger">*</span></label>
                    <input type="number" name="session" class="form-control"
                           value="{{ old('session', $course->session) }}" min="1" required>
                    @error('session') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Semester <span class="text-danger">*</span></label>
                    <select name="semester_id" id="semester_id" class="form-control select2" required>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}"
                                {{ old('semester_id', $course->semester_id) == $semester->id ? 'selected' : '' }}>
                                {{ $semester->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('semester_id') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" name="cross_catering" class="form-check-input" value="1"
                            {{ old('cross_catering', $course->cross_catering) ? 'checked' : '' }}>
                        Cross Catering
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" name="is_workshop" class="form-check-input" value="1"
                            {{ old('is_workshop', $course->is_workshop) ? 'checked' : '' }}>
                        Is Workshop
                    </label>
                </div>

                <div class="form-group">
                    <label>Lecturers <span class="text-danger">*</span></label>
                    <select name="lecturer_ids[]" id="lecturer_ids" class="form-control select2" multiple required>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}"
                                {{ $course->lecturers->contains($lecturer->id) || in_array($lecturer->id, old('lecturer_ids', [])) ? 'selected' : '' }}>
                                {{ $lecturer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('lecturer_ids') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Faculties <span class="text-danger">*</span></label>
                    <select name="faculty_ids[]" id="faculty_ids" class="form-control select2" multiple required>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}"
                                {{ in_array($faculty->id, $selectedFacultyIds) ? 'selected' : '' }}>
                                {{ $faculty->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('faculty_ids') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div id="facultyStudentCountsBox" class="mt-3"></div>

                <div class="d-flex flex-row justify-content-end my-2">
                    <a href="{{ route('courses.index') }}" class="btn btn-outline-danger">Cancel</a>
                    <button type="submit" class="btn btn-primary mx-2">Update</button>
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
    $('.select2').select2({
        width: '100%'
    });

    const studentCounts = @json($studentCounts);

    function renderFacultyStudentCounts() {
        const selected = $('#faculty_ids').select2('data');
        const box = $('#facultyStudentCountsBox');

        box.empty();

        if (!selected.length) {
            return;
        }

        box.append(`
            <div class="alert alert-info py-2">
                Update student number for each selected faculty.
            </div>
        `);

        selected.forEach(function (item) {
            const id = item.id;
            const name = item.text.trim();
            const value = studentCounts[id] ?? 0;

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
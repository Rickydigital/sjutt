@extends('components.app-main-layout')

@section('content')
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
                        <label for="course_code">Course Code <span class="text-danger">*</span></label>
                        <input type="text" name="course_code" id="course_code" class="form-control"
                            value="{{ old('course_code', $course->course_code) }}" required>
                        @error('course_code')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control"
                            value="{{ old('name', $course->name) }}" required>
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="5">{{ old('description', $course->description) }}</textarea>
                        @error('description')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="credits">Credits <span class="text-danger">*</span></label>
                        <input type="number" name="credits" id="credits" class="form-control"
                            value="{{ old('credits', $course->credits) }}" min="1" required>
                        @error('credits')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="lecturer_ids">Lecturers</label>
                        <select name="lecturer_ids[]" id="lecturer_ids" class="form-control select2" multiple>
                            @foreach ($lecturers as $lecturer)
                                <option value="{{ $lecturer->id }}"
                                    {{ $course->lecturers->contains($lecturer->id) || in_array($lecturer->id, old('lecturer_ids', [])) ? 'selected' : '' }}>
                                    {{ $lecturer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('lecturer_ids')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="faculty_ids">Faculties</label>
                        <select name="faculty_ids[]" id="faculty_ids" class="form-control select2" multiple>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}"
                                    {{ $course->faculties->contains($faculty->id) || in_array($faculty->id, old('faculty_ids', [])) ? 'selected' : '' }}>
                                    {{ $faculty->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('faculty_ids')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class=" d-flex flex-row justify-content-end my-2">
                        <a href="{{ route('courses.index') }}" class="btn btn-outline-danger">Cancel</a>
                        <button type="submit" class="btn btn-primary mx-2">Update </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

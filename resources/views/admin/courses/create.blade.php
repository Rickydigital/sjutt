@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #4B2E83; color: white;">
                            <strong class="card-title">Create Course</strong>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('courses.store') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="course_code">Course Code <span class="text-danger">*</span></label>
                                    <input type="text" name="course_code" id="course_code" class="form-control" value="{{ old('course_code') }}" required>
                                    @error('course_code')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="name">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                                    @error('name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea name="description" id="description" class="form-control" rows="5">{{ old('description') }}</textarea>
                                    @error('description')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="credits">Credits <span class="text-danger">*</span></label>
                                    <input type="number" name="credits" id="credits" class="form-control" value="{{ old('credits') }}" min="1" required>
                                    @error('credits')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="lecturer_ids">Lecturers</label>
                                    <select name="lecturer_ids[]" id="lecturer_ids" class="form-control select2" multiple>
                                        @foreach ($lecturers as $lecturer)
                                            <option value="{{ $lecturer->id }}" {{ in_array($lecturer->id, old('lecturer_ids', [])) ? 'selected' : '' }}>
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
                                            <option value="{{ $faculty->id }}" {{ in_array($faculty->id, old('faculty_ids', [])) ? 'selected' : '' }}>
                                                {{ $faculty->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('faculty_ids')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group text-right">
                                    <a href="{{ route('courses.index') }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn" style="background-color: #4B2E83; color: white;">Save Course</button>
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
            $('.select2').select2({
                placeholder: 'Select options',
                allowClear: true
            });
        });
    </script>
@endsection
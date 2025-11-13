@extends('components.app-main-layout')

@section('content')
    <div class="col-md-8 offset-md-2">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong class="card-title">{{ $course->name }} ({{ $course->course_code }})</strong>
            </div>
            <div class="card-body">
                <p><strong>Code:</strong> {{ $course->course_code }}</p>
                <p><strong>Name:</strong> {{ $course->name }}</p>
                <p><strong>Description:</strong> {{ $course->description ?: 'None' }}</p>
                <p><strong>Credits:</strong> {{ $course->credits }}</p>
                <p><strong>Hours:</strong> {{ $course->hours ?: 'Not specified' }}</p>
                <p><strong>Practical Hours:</strong> {{ $course->practical_hrs ?? 'N/A' }}</p>
                <p><strong>Session:</strong> {{ $course->session ?: 'Not specified' }}</p>
                <p><strong>Semester:</strong> {{ $course->semester->name ?? 'N/A' }}</p>
                <p><strong>Cross Catering:</strong> {{ $course->cross_catering ? 'Yes' : 'No' }}</p>
                <p><strong>Workshop:</strong> {{ $course->is_workshop ? 'Yes' : 'No' }}</p>
                <p><strong>Faculties:</strong> {{ $course->faculties->pluck('name')->join(', ') ?: 'None' }}</p>
                <p><strong>Lecturers:</strong> {{ $course->lecturers->pluck('name')->join(', ') ?: 'None' }}</p>
                <div class="d-flex flex-row justify-content-end my-2">
                    <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary">Back to Courses</a>
                    @if (Auth::user()->hasAnyRole(['Admin', 'Administrator']))
                        <a href="{{ route('courses.edit', $course) }}" class="btn btn-primary mx-2">Edit</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
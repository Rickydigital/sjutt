@extends('components.app-main-layout')

@section('content')
<div class="col-md-10 offset-md-1">
    <div class="card shadow-sm border-0">
        
        {{-- Header --}}
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">{{ $course->name }}</h5>
                <small class="text-muted">{{ $course->course_code }}</small>
            </div>

            <div>
                <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary btn-sm">
                    Back
                </a>

                @if (Auth::user()->hasAnyRole(['Admin', 'Administrator']))
                    <a href="{{ route('courses.edit', $course) }}" class="btn btn-primary btn-sm">
                        Edit
                    </a>
                @endif
            </div>
        </div>

        {{-- Body --}}
        <div class="card-body">

            {{-- Basic Info --}}
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="p-3 border rounded-3 h-100">
                        <h6 class="text-muted mb-3">Course Details</h6>

                        <p><strong>Description:</strong><br>
                            {{ $course->description ?: 'No description provided' }}
                        </p>

                        <div class="row">
                            <div class="col-6">
                                <p><strong>Credits</strong><br>{{ $course->credits }}</p>
                            </div>
                            <div class="col-6">
                                <p><strong>Session</strong><br>{{ $course->session }}</p>
                            </div>
                            <div class="col-6">
                                <p><strong>Hours</strong><br>{{ $course->hours }}</p>
                            </div>
                            <div class="col-6">
                                <p><strong>Practical</strong><br>{{ $course->practical_hrs ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <p>
                            <strong>Semester:</strong>
                            <span class="badge bg-light text-dark">
                                {{ $course->semester->name ?? 'N/A' }}
                            </span>
                        </p>

                        <p>
                            <strong>Flags:</strong><br>
                            <span class="badge {{ $course->cross_catering ? 'bg-success' : 'bg-secondary' }}">
                                Cross Catering
                            </span>
                            <span class="badge {{ $course->is_workshop ? 'bg-info' : 'bg-secondary' }}">
                                Workshop
                            </span>
                        </p>
                    </div>
                </div>

                {{-- Lecturers --}}
                <div class="col-md-6">
                    <div class="p-3 border rounded-3 h-100">
                        <h6 class="text-muted mb-3">Lecturers</h6>

                        @if($course->lecturers->isEmpty())
                            <p class="text-muted">No lecturers assigned</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($course->lecturers as $lecturer)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        {{ $lecturer->name }}
                                        <span class="badge bg-light text-dark">Lecturer</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Faculties + Student Counts --}}
            <div class="p-3 border rounded-3">
                <h6 class="text-muted mb-3">Faculties & Student Distribution</h6>

                @if($course->faculties->isEmpty())
                    <p class="text-muted">No faculties assigned</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Faculty</th>
                                    <th>Program</th>
                                    <th class="text-end">Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($course->faculties as $faculty)
                                    <tr>
                                        <td>{{ $faculty->name }}</td>
                                        <td>{{ $faculty->program->name ?? '-' }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-primary">
                                                {{ $faculty->pivot->student_count ?? 0 }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="2">Total Students</td>
                                    <td class="text-end">
                                        {{ $course->faculties->sum(fn($f) => $f->pivot->student_count ?? 0) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
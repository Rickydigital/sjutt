@extends('components.app-main-layout')

@section('content')
    <div class="col-md-10 offset-md-1">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong class="card-title">Faculty Details: {{ $faculty->name }}</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="font-weight-bold">Basic Information</h3>
                        <p><strong>Name:</strong> {{ $faculty->name }}</p>
                        <p><strong>Total Students:</strong> {{ $faculty->total_students_no }}</p>
                        <p><strong>Description:</strong> {{ $faculty->description ?? 'N/A' }}</p>
                        <p><strong>Program:</strong> {{ $faculty->program ? $faculty->program->name : 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h5 class="font-weight-bold" style="color: #4B2E83;">Courses</h5>
                        @if ($faculty->courses->isEmpty())
                            <p>No courses assigned.</p>
                        @else
                            <ul>
                                @foreach ($faculty->courses as $course)
                                    <li>{{ $course->name }} ({{ $course->course_code }})</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
                <hr>
                <h3 class="font-weight-bold">Student Groups</h3>
                @if ($faculty->groups->isEmpty())
                    <p>No groups assigned.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Group Name</th>
                                    <th>Student Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($faculty->groups as $group)
                                    <tr>
                                        <td>{{ $group->group_name }}</td>
                                        <td>{{ $group->student_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class=" d-flex flex-row justify-content-end my-2">
                    <a href="{{ route('faculties.index') }}" class="btn btn-outline-danger">Back</a>
                    <a href="{{ route('faculties.edit', $faculty->id) }}" class="btn btn-primary mx-2">Edit Faculty</a>
                </div>
            </div>
        </div>
    </div>
@endsection

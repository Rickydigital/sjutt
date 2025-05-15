@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-md-10 offset-md-1">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #4B2E83; color: white;">
                            <strong class="card-title">Faculty Details: {{ $faculty->name }}</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="font-weight-bold" style="color: #4B2E83;">Basic Information</h5>
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
                            <h5 class="font-weight-bold" style="color: #4B2E83;">Student Groups</h5>
                            @if ($faculty->groups->isEmpty())
                                <p>No groups assigned.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead style="background-color: #4B2E83; color: white;">
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
                            <div class="text-right mt-3">
                                <a href="{{ route('faculties.edit', $faculty->id) }}" class="btn btn-warning">Edit Faculty</a>
                                <a href="{{ route('faculties.index') }}" class="btn btn-secondary">Back to List</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .table-bordered th, .table-bordered td { border: 2px solid #4B2E83 !important; }
        .table-hover tbody tr:hover { background-color: #f1eef9; transition: background-color 0.3s ease; }
        .btn:hover { opacity: 0.85; transform: translateY(-1px); transition: all 0.2s ease; }
        .card { border: none; border-radius: 10px; overflow: hidden; }
        .table th, .table td { vertical-align: middle; }
    </style>
@endsection
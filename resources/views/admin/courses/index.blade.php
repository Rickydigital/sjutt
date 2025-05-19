@extends('components.app-main-layout')

@section('content')

    <div class="animated fadeIn">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex align-items-center justify-content-between">
                    <h1 class="font-weight-bold">
                        <i class="fa fa-graduation-cap mr-2"></i> Course Management
                    </h1>
                    <a href="{{ route('courses.create') }}" class="btn btn-primary"> New Course </a>
                </div>
            </div>
        </div>

        <!-- Course Table -->
        <div class="card">
            <div class="card-header">
                <strong class="card-title">List of Courses</strong>
            </div>
            <div class="card-body p-0">
                @if ($courses->isEmpty())
                    <div class="alert alert-info text-center m-3">
                        <i class="fa fa-info-circle mr-2"></i> No courses found.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Credits</th>
                                    <th>Lecturers</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $course)
                                    <tr>
                                        <td class="align-middle">{{ $course->course_code }}</td>
                                        <td class="align-middle">{{ $course->name }}</td>
                                        <td class="align-middle">{{ $course->credits }}</td>
                                        <td class="align-middle">
                                            {{ $course->lecturers->pluck('name')->join(', ') ?: 'None' }}
                                        </td>
                                        <td class="align-middle">
                                            <a href="{{ route('courses.edit', $course->id) }}"
                                                class="action-icon text-primary" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form action="{{ route('courses.destroy', $course->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-icon text-danger" title="Delete"
                                                    onclick="return confirm('Are you sure?');">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @if ($courses->hasPages())
            <div class="row mt-4">
                <div class="col-md-12">
                    {{ $courses->links('pagination::bootstrap-4') }}
                </div>
            </div>
        @endif
    </div>
@endsection

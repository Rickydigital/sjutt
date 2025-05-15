@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold" style="color: #4B2E83;">
                            <i class="fa fa-graduation-cap mr-2"></i> Course Management
                        </h1>
                        <a href="{{ route('courses.create') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                            <i class="fa fa-plus mr-1"></i> Create Course
                        </a>
                    </div>
                </div>
            </div>

            <!-- Course Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">List of Courses</strong>
                        </div>
                        <div class="card-body p-0">
                            @if ($courses->isEmpty())
                                <div class="alert alert-info text-center m-3">
                                    <i class="fa fa-info-circle mr-2"></i> No courses found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead style="background-color: #4B2E83; color: white;">
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
                                                        <a href="{{ route('courses.edit', $course->id) }}" class="btn btn-sm btn-warning action-btn" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('courses.destroy', $course->id) }}" method="POST" style="display:inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger action-btn" title="Delete" onclick="return confirm('Are you sure?');">
                                                                <i class="fa fa-trash"></i>
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
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('[title]').tooltip({ placement: 'top', trigger: 'hover' });
        });
    </script>
@endsection

<style>
    .table-bordered th, .table-bordered td { border: 2px solid #4B2E83 !important; }
    .table-hover tbody tr:hover { background-color: #f1eef9; transition: background-color 0.3s ease; }
    .btn:hover { opacity: 0.85; transform: translateY(-1px); transition: all 0.2s ease; }
    .card { border: none; border-radius: 10px; overflow: hidden; }
    .action-btn { min-width: 36px; padding: 6px; margin: 0 4px; }
    .table th, .table td { vertical-align: middle; }
</style>
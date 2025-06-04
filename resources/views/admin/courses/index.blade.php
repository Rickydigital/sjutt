@extends('components.app-main-layout')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                <div>
                    <strong class="card-title">Courses</strong>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('courses.create') }}" class="btn btn-primary">New Course</a>
                    <a href="{{ route('courses.export') }}" class="btn btn-success">Export Courses</a>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importModal">Import Courses</button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="courseFilter" class="form-label">Filter by Course</label>
                    <select id="courseFilter" class="form-select" style="width: 100%;">
                        <option value="">All Courses</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->course_code }}">{{ $course->name }} ({{ $course->course_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="facultyFilter" class="form-label">Filter by Faculty</label>
                    <select id="facultyFilter" name="faculty_id" class="form-select" style="width: 100%;">
                        <option value="">All Faculties</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}" {{ request('faculty_id') == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="search" class="form-label">Search Courses</label>
                    <form action="{{ route('courses.index') }}" method="GET" class="d-flex">
                        <input type="text" name="search" id="search" class="form-control me-2" placeholder="Search by code or name" value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if ($courses->isEmpty())
                <p class="text-center">No courses found.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Faculty</th>
                                <th>Credits</th>
                                <th>Lecturers</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courses as $course)
                                <tr>
                                    <td>{{ $course->course_code }}</td>
                                    <td>{{ $course->name }}</td>
                                    <td>{{ $course->faculties->pluck('name')->join(', ') ?: 'None' }}</td>
                                    <td>{{ $course->credits }}</td>
                                    <td>{{ $course->lecturers->pluck('name')->join(', ') ?: 'None' }}</td>
                                    <td>
                                        <a href="{{ route('courses.show', $course) }}" class="action-icon text-primary" data-bs-toggle="tooltip" title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <a href="{{ route('courses.edit', $course) }}" class="action-icon text-primary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('courses.destroy', $course) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-icon text-danger" data-bs-toggle="tooltip" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="my-2">
                    {{ $courses->links('vendor.pagination.bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Courses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('courses.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="file" class="form-label">Select Excel File</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @section('scripts')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                // Initialize Select2 for course filter
                $('#courseFilter').select2({
                    placeholder: "Select a course",
                    allowClear: true,
                    templateResult: function(data) {
                        if (!data.id) {
                            return data.text;
                        }
                        var course = {!! json_encode($courses->toArray()) !!}.find(c => c.course_code == data.id);
                        if (!course) {
                            return data.text;
                        }
                        var faculties = course.faculties ? course.faculties.map(f => f.name).join(', ') : 'No Faculty';
                        return $('<span>' + data.text + '<br><small>' + faculties + '</small></span>');
                    }
                });

                // Initialize Select2 for faculty filter
                $('#facultyFilter').select2({
                    placeholder: "Select a faculty",
                    allowClear: true
                });

                // Client-side course filter
                $('#courseFilter').on('change', function() {
                    var courseCode = $(this).val();
                    if (courseCode) {
                        $('tbody tr').hide();
                        $('tbody tr').each(function() {
                            if ($(this).find('td').first().text() === courseCode) {
                                $(this).show();
                            }
                        });
                    } else {
                        $('tbody tr').show();
                    }
                });

                // Faculty filter submission
                $('#facultyFilter').on('change', function() {
                    var facultyId = $(this).val();
                    var url = new URL(window.location.href);
                    if (facultyId) {
                        url.searchParams.set('faculty_id', facultyId);
                    } else {
                        url.searchParams.delete('faculty_id');
                    }
                    window.location.href = url.toString();
                });
            });
        </script>
    @endsection
@endsection
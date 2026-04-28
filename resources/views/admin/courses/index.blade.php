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
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#facultyStudentExportModal">
                        Export Faculty Students Sample
                    </button>

                    <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#facultyStudentImportModal">
                        Import Faculty Students
                    </button>
                    <a href="{{ route('courses.export') }}" class="btn btn-success">Export Courses</a>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importModal">Import Courses</button>
                </div>
            </div>
            <div class="row">
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
                    <label for="semesterFilter" class="form-label">Filter by Semester</label>
                    <select id="semesterFilter" name="semester_id" class="form-select" style="width: 100%;">
                        <option value="">All Semesters</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" {{ request('semester_id') == $semester->id ? 'selected' : '' }}>{{ $semester->name }}</option>
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
                    <table class="table table-striped table-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Semester</th>
                                <th>Faculty</th>
                                <th>Credits</th>
                                <th>Lecturer</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courses as $course)
                                <tr>
                                    <td>{{ $course->course_code }}</td>
                                    <td>{{ Str::words($course->name, 1, '...') }}</td>
                                    <td>{{ $course->semester->name ?? 'N/A' }}</td>
                                    <td>
                                        @if ($course->faculties->isNotEmpty())
                                            {{ $course->faculties->first()->name }}{{ $course->faculties->count() > 1 ? '...' : '' }}
                                        @else
                                            None
                                        @endif
                                    </td>
                                    <td>{{ $course->credits }}</td>
                                    <td>
                                        @if ($course->lecturers->isNotEmpty())
                                            {{ $course->lecturers->first()->name }}{{ $course->lecturers->count() > 1 ? '...' : '' }}
                                        @else
                                            None
                                        @endif
                                    </td>
                                    <td>
                                        <a href="#" class="action-icon text-primary" data-bs-toggle="modal" data-bs-target="#showModal{{ $course->id }}" title="View">
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
                                <!-- Show Modal for Course -->
                                <div class="modal fade" id="showModal{{ $course->id }}" tabindex="-1" aria-labelledby="showModalLabel{{ $course->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="showModalLabel{{ $course->id }}">{{ $course->name }} ({{ $course->course_code }})</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
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
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                            <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
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

    {{-- Export Faculty Student Count Sample Modal --}}
<div class="modal fade" id="facultyStudentExportModal" tabindex="-1" aria-labelledby="facultyStudentExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="facultyStudentExportModalLabel">
                    Export Course Faculty Student Count Sample
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('courses.faculty-students.template') }}" method="GET">
                <div class="modal-body">
                    <div class="alert alert-info">
                        Select one semester and one or more programs. The exported Excel will list all courses for that semester,
                        with selected program faculties as columns.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester_id" id="export_semester_id" class="form-select modal-select2" required>
                            <option value="">Select Semester</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Programs <span class="text-danger">*</span></label>
                        <select name="program_ids[]" id="export_program_ids" class="form-select modal-select2" multiple required>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}">
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">You can select many programs.</small>
                    </div>

                    <div class="small text-muted">
                        Example output: <strong>course_code | course_name | CICT 1 | CICT 2 | BAF 1</strong>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning">Download Sample</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Import Faculty Student Counts Modal --}}
<div class="modal fade" id="facultyStudentImportModal" tabindex="-1" aria-labelledby="facultyStudentImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="facultyStudentImportModalLabel">
                    Import Course Faculty Student Counts
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('courses.faculty-students.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-body">
                    <div class="alert alert-warning">
                        First download the sample, fill only student numbers under faculty columns, then import it here.
                        Do not rename <strong>course_code</strong> or faculty column names.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester_id" id="import_semester_id" class="form-select modal-select2" required>
                            <option value="">Select Semester</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Programs <span class="text-danger">*</span></label>
                        <select name="program_ids[]" id="import_program_ids" class="form-select modal-select2" multiple required>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}">
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select the same programs used during sample export.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excel File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>

                    <div class="small text-muted">
                        Empty cells are ignored. Numeric cells update <strong>course_faculty.student_count</strong>.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark">Import Student Counts</button>
                </div>
            </form>
        </div>
    </div>
</div>
   @section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    $('#facultyFilter').select2({ placeholder: "Select a faculty", allowClear: true, width: '100%' });
    $('#semesterFilter').select2({ placeholder: "Select a semester", allowClear: true, width: '100%' });

    function submitFilters() {
        const url = new URL(window.location.href);
        const facultyId = $('#facultyFilter').val();
        const semesterId = $('#semesterFilter').val();

        facultyId ? url.searchParams.set('faculty_id', facultyId) : url.searchParams.delete('faculty_id');
        semesterId ? url.searchParams.set('semester_id', semesterId) : url.searchParams.delete('semester_id');

        window.location.href = url.toString();
    }

    $('#facultyFilter, #semesterFilter').on('change', submitFilters);

    $('#facultyStudentExportModal').on('shown.bs.modal', function () {
        $('#export_semester_id, #export_program_ids').select2({
            width: '100%',
            dropdownParent: $('#facultyStudentExportModal')
        });
    });

    $('#facultyStudentImportModal').on('shown.bs.modal', function () {
        $('#import_semester_id, #import_program_ids').select2({
            width: '100%',
            dropdownParent: $('#facultyStudentImportModal')
        });
    });
});
</script>
@endsection
@endsection
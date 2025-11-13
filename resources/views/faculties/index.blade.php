@extends('components.app-main-layout')

@section('content')
<div class="animated fadeIn">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex align-items-center justify-content-between">
                <h1 class="font-weight-bold">
                    <i class="fa fa-university mr-2"></i> Faculty Management
                </h1>
                <div class="mb-3 d-flex align-items-center">
                    <!-- Program Filter Dropdown -->
                    <form action="{{ route('faculties.index') }}" method="GET" class="mr-3">
                        <select name="program_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Programs</option>
                            @foreach ($allPrograms as $program)
                            <option value="{{ $program->id }}" {{ $programId==$program->id ? 'selected' : '' }}>
                                {{ $program->name }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('faculties.create') }}" class="btn btn-primary mr-2">New Faculty</a>
                    <a href="{{ route('faculties.export') }}" class="btn btn-success mr-2">Export Faculties</a>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal"
                        data-bs-target="#importModal">
                        Import Faculties
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Faculty Table -->
    <div class="card">
        <div class="card-header">
            <strong class="card-title">Faculties by Program</strong>
        </div>
        <div class="card-body p-0">
            @if ($programs->isEmpty() || $programs->every(fn($program) => $program->faculties->isEmpty()))
            <div class="alert alert-info text-center m-3">
                <i class="fa fa-info-circle mr-2"></i> No faculties found.
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>Program</th>
                            <th>Faculties</th>
                            <th>Total Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programs as $program)
                        @if ($program->faculties->isNotEmpty())
                        @php
                        $facultyCount = $program->faculties->count();
                        @endphp
                        @foreach ($program->faculties as $index => $faculty)
                        <tr>
                            @if ($index === 0)
                            <!-- Program name spans all faculty rows -->
                            <td rowspan="{{ $facultyCount }}" class="align-middle">
                                {{ $program->name ?? 'N/A' }}
                            </td>
                            @endif
                            <td class="align-middle">{{ $faculty->name }}</td>
                            <td class="align-middle">{{ $faculty->total_students_no }}</td>
                            <td class="align-middle">
                                <a href="{{ route('faculties.show', $faculty->id) }}" class="action-icon text-primary"
                                    title="View" data-bs-toggle="tooltip" data-bs-placement="top">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="{{ route('faculties.edit', $faculty->id) }}" class="action-icon text-primary"
                                    title="Edit" data-bs-toggle="tooltip" data-bs-placement="top">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('faculties.destroy', $faculty->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-icon text-danger" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Delete"
                                        onclick="return confirm('Are you sure you want to delete this faculty?');">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Pagination Links -->
            <div class="my-2">
                {{ $programs->appends(['program_id' => $programId])->links('vendor.pagination.bootstrap-5') }}
            </div>


            @endif
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('faculties.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Faculties</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label">Choose Excel File</label>
                        <input type="file" class="form-control" id="file" name="file" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
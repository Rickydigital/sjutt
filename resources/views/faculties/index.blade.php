@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold" style="color: #4B2E83;">
                            <i class="fa fa-university mr-2"></i> Faculty Management
                        </h1>
                        <a href="{{ route('faculties.create') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                            <i class="fa fa-plus mr-1"></i> Create Faculty
                        </a>
                    </div>
                </div>
            </div>

            <!-- Faculty Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">Faculties by Program</strong>
                        </div>
                        <div class="card-body p-0">
                            @if ($programs->isEmpty() || $programs->every(fn($program) => $program->faculties->isEmpty()))
                                <div class="alert alert-info text-center m-3">
                                    <i class="fa fa-info-circle mr-2"></i> No faculties found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead style="background-color: #4B2E83; color: white;">
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
                                                                <a href="{{ route('faculties.show', $faculty->id) }}" class="btn btn-sm btn-info action-btn" title="View">
                                                                    <i class="fa fa-eye"></i>
                                                                </a>
                                                                <a href="{{ route('faculties.edit', $faculty->id) }}" class="btn btn-sm btn-warning action-btn" title="Edit">
                                                                    <i class="fa fa-edit"></i>
                                                                </a>
                                                                <form action="{{ route('faculties.destroy', $faculty->id) }}" method="POST" style="display:inline;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger action-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this faculty?');">
                                                                        <i class="fa fa-trash"></i>
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
                            @endif
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
    .table th, .table td { vertical-align: middle; text-align: center; }
    .table th { font-weight: 600; }
</style>
@extends('components.app-main-layout')

@section('content')


    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('programs.export') }}" class="btn btn-success">
                    <i class="bi bi-download me-1"></i> Export Programs
                </a>

                <a href="{{ asset('storage/sample_import_programs.xlsx') }}" class="btn btn-secondary">
                    <i class="bi bi-file-earmark-text me-1"></i> Sample Import
                </a>

                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="bi bi-upload me-1"></i> Import Programs
                </button>

                <a href="{{ route('programs.create') }}" class="btn btn-primary"> New Program </a>
            </div>

        </div>
    </div>

    <!-- Program Table -->

    <div class="card">
        <div class="card-header">
            <strong class="card-title">Programs</strong>
        </div>
        <div class="card-body p-0">
            @if ($programs->isEmpty())
                <div class="alert alert-info text-center m-3">
                    <i class="fa fa-info-circle mr-2"></i> No programs found.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Name</th>
                                <th>Administrator</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($programs as $program)
                                <tr>
                                    <td class="align-middle">{{ $program->name }}</td>
                                    <td class="align-middle">
                                        {{ $program->administrator ? $program->administrator->name : 'N/A' }}
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('programs.edit', $program->id) }}"
                                            class="action-icon text-primary" title="Edit" data-bs-toggle="tooltip"
                                            data-bs-placement="top">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('programs.destroy', $program->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-icon text-danger" title="Delete"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
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

    @if ($programs->hasPages())
        <div class="row mt-4">
            <div class="col-md-12">
                {{ $programs->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif



    <!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('programs.import') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Programs from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="file">Choose Excel file</label>
                    <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls" required>
                </div>
                <small class="text-muted">Ensure the file includes: Name, Short Name, Total Years, Description, Administrator Email.</small>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-info">Import</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('[title]').tooltip({
                placement: 'top',
                trigger: 'hover'
            });
        });
    </script>
@endsection

<style>
    .table-bordered th,
    .table-bordered td {
        border: 2px solid #4B2E83 !important;
    }

    .table-hover tbody tr:hover {
        background-color: #f1eef9;
        transition: background-color 0.3s ease;
    }

    .btn:hover {
        opacity: 0.85;
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }

    .card {
        border: none;
        border-radius: 10px;
        overflow: hidden;
    }

    .action-btn {
        min-width: 36px;
        padding: 6px;
        margin: 0 4px;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }
</style>

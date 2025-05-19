@extends('components.app-main-layout')

@section('content')


    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex align-items-center justify-content-between">
                <h1 class="font-weight-bold">
                    <i class="fa fa-book mr-2"></i> Program Management
                </h1>
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

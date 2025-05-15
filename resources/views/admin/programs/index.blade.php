@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold" style="color: #4B2E83;">
                            <i class="fa fa-book mr-2"></i> Program Management
                        </h1>
                        <a href="{{ route('programs.create') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                            <i class="fa fa-plus mr-1"></i> Create Program
                        </a>
                    </div>
                </div>
            </div>

            <!-- Program Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">List of Programs</strong>
                        </div>
                        <div class="card-body p-0">
                            @if ($programs->isEmpty())
                                <div class="alert alert-info text-center m-3">
                                    <i class="fa fa-info-circle mr-2"></i> No programs found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead style="background-color: #4B2E83; color: white;">
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
                                                    <td class="align-middle">{{ $program->administrator ? $program->administrator->name : 'N/A' }}</td>
                                                    <td class="align-middle">
                                                        <a href="{{ route('programs.edit', $program->id) }}" class="btn btn-sm btn-warning action-btn" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('programs.destroy', $program->id) }}" method="POST" style="display:inline;">
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

            @if ($programs->hasPages())
                <div class="row mt-4">
                    <div class="col-md-12">
                        {{ $programs->links('pagination::bootstrap-4') }}
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
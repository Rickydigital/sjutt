@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold" style="color: #4B2E83;">
                            <i class="fa fa-money-bill mr-2"></i> Fee Structure Management
                        </h1>
                        <a href="{{ route('fee_structures.create') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                            <i class="fa fa-plus mr-1"></i> Create Fee Structure
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <form method="GET" action="{{ route('fee_structures.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search fee structures..." 
                                value="{{ request('search') }}" style="border-radius: 20px 0 0 20px; border-color: #4B2E83;">
                            <div class="input-group-append">
                                <button type="submit" class="btn" style="background-color: #4B2E83; color: white; border-radius: 0 20px 20px 0;">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Fee Structures Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">List of Fee Structures</strong>
                        </div>
                        <div class="card-body p-0">
                            @if ($feeStructures->isEmpty())
                                <div class="alert alert-info text-center m-3">
                                    <i class="fa fa-info-circle mr-2"></i> No fee structures found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead style="background-color: #4B2E83; color: white;">
                                            <tr>
                                                <th scope="col"><i class="fa fa-graduation-cap mr-2"></i> Program Type</th>
                                                <th scope="col"><i class="fa fa-book mr-2"></i> Program Name</th>
                                                <th scope="col"><i class="fa fa-money mr-2"></i> First Year Fee</th>
                                                <th scope="col"><i class="fa fa-money mr-2"></i> Continuing Year Fee</th>
                                                <th scope="col"><i class="fa fa-money mr-2"></i> Final Year Fee</th>
                                                <th scope="col"><i class="fa fa-cogs mr-2"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($feeStructures as $feeStructure)
                                                <tr>
                                                    <td class="align-middle">{{ $feeStructure->program_type }}</td>
                                                    <td class="align-middle">{{ $feeStructure->program_name }}</td>
                                                    <td class="align-middle">{{ $feeStructure->first_year }}</td>
                                                    <td class="align-middle">{{ $feeStructure->continuing_year }}</td>
                                                    <td class="align-middle">{{ $feeStructure->final_year }}</td>
                                                    <td class="align-middle">
                                                        <a href="#" class="btn btn-sm btn-info action-btn" data-toggle="modal" 
                                                            data-target="#viewModal-{{ $feeStructure->id }}" title="View">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('fee_structures.edit', $feeStructure->id) }}" 
                                                            class="btn btn-sm btn-warning action-btn" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('fee_structures.destroy', $feeStructure->id) }}" method="POST" 
                                                            style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger action-btn" title="Delete">
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

            <!-- Modals -->
            @foreach ($feeStructures as $feeStructure)
                <div class="modal fade" id="viewModal-{{ $feeStructure->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header" style="background-color: #4B2E83; color: white;">
                                <h5 class="modal-title">Fee Structure Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-4"><strong>Program Type:</strong></div>
                                    <div class="col-md-8">{{ $feeStructure->program_type }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Program Name:</strong></div>
                                    <div class="col-md-8">{{ $feeStructure->program_name }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>First Year Fee:</strong></div>
                                    <div class="col-md-8">{{ $feeStructure->first_year }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Continuing Year Fee:</strong></div>
                                    <div class="col-md-8">{{ $feeStructure->continuing_year }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Final Year Fee:</strong></div>
                                    <div class="col-md-8">{{ $feeStructure->final_year }}</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination -->
            @if ($feeStructures->hasPages())
                <div class="row mt-4">
                    <div class="col-md-12">
                        {{ $feeStructures->appends(['search' => request('search')])->links('pagination::bootstrap-4') }}
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
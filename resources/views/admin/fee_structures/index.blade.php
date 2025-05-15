@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4 align-items-center">
                <div class="col-md-6">
                    <h1 class="font-weight-bold" style="color: #4B2E83;">
                        <i class="fa fa-money-bill mr-2"></i> Fee Structure Management
                    </h1>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="{{ route('fee_structures.create') }}" class="btn btn-primary" style="background-color: #4B2E83; border-color: #4B2E83; border-radius: 25px;">
                        <i class="fa fa-plus mr-1"></i> Create Fee Structure
                    </a>
                    <a href="{{ route('fee_structures.download_template') }}" class="btn btn-info" style="border-radius: 25px;">
                        <i class="fa fa-download mr-1"></i> Download Template
                    </a>
                </div>
            </div>

            <!-- Search and Import -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <form method="GET" action="{{ route('fee_structures.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search fee structures..." 
                                value="{{ request('search') }}" style="border-radius: 20px 0 0 20px; border-color: #4B2E83;">
                            <button type="submit" class="btn btn-primary" style="background-color: #4B2E83; border-color: #4B2E83; border-radius: 0 20px 20px 0;">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 col-lg-8 text-md-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal" style="border-radius: 25px;">
                        <i class="fa fa-upload mr-1"></i> Import Excel
                    </button>
                </div>
            </div>

            <!-- Alerts -->
            @if (session('success'))
                <div class="alert alert-success mb-4">
                    <i class="fa fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mb-4">
                    <i class="fa fa-exclamation-circle mr-2"></i> {{ session('error') }}
                </div>
            @endif

            <!-- Fee Structures Sections -->
            @if ($feeStructures->isEmpty())
                <div class="alert alert-info text-center mb-4">
                    <i class="fa fa-info-circle mr-2"></i> No fee structures found.
                </div>
            @else
                <!-- Tuition Fees -->
                @foreach (['TUITION_FEE_UNDERGRADUATE', 'TUITION_FEE_NON_DEGREE', 'TUITION_FEE_POSTGRADUATE'] as $type)
                    @php
                        $filtered = $feeStructures->filter(function ($fee) use ($type) {
                            return $fee->program_type === $type;
                        });
                        $title = str_replace(['TUITION_FEE_', 'UNDERGRADUATE', 'NON_DEGREE', 'POSTGRADUATE'], 
                                            ['Tuition Fee ', 'Undergraduate', 'Non-Degree', 'Postgraduate'], $type);
                    @endphp
                    @if ($filtered->isNotEmpty())
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm border-0 rounded-3">
                                    <div class="card-header d-flex align-items-center" style="background-color: #4B2E83; color: white;">
                                        <i class="fa fa-graduation-cap mr-2"></i>
                                        <strong class="card-title">{{ $title }}</strong>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead style="background-color: #f8f9fa; color: #4B2E83;">
                                                    <tr>
                                                        <th scope="col">Program Name</th>
                                                        <th scope="col">First Year (TZS)</th>
                                                        <th scope="col">Continuing Year (TZS)</th>
                                                        <th scope="col">Final Year (TZS)</th>
                                                        <th scope="col">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($filtered as $feeStructure)
                                                        <tr>
                                                            <td class="align-middle">{{ $feeStructure->program_name }}</td>
                                                            <td class="align-middle">{{ number_format($feeStructure->first_year, 0) }}</td>
                                                            <td class="align-middle">{{ number_format($feeStructure->continuing_year, 0) }}</td>
                                                            <td class="align-middle">{{ number_format($feeStructure->final_year, 0) }}</td>
                                                            <td class="align-middle">
                                                                <a href="{{ route('fee_structures.edit', $feeStructure->id) }}" class="btn btn-sm btn-warning" title="Edit" data-bs-toggle="tooltip">
                                                                    <i class="fa fa-edit"></i>
                                                                </a>
                                                                <form action="{{ route('fee_structures.destroy', $feeStructure->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this fee structure?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete" data-bs-toggle="tooltip">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                <!-- Compulsory Charges -->
                @php
                    $compulsory = $feeStructures->filter(function ($fee) {
                        return str_contains($fee->program_type, 'COMPULSORY_CHARGE_');
                    });
                @endphp
                @if ($compulsory->isNotEmpty())
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm border-0 rounded-3">
                                <div class="card-header d-flex align-items-center" style="background-color: #4B2E83; color: white;">
                                    <i class="fa fa-money-check mr-2"></i>
                                    <strong class="card-title">Compulsory Charges</strong>
                                </div>
                                <div class="card-body">
                                    @foreach (['DIPLOMA', 'CERTIFICATE', 'BACHELOR', 'POSTGRADUATE'] as $level)
                                        @php
                                            $type = "COMPULSORY_CHARGE_$level";
                                            $filtered = $compulsory->filter(function ($fee) use ($type) {
                                                return $fee->program_type === $type;
                                            });
                                            $levelTitle = str_replace(['DIPLOMA', 'CERTIFICATE', 'BACHELOR', 'POSTGRADUATE'], 
                                                                    ['Diploma', 'Certificate', 'Bachelor', 'Postgraduate'], $level);
                                        @endphp
                                        @if ($filtered->isNotEmpty())
                                            <h5 class="font-weight-bold mt-4 mb-3" style="color: #4B2E83;">{{ $levelTitle }}</h5>
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-4">
                                                    <thead style="background-color: #f8f9fa; color: #4B2E83;">
                                                        <tr>
                                                            <th scope="col">Program Name</th>
                                                            <th scope="col">First Year (TZS)</th>
                                                            <th scope="col">Continuing Year (TZS)</th>
                                                            <th scope="col">Final Year (TZS)</th>
                                                            <th scope="col">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($filtered as $feeStructure)
                                                            <tr>
                                                                <td class="align-middle">{{ $feeStructure->program_name }}</td>
                                                                <td class="align-middle">{{ number_format($feeStructure->first_year, 0) }}</td>
                                                                <td class="align-middle">{{ number_format($feeStructure->continuing_year, 0) }}</td>
                                                                <td class="align-middle">{{ number_format($feeStructure->final_year, 0) }}</td>
                                                                <td class="align-middle">
                                                                    <a href="{{ route('fee_structures.edit', $feeStructure->id) }}" class="btn btn-sm btn-warning" title="Edit" data-bs-toggle="tooltip">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>
                                                                    <form action="{{ route('fee_structures.destroy', $feeStructure->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this fee structure?');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" data-bs-toggle="tooltip">
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
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Import Modal -->
            <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content rounded-3">
                        <div class="modal-header" style="background-color: #4B2E83; color: white;">
                            <h5 class="modal-title" id="importModalLabel">Import Fee Structures</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('fee_structures.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="file" class="form-label">Upload Excel File</label>
                                    <input type="file" class="form-control" id="file" name="file" accept=".xlsx, .xls" required>
                                    @error('file')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <p class="text-muted">Ensure the Excel file follows the template format. <a href="{{ route('fee_structures.download_template') }}">Download template</a> if needed.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 25px;">Cancel</button>
                                <button type="submit" class="btn btn-primary" style="background-color: #4B2E83; border-color: #4B2E83; border-radius: 25px;">Import</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            @if ($feeStructures->hasPages())
                <div class="row mt-4">
                    <div class="col-md-12">
                        {{ $feeStructures->appends(['search' => request('search')])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    @section('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
    @endsection

    @section('style')
        <style>
            .table th, .table td {
                vertical-align: middle;
                padding: 12px;
            }
            .table-hover tbody tr:hover {
                background-color: #f1eef9;
            }
            .btn:hover {
                opacity: 0.85;
                transform: translateY(-1px);
                transition: all 0.2s ease;
            }
            .card {
                border-radius: 15px;
                overflow: hidden;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
            }
            .card-header {
                padding: 15px 20px;
                border-bottom: none;
            }
            .btn-sm {
                padding: 6px 12px;
                font-size: 0.875rem;
                border-radius: 10px;
            }
            .btn-warning {
                background-color: #ffc107;
                border-color: #ffc107;
                color: #212529;
            }
            .btn-danger {
                background-color: #dc3545;
                border-color: #dc3545;
            }
            .btn-danger i {
                color: white;
            }
            .table thead th {
                border-bottom: 2px solid #4B2E83;
            }
            .alert {
                border-radius: 10px;
                padding: 15px;
            }
        </style>
    @endsection
@endsection
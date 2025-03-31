@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold" style="color: #4B2E83;">
                            <i class="fa fa-book mr-2"></i> Course Management
                        </h1>
                        <a href="{{ route('courses.create') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                            <i class="fa fa-plus mr-1"></i> Create Course
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <form method="GET" action="{{ route('courses.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search courses..." 
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

            <!-- Courses Table -->
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
                                    <table id="courses-table" class="table table-bordered table-hover mb-0">
                                        <thead style="background-color: #4B2E83; color: white;">
                                            <tr>
                                                <th scope="col" class="py-3"><i class="fa fa-university mr-2"></i> School/Faculty</th>
                                                <th scope="col" class="py-3"><i class="fa fa-graduation-cap mr-2"></i> Program Name</th>
                                                <th scope="col" class="py-3"><i class="fa fa-certificate mr-2"></i> Entry Qualifications</th>
                                                <th scope="col" class="py-3"><i class="fa fa-money mr-2"></i> Tuition Fee</th>
                                                <th scope="col" class="py-3"><i class="fa fa-clock-o mr-2"></i> Duration</th>
                                                <th scope="col" class="py-3"><i class="fa fa-cogs mr-2"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($courses as $course)
                                                <tr>
                                                    <td class="align-middle">{{ $course->school_faculty }}</td>
                                                    <td class="align-middle">{{ $course->academic_programme }}</td>
                                                    <td class="align-middle">{{ Str::limit($course->entry_qualifications, 50) }}</td>
                                                    <td class="align-middle">Tsh. {{ number_format($course->tuition_fee_per_year, 2) }}</td>
                                                    <td class="align-middle">{{ $course->duration }}</td>
                                                    <td class="align-middle">
                                                        <!-- View Button -->
                                                        <a href="#" class="btn btn-sm btn-info action-btn" data-toggle="modal" 
                                                            data-target="#viewModal-{{ $course->id }}" title="View">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('courses.edit', $course->id) }}" class="btn btn-sm btn-warning action-btn" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('courses.destroy', $course->id) }}" method="POST" style="display:inline;" 
                                                            onsubmit="return confirm('Are you sure you want to delete this course?');">
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

            <!-- Modals for Each Course -->
            @foreach ($courses as $course)
                <div class="modal fade" id="viewModal-{{ $course->id }}" tabindex="-1" role="dialog" 
                    aria-labelledby="viewModalLabel-{{ $course->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header modal-header-custom">
                                <h5 class="modal-title" id="viewModalLabel-{{ $course->id }}">Course Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-4"><strong>School/Faculty:</strong></div>
                                    <div class="col-md-8">{{ $course->school_faculty }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Program Name:</strong></div>
                                    <div class="col-md-8">{{ $course->academic_programme }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Entry Qualifications:</strong></div>
                                    <div class="col-md-8">{{ $course->entry_qualifications }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Tuition Fee:</strong></div>
                                    <div class="col-md-8">Tsh. {{ number_format($course->tuition_fee_per_year, 2) }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Duration:</strong></div>
                                    <div class="col-md-8">{{ $course->duration }}</div>
                                </div>
                                <!-- Add more fields here if your course model has additional attributes -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination -->
            @if ($courses->hasPages())
                <div class="row mt-4">
                    <div class="col-md-12">
                        {{ $courses->appends(['search' => request('search')])->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip({
                placement: 'top',
                trigger: 'hover'
            });
        });
    </script>
@endsection

<style>
    .table-bordered th, .table-bordered td {
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
    .table th, .table td {
        vertical-align: middle;
    }
    .modal-header-custom {
        background-color: #4B2E83;
        color: white;
    }
</style>
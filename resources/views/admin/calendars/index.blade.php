{{-- @extends('layouts.admin') --}}
@extends('components.app-main-layout')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold">
                            <i class="fa fa-calendar mr-2"></i> Calendar Management
                        </h1>
                        <div>
                            <a href="{{ route('admin.calendars.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus"></i> Add New
                            </a>
                            <form action="{{ route('admin.calendars.import') }}" method="POST"
                                enctype="multipart/form-data" class="d-inline">
                                @csrf
                                <input type="file" name="file" accept=".csv, .xlsx, .xls" required
                                    style="display: none;" id="fileInput">
                                <label for="fileInput" class="btn btn-primary text-white" style="cursor: pointer;">
                                    <i class="bi bi-download mr-1"></i> Import
                                </label>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <form method="GET" action="{{ route('calendar.index') }}" class="row">
                        <div class="col-md-3 mb-3">
                            <input type="text" name="search" class="form-control" placeholder="Search calendars..."
                                value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <select name="month" class="form-control" onchange="this.form.submit()">
                                <option value="">All Months</option>
                                @foreach ($months as $monthNum)
                                    <option value="{{ $monthNum }}" {{ $monthFilter == $monthNum ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($monthNum)->format('F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <input type="text" name="date" class="form-control"
                                placeholder="Filter by date (e.g., Jan 1)" value="{{ $dateFilter }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <select name="program_category" class="form-control" onchange="this.form.submit()">
                                <option value="">All Program Categories</option>
                                @foreach ($programCategories as $category)
                                    <option value="{{ $category }}"
                                        {{ $programCategoryFilter == $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter mr-1"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Success Message -->
            @if (session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif

            <!-- Calendars -->
            @foreach ($calendars as $month => $monthCalendars)
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                        <strong class="card-title" style="color: #4B2E83;">{{ $month }}</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead style="background-color: #4B2E83; color: white;">
                                    <tr>
                                        <th><i class="fa fa-calendar-day mr-2"></i> Dates</th>
                                        <th><i class="fa fa-list-ol mr-2"></i> Week Numbers</th>
                                        <th><i class="fa fa-book mr-2"></i> Academic Calendar</th>
                                        <th><i class="fa fa-users mr-2"></i> Meeting Activities</th>
                                        <th><i class="fa fa-graduation-cap mr-2"></i> Academic Year</th>
                                        <th><i class="fa fa-cogs mr-2"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($monthCalendars as $calendar)
                                        <tr>
                                            <td class="align-middle">{{ $calendar->dates }}</td>
                                            <td class="align-middle">
                                                @foreach ($calendar->weekNumbers as $weekNumber)
                                                    {{ $weekNumber->program_category }}:
                                                    {{ $weekNumber->week_number }}<br>
                                                @endforeach
                                            </td>
                                            <td class="align-middle">{{ $calendar->academic_calendar ?? 'N/A' }}</td>
                                            <td class="align-middle">{{ $calendar->meeting_activities_calendar ?? 'N/A' }}
                                            </td>
                                            <td class="align-middle">{{ $calendar->academic_year }}</td>
                                            <td class="align-middle">
                                                <a href="#" class="btn btn-sm btn-info action-btn" data-toggle="modal"
                                                    data-bs-target="#viewModal-{{ $calendar->id }}" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.calendars.edit', $calendar->id) }}"
                                                    class="btn btn-sm btn-warning action-btn" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.calendars.destroy', $calendar->id) }}"
                                                    method="POST" style="display:inline;"
                                                    onsubmit="return confirm('Are you sure you want to delete this calendar?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger action-btn"
                                                        title="Delete">
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

                <!-- View Modal -->
                @foreach ($monthCalendars as $calendar)
                    <div class="modal fade" id="viewModal-{{ $calendar->id }}" tabindex="-1" role="dialog"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Calendar Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-4"><strong>Dates:</strong></div>
                                        <div class="col-md-8">{{ $calendar->dates }}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4"><strong>Week Numbers:</strong></div>
                                        <div class="col-md-8">
                                            @foreach ($calendar->weekNumbers as $weekNumber)
                                                {{ $weekNumber->program_category }}: {{ $weekNumber->week_number }}<br>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4"><strong>Academic Calendar:</strong></div>
                                        <div class="col-md-8">{{ $calendar->academic_calendar ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4"><strong>Meeting Activities:</strong></div>
                                        <div class="col-md-8">{{ $calendar->meeting_activities_calendar ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4"><strong>Academic Year:</strong></div>
                                        <div class="col-md-8">{{ $calendar->academic_year }}</div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
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

    .form-control:focus {
        border-color: #4B2E83;
        box-shadow: 0 0 5px rgba(75, 46, 131, 0.5);
    }
</style>

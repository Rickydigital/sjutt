@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold" style="color: #4B2E83;">
                            <i class="fa fa-clock mr-2"></i> Timetable
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Search Bar, Import Form, and Filters -->
            <div class="row mb-4 align-items-center">
                <!-- Search Bar -->
                <div class="col-md-6 col-lg-3 mb-3 mb-md-0">
                    <form method="GET" action="{{ route('timetable.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search timetable..." 
                                value="{{ request('search') }}" style="border-radius: 20px 0 0 20px; border-color: #4B2E83;">
                            <div class="input-group-append">
                                <button type="submit" class="btn" style="background-color: #4B2E83; color: white; border-radius: 0 20px 20px 0;">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Import Form -->
                <div class="col-md-6 col-lg-3 mb-3 mb-md-0">
                    <form action="{{ route('timetable.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="input-group">
                            <input type="file" name="file" class="form-control" required style="border-radius: 20px 0 0 20px; border-color: #4B2E83;">
                            <div class="input-group-append">
                                <button type="submit" class="btn" style="background-color: #4B2E83; color: white; border-radius: 0 20px 20px 0;">
                                    <i class="fa fa-upload mr-1"></i> Import
                                </button>
                            </div>
                        </div>
                        @error('file')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </form>
                </div>

                <!-- Filters -->
                <div class="col-md-12 col-lg-6 mt-3 mt-lg-0">
                    <form method="GET" action="{{ route('timetable.index') }}" class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <select name="day" class="form-control" style="border-color: #4B2E83;" onchange="this.form.submit()">
                                <option value="">All Days</option>
                                @foreach ($days as $dayOption)
                                    <option value="{{ $dayOption }}" {{ $dayFilter == $dayOption ? 'selected' : '' }}>
                                        {{ $dayOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <select name="faculty" class="form-control" style="border-color: #4B2E83;" onchange="this.form.submit()">
                                <option value="">All Faculties</option>
                                @foreach ($faculties as $facultyOption)
                                    <option value="{{ $facultyOption }}" {{ $facultyFilter == $facultyOption ? 'selected' : '' }}>
                                        {{ $facultyOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="year" class="form-control" style="border-color: #4B2E83;" onchange="this.form.submit()">
                                <option value="">All Years</option>
                                @foreach ($years as $yearOption)
                                    <option value="{{ $yearOption }}" {{ $yearFilter == $yearOption ? 'selected' : '' }}>
                                        {{ $yearOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Preserve search value in filters -->
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    </form>
                </div>
            </div>

            <!-- Success Message -->
            @if (session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif

            <!-- Timetable -->
            @php
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            @endphp
            @foreach ($days as $day)
                @if (isset($timetables[$day]) && $timetables[$day]->count() > 0)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">{{ $day }}</strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0">
                                    <thead style="background-color: #4B2E83; color: white;">
                                        <tr>
                                            <th><i class="fa fa-university mr-2"></i> Faculty</th>
                                            <th><i class="fa fa-calendar mr-2"></i> Year</th>
                                            <th><i class="fa fa-clock mr-2"></i> Start Time</th>
                                            <th><i class="fa fa-clock mr-2"></i> End Time</th>
                                            <th><i class="fa fa-code mr-2"></i> Course Code</th>
                                            <th><i class="fa fa-tasks mr-2"></i> Activity</th>
                                            <th><i class="fa fa-map-marker mr-2"></i> Venue</th>
                                            <th><i class="fa fa-cogs mr-2"></i> Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($timetables[$day] as $timetable)
                                            <tr>
                                                <td class="align-middle">{{ $timetable->faculty }}</td>
                                                <td class="align-middle">{{ $timetable->year }}</td>
                                                <td class="align-middle">{{ $timetable->time_start }}</td>
                                                <td class="align-middle">{{ $timetable->time_end }}</td>
                                                <td class="align-middle">{{ $timetable->course_code }}</td>
                                                <td class="align-middle">{{ $timetable->activity }}</td>
                                                <td class="align-middle">{{ $timetable->venue }}</td>
                                                <td class="align-middle">
                                                    <a href="#" class="btn btn-sm btn-info action-btn" data-toggle="modal" 
                                                        data-target="#viewModal-{{ $timetable->id }}" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('timetable.edit', $timetable->id) }}" 
                                                        class="btn btn-sm btn-warning action-btn" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('timetable.destroy', $timetable->id) }}" method="POST" 
                                                        style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this timetable?');">
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
                        </div>
                    </div>

                    <!-- View Modal -->
                    @foreach ($timetables[$day] as $timetable)
                        <div class="modal fade" id="viewModal-{{ $timetable->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header" style="background-color: #4B2E83; color: white;">
                                        <h5 class="modal-title">Timetable Details</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-4"><strong>Day:</strong></div>
                                            <div class="col-md-8">{{ $timetable->day }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4"><strong>Faculty:</strong></div>
                                            <div class="col-md-8">{{ $timetable->faculty }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4"><strong>Year:</strong></div>
                                            <div class="col-md-8">{{ $timetable->year }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4"><strong>Start Time:</strong></div>
                                            <div class="col-md-8">{{ $timetable->time_start }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4"><strong>End Time:</strong></div>
                                            <div class="col-md-8">{{ $timetable->time_end }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4"><strong>Course Code:</strong></div>
                                            <div class="col-md-8">{{ $timetable->course_code }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4"><strong>Activity:</strong></div>
                                            <div class="col-md-8">{{ $timetable->activity }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4"><strong>Venue:</strong></div>
                                            <div class="col-md-8">{{ $timetable->venue }}</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            @endforeach
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
    .form-control:focus { border-color: #4B2E83; box-shadow: 0 0 5px rgba(75, 46, 131, 0.5); }
</style>
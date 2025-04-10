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
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
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
            
                <div class="col-md-6 mb-3">
                    <div class="import-container">
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
                        <div id="filePreview" class="card mt-3" style="display: none; border-color: #4B2E83;">
                            <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                                <strong style="color: #4B2E83;">File Preview</strong>
                                <button type="button" class="close" id="clearPreview" style="float: right;">
                                    <span>×</span>
                                </button>
                            </div>
                            <div class="card-body p-2">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="previewTable">
                                        <thead style="background-color: #4B2E83; color: white;"></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="text-muted small mt-2" id="previewInfo"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-12">
                    <form method="GET" action="{{ route('timetable.index') }}" class="row">
                        <div class="col-md-4 mb-3">
                            <select name="day" class="form-control" style="border-color: #4B2E83;" onchange="this.form.submit()">
                                <option value="">All Days</option>
                                @foreach ($days as $dayOption)
                                    <option value="{{ $dayOption }}" {{ $dayFilter == $dayOption ? 'selected' : '' }}>
                                        {{ $dayOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <select name="faculty" class="form-control" style="border-color: #4B2E83;" onchange="this.form.submit()">
                                <option value="">All Faculties</option>
                                @foreach ($faculties as $id => $name)
                                    <option value="{{ $id }}" {{ $facultyFilter == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="year" class="form-control" style="border-color: #4B2E83;" onchange="this.form.submit()">
                                <option value="">All Years</option>
                                @foreach ($years as $id => $year)
                                    <option value="{{ $id }}" {{ $yearFilter == $id ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    </form>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6 col-12">
                    <a href="{{ route('timetable.pdf', request()->query()) }}" class="btn btn-primary">Export to PDF</a>
                </div>
                <div class="col-md-6 col-12">
                    <a href="{{ route('timetable.create') }}" class="btn btn-primary">Add New</a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif

            <!-- Timetable -->
            @php
                $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                $groupedTimetables = $timetables->groupBy('faculty_id')
                    ->map(function ($facultyGroup) {
                        return $facultyGroup->groupBy('year_id');
                    });
            @endphp

            @if ($groupedTimetables->isEmpty())
                <div class="alert alert-info m-3">No timetable data available for the selected filters.</div>
            @else
                @foreach ($groupedTimetables as $facultyId => $years)
                    @foreach ($years as $yearId => $timetables)
                        @php
                            $faculty = \App\Models\Faculty::find($facultyId);
                            $year = \App\Models\Year::find($yearId);
                            $activitiesByDay = $timetables->groupBy('day');
                            $occupiedUntil = array_fill_keys($days, -1);
                        @endphp

                        @if ($faculty && $year) <!-- Check if $faculty and $year exist -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                                    <strong class="card-title" style="color: #4B2E83;">{{ $faculty->name }} - Year {{ $year->year }}</strong>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover mb-0">
                                            <thead style="background-color: #4B2E83; color: white;">
                                                <tr>
                                                    <th>Time</th>
                                                    @foreach ($days as $day)
                                                        <th>{{ $day }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($timeSlots as $i => $slotStart)
                                                    <tr>
                                                        <td>{{ $slotStart }}-{{ date('H:i', strtotime($slotStart) + 3600) }}</td>
                                                        @foreach ($days as $day)
                                                            @if ($i > $occupiedUntil[$day])
                                                                @php
                                                                    $activity = null;
                                                                    $slotEnd = date('H:i', strtotime($slotStart) + 3600);
                                                                    foreach ($activitiesByDay[$day] ?? [] as $act) {
                                                                        if ($act->time_start < $slotEnd && $act->time_end > $slotStart) {
                                                                            $activity = $act;
                                                                            break;
                                                                        }
                                                                    }
                                                                @endphp
                                                                @if ($activity)
                                                                    @php
                                                                        $startTime = strtotime($activity->time_start);
                                                                        $endTime = strtotime($activity->time_end);
                                                                        $span = ceil(($endTime - $startTime) / 3600);
                                                                        $occupiedUntil[$day] = $i + $span - 1;
                                                                    @endphp
                                                                    <td rowspan="{{ $span }}">
                                                                        {{ $activity->course_code }} <br>
                                                                        {{ $activity->activity }} <br>
                                                                        {{ $activity->venue->name }} <br>
                                                                        <a href="#" class="btn btn-sm btn-info action-btn" data-toggle="modal" 
                                                                            data-target="#viewModal-{{ $activity->id }}" title="View">
                                                                            <i class="fa fa-eye"></i>
                                                                        </a>
                                                                        <a href="{{ route('timetable.edit', $activity->id) }}" 
                                                                            class="btn btn-sm btn-warning action-btn" title="Edit">
                                                                            <i class="fa fa-edit"></i>
                                                                        </a>
                                                                        <form action="{{ route('timetable.destroy', $activity->id) }}" method="POST" 
                                                                            style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this timetable?');">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="btn btn-sm btn-danger action-btn" title="Delete">
                                                                                <i class="fa fa-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                    </td>
                                                                @else
                                                                    <td>-</td>
                                                                @endif
                                                            @endif
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endforeach
            @endif

            <!-- View Modal -->
            @foreach ($timetables as $timetable)
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
                                    <div class="col-md-8">{{ $timetable->faculty->name ?? 'N/A' }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Year:</strong></div>
                                    <div class="col-md-8">{{ $timetable->year->year ?? 'N/A' }}</div>
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
                                    <div class="col-md-8">{{ $timetable->venue->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
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
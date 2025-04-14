@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header and Filters (unchanged) -->

            @if(session('import_errors'))
                <div class="alert alert-danger">
                    <strong>Import completed with errors:</strong>
                    <ul>
                        @foreach(session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold" style="color: #4B2E83;">
                            <i class="fa fa-calendar mr-2"></i> Examination Timetables
                        </h1>
                        <div>
                            <a href="{{ route('timetables.create') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                                <i class="fa fa-plus mr-1"></i> Add New
                            </a>

                            <a href="{{ route('timetables.import.view') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                                <i class="fa fa-upload mr-1"></i> Import
                            </a>
                          
                            <a href="{{ route('timetables.export.all.pdf') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                                <i class="fa fa-file-pdf-o mr-1"></i> Export All to PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <form method="GET" action="{{ route('timetables.index') }}" class="row">
                        <!-- Filters (unchanged) -->
                    </form>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif

            <!-- Timetable Tables Grouped by Program -->

               
            @php
                $timeSlots = [
                    'Morning' => '08:00:00-11:00:00',
                    'Noon' => '12:30:00-15:30:00',
                    'Evening' => '17:00:00-20:00:00'
                ];
                $allDays = $timetables->pluck('exam_date')->unique()->sort()->take(40); // Take 10 days
                $yearsList = \App\Models\Year::whereIn('id', [1, 2, 3, 4])->orderBy('year')->get();
                $week1Days = $allDays->slice(0, 5); // Mon-Fri (Feb 10-14)
                $week2Days = $allDays->slice(5, 5); // Mon-Fri (Feb 17-21)
            @endphp

            @forelse ($groupedTimetables as $program => $data)
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #4B2E83; color: white;">
                        <h3 class="m-0">{{ $program }} Timetables</h3>
                    </div>
                    <div class="card-body p-0">
                        <!-- Week 1 Table -->
                        <h4 class="text-center mt-3" style="color: #4B2E83;">Week 1 (Feb 10 - Feb 14)</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead style="background-color: #4B2E83; color: white;">
                                    <tr>
                                        <th rowspan="2">Time Slot</th>
                                        <th rowspan="2">Year</th>
                                        @foreach ($week1Days as $day)
                                            <th colspan="{{ $data['faculties']->count() }}" class="day-header">
                                                {{ \Carbon\Carbon::parse($day)->format('l (M d)') }}
                                            </th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach ($week1Days as $day)
                                            @foreach ($data['faculties'] as $faculty)
                                                <th class="sub-header">{{ $faculty->name }}</th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($timeSlots as $slotName => $timeRange)
                                        @php
                                            [$startTime, $endTime] = explode('-', $timeRange);
                                        @endphp
                                        @foreach ($yearsList as $yearIndex => $year)
                                            <tr>
                                                @if ($yearIndex === 0)
                                                    <td rowspan="4" class="time-slot">
                                                        {{ substr($startTime, 0, 5) }}-{{ substr($endTime, 0, 5) }} ({{ $slotName }})
                                                    </td>
                                                @endif
                                                <td>{{ $year->year }}</td>
                                                @foreach ($week1Days as $day)
                                                    @foreach ($data['faculties'] as $faculty)
                                                    @php
                                                        $entry = $data['timetables']->firstWhere(function ($item) use ($day, $faculty, $year, $startTime, $endTime) {
                                                            return $item->exam_date == $day &&
                                                                $item->faculty_id == $faculty->id &&  // Changed from faculty to faculty_id
                                                                $item->year_id == $year->id &&       // Changed from year to year_id
                                                                $item->start_time == $startTime &&
                                                                $item->end_time == $endTime;
                                                        });
                                                    @endphp
                                                        <td>
                                                            @if ($entry)
                                                                {{ $entry->course_code }} <br><hr> {{ $entry->venue->name }}
                                                                <div class="action-buttons">
                                                                    <a href="{{ route('timetables.edit', $entry->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>
                                                                    <form action="{{ route('timetables.destroy', $entry->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                                            <i class="fa fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Week 2 Table -->
                        @if ($week2Days->isNotEmpty())
                            <h4 class="text-center mt-3" style="color: #4B2E83;">Week 2 (Feb 17 - Feb 21)</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0">
                                    <thead style="background-color: #4B2E83; color: white;">
                                        <tr>
                                            <th rowspan="2">Time Slot</th>
                                            <th rowspan="2">Year</th>
                                            @foreach ($week2Days as $day)
                                                <th colspan="{{ $data['faculties']->count() }}" class="day-header">
                                                    {{ \Carbon\Carbon::parse($day)->format('l (M d)') }}
                                                </th>
                                            @endforeach
                                        </tr>
                                        <tr>
                                            @foreach ($week2Days as $day)
                                                @foreach ($data['faculties'] as $faculty)
                                                    <th class="sub-header">{{ $faculty->name }}</th>
                                                @endforeach
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($timeSlots as $slotName => $timeRange)
                                            @php
                                                [$startTime, $endTime] = explode('-', $timeRange);
                                            @endphp
                                            @foreach ($yearsList as $yearIndex => $year)
                                                <tr>
                                                    @if ($yearIndex === 0)
                                                        <td rowspan="4" class="time-slot">
                                                            {{ substr($startTime, 0, 5) }}-{{ substr($endTime, 0, 5) }} ({{ $slotName }})
                                                        </td>
                                                    @endif
                                                    <td>{{ $year->year }}</td>
                                                    @foreach ($week2Days as $day)
                                                        @foreach ($data['faculties'] as $faculty)
                                                        @php
                                                            $entry = $data['timetables']->firstWhere(function ($item) use ($day, $faculty, $year, $startTime, $endTime) {
                                                                return $item->exam_date == $day &&
                                                                    $item->faculty_id == $faculty->id &&  // Changed from faculty to faculty_id
                                                                    $item->year_id == $year->id &&       // Changed from year to year_id
                                                                    $item->start_time == $startTime &&
                                                                    $item->end_time == $endTime;
                                                            });
                                                        @endphp
                                                            <td>
                                                                @if ($entry)
                                                                    {{ $entry->course_code }} <br><hr> {{ $entry->venue->name }}
                                                                    <div class="action-buttons">
                                                                        <a href="{{ route('timetables.edit', $entry->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                                                            <i class="fa fa-edit"></i>
                                                                        </a>
                                                                        <form action="{{ route('timetables.destroy', $entry->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                                                <i class="fa fa-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <p>No timetables found.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('[title]').tooltip({ placement: 'top', trigger: 'hover' });
        });
        document.getElementById('fileInput').addEventListener('change', function() {
            document.getElementById('importForm').submit();
        });
    </script>
@endsection

<style>
    .table-bordered th, .table-bordered td { border: 2px solid #4B2E83 !important; }
    .table-hover tbody tr:hover { background-color: #f1eef9; transition: background-color 0.3s ease; }
    .btn:hover { opacity: 0.85; transform: translateY(-1px); transition: all 0.2s ease; }
    .card { border: none; border-radius: 10px; overflow: hidden; }
    .form-control:focus { border-color: #4B2E83; box-shadow: 0 0 5px rgba(75, 46, 131, 0.5); }
    .card-header { padding: 15px; }
    .day-header { background-color: #e6f3ff; color: #4B2E83; }
    .sub-header { background-color: #f9f9f9; font-weight: bold; color: #4B2E83; }
    .time-slot { background-color: #fff3e6; }
    .action-buttons { margin-top: 5px; }
    .btn-sm { padding: 2px 5px; }
</style>
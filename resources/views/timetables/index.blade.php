@php
    use Carbon\Carbon;
@endphp

@extends('components.app-main-layout')

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .time-slot-header {
            font-weight: bold;
            background-color: #f8f9fa;
            position: sticky;
            left: 0;
            z-index: 2;
        }
        .year-header {
            background-color: #e9ecef;
            position: sticky;
            left: 150px;
            z-index: 2;
        }
        caption {
            caption-side: top;
            font-size: 1.25rem;
            font-weight: bold;
            padding: 10px;
            background-color: #007bff;
            color: white;
        }
        th, td {
            vertical-align: middle;
            white-space: nowrap;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table th, .table td {
            min-width: 150px;
        }
        .table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background-color: #fff;
            border-bottom: 2px solid #dee2e6;
        }
        .table thead th:first-child, .table thead th:nth-child(2) {
            z-index: 4;
        }
    </style>
@endsection

@section('content')
<div class="container">
    <h1>Examination Timetables</h1>

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div>
            @if ($setups->isNotEmpty())
                <div class="form-group">
                    <label for="exam_type" class="form-label">Select Exam Type to View:</label>
                    <select class="form-control" id="exam_type" onchange="location = this.value;">
                        <option value="{{ route('timetables.index') }}">Select Exam Type</option>
                        @foreach ($examTypes as $type)
                            <option value="{{ route('timetables.index', ['exam_type' => $type]) }}" {{ $selectedType == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <div>
            @if ($setup)
                <a href="{{ route('timetables.pdf', ['exam_type' => $selectedType]) }}" class="btn btn-success">Download PDF</a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editSetupModal">Edit Setup</button>
            @else
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#setupModal">Create Setup</button>
            @endif
        </div>
    </div>

    @if ($setup)
        @php
            $datesPerPage = 5;
            $dateChunks = array_chunk($days, $datesPerPage);
        @endphp
        @foreach ($dateChunks as $chunkIndex => $dateChunk)
            <div class="card mb-4 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <caption>Type: {{ $selectedType }} ({{ $setup->academic_year }}) - Semester: {{ $setup->semester }}</caption>
                            <thead>
                                <tr>
                                    <th rowspan="2" class="time-slot-header">Time</th>
                                    <th rowspan="2" class="year-header">Year</th>
                                    @foreach ($dateChunk as $date)
                                        @php
                                            $carbonDate = Carbon::parse($date);
                                            $formattedDate = $carbonDate->format('d-m') . ' (' . $carbonDate->format('l') . ')';
                                        @endphp
                                        <th colspan="{{ $programs->count() }}">{{ $formattedDate }}</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach ($dateChunk as $date)
                                        @foreach ($programs as $program)
                                            <th>{{ $program->short_name }}</th>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($timeSlots as $slot)
                                    <tr>
                                        <td rowspan="4" class="time-slot-header">{{ $slot['name'] }} ({{ $slot['start_time'] }} - {{ $slot['end_time'] }})</td>
                                        <td class="year-header">Year 1</td>
                                        @foreach ($dateChunk as $date)
                                            @foreach ($programs as $program)
                                                @php
                                                    $faculty = \App\Models\Faculty::where('program_id', $program->id)
                                                        ->where('name', 'LIKE', "% 1")
                                                        ->first();
                                                    $slotStartTime = Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i:s');
                                                    $slotEndTime = Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i:s');
                                                    $timetable = $timetables->firstWhere(function ($t) use ($faculty, $date, $slotStartTime, $slotEndTime) {
                                                        return $t->faculty_id == ($faculty ? $faculty->id : null) &&
                                                               $t->exam_date == $date &&
                                                               $t->start_time == $slotStartTime &&
                                                               $t->end_time == $slotEndTime;
                                                    });
                                                @endphp
                                                <td>
                                                    @if ($timetable)
                                                        <span class="badge badge-info p-2">{{ $timetable->course_code }} ({{ optional($timetable->venue)->name ?? 'N/A' }})</span>
                                                        <div class="mt-1 d-flex flex-nowrap">
                                                            <button class="btn btn-sm btn-info show-exam mr-1"
                                                                    data-id="{{ $timetable->id }}"
                                                                    data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                    data-course-code="{{ $timetable->course_code }}"
                                                                    data-exam-date="{{ $timetable->exam_date }}"
                                                                    data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                    data-venue-id="{{ $timetable->venue_id }}"
                                                                    data-group-selection="{{ $timetable->group_selection }}"
                                                                    data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                    title="Show Details">
                                                                <i class="fa fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-primary edit-exam mr-1"
                                                                    data-id="{{ $timetable->id }}"
                                                                    data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                    data-course-code="{{ $timetable->course_code }}"
                                                                    data-exam-date="{{ $timetable->exam_date }}"
                                                                    data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                    data-venue-id="{{ $timetable->venue_id }}"
                                                                    data-group-selection="{{ $timetable->group_selection }}"
                                                                    data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                    title="Edit Exam">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-exam"
                                                                    data-id="{{ $timetable->id }}"
                                                                    title="Delete Exam">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    @else
                                                        @if (1 <= $program->total_years)
                                                            <button class="btn btn-sm btn-success create-exam"
                                                                    data-program-id="{{ $program->id }}"
                                                                    data-year-num="1"
                                                                    data-date="{{ $date }}"
                                                                    data-time-slot="{{ json_encode($slot) }}">+</button>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                    <tr>
                                        <td class="year-header">Year 2</td>
                                        @foreach ($dateChunk as $date)
                                            @foreach ($programs as $program)
                                                @php
                                                    $faculty = \App\Models\Faculty::where('program_id', $program->id)
                                                        ->where('name', 'LIKE', "% 2")
                                                        ->first();
                                                    $slotStartTime = Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i:s');
                                                    $slotEndTime = Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i:s');
                                                    $timetable = $timetables->firstWhere(function ($t) use ($faculty, $date, $slotStartTime, $slotEndTime) {
                                                        return $t->faculty_id == ($faculty ? $faculty->id : null) &&
                                                               $t->exam_date == $date &&
                                                               $t->start_time == $slotStartTime &&
                                                               $t->end_time == $slotEndTime;
                                                    });
                                                @endphp
                                                <td>
                                                    @if ($timetable)
                                                        <span class="badge badge-info p-2">{{ $timetable->course_code }} ({{ optional($timetable->venue)->name ?? 'N/A' }})</span>
                                                        <div class="mt-1">
                                                            <button class="btn btn-sm btn-info show-exam mr-1"
                                                                    data-id="{{ $timetable->id }}"
                                                                    data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                    data-course-code="{{ $timetable->course_code }}"
                                                                    data-exam-date="{{ $timetable->exam_date }}"
                                                                    data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                    data-venue-id="{{ $timetable->venue_id }}"
                                                                    data-group-selection="{{ $timetable->group_selection }}"
                                                                    data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                    title="Show Details">
                                                                <i class="fa fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-primary edit-exam mr-1"
                                                                    data-id="{{ $timetable->id }}"
                                                                    data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                    data-course-code="{{ $timetable->course_code }}"
                                                                    data-exam-date="{{ $timetable->exam_date }}"
                                                                    data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                    data-venue-id="{{ $timetable->venue_id }}"
                                                                    data-group-selection="{{ $timetable->group_selection }}"
                                                                    data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                    title="Edit Exam">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-exam"
                                                                    data-id="{{ $timetable->id }}"
                                                                    title="Delete Exam">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    @else
                                                        @if (2 <= $program->total_years)
                                                            <button class="btn btn-sm btn-success create-exam"
                                                                    data-program-id="{{ $program->id }}"
                                                                    data-year-num="2"
                                                                    data-date="{{ $date }}"
                                                                    data-time-slot="{{ json_encode($slot) }}">+</button>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                    <tr>
                                        <td class="year-header">Year 3</td>
                                        @foreach ($dateChunk as $date)
                                            @foreach ($programs as $program)
                                                @php
                                                    $faculty = \App\Models\Faculty::where('program_id', $program->id)
                                                        ->where('name', 'LIKE', "% 3")
                                                        ->first();
                                                    $slotStartTime = Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i:s');
                                                    $slotEndTime = Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i:s');
                                                    $timetable = $timetables->firstWhere(function ($t) use ($faculty, $date, $slotStartTime, $slotEndTime) {
                                                        return $t->faculty_id == ($faculty ? $faculty->id : null) &&
                                                               $t->exam_date == $date &&
                                                               $t->start_time == $slotStartTime &&
                                                               $t->end_time == $slotEndTime;
                                                    });
                                                @endphp
                                                <td>
                                                    @if ($timetable)
                                                        <span class="badge badge-info p-2">{{ $timetable->course_code }} ({{ optional($timetable->venue)->name ?? 'N/A' }})</span>
                                                        <div class="mt-1">
                                                            <button class="btn btn-sm btn-info show-exam mr-1"
                                                                    data-id="{{ $timetable->id }}"
                                                                    data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                    data-course-code="{{ $timetable->course_code }}"
                                                                    data-exam-date="{{ $timetable->exam_date }}"
                                                                    data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                    data-venue-id="{{ $timetable->venue_id }}"
                                                                    data-group-selection="{{ $timetable->group_selection }}"
                                                                    data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                    title="Show Details">
                                                                <i class="fa fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-primary edit-exam mr-1"
                                                                    data-id="{{ $timetable->id }}"
                                                                    data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                    data-course-code="{{ $timetable->course_code }}"
                                                                    data-exam-date="{{ $timetable->exam_date }}"
                                                                    data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                    data-venue-id="{{ $timetable->venue_id }}"
                                                                    data-group-selection="{{ $timetable->group_selection }}"
                                                                    data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                    title="Edit Exam">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-exam"
                                                                    data-id="{{ $timetable->id }}"
                                                                    title="Delete Exam">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    @else
                                                        @if (3 <= $program->total_years)
                                                            <button class="btn btn-sm btn-success create-exam"
                                                                    data-program-id="{{ $program->id }}"
                                                                    data-year-num="3"
                                                                    data-date="{{ $date }}"
                                                                    data-time-slot="{{ json_encode($slot) }}">+</button>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                    <tr>
                                        <td class="year-header">Year 4</td>
                                        @foreach ($dateChunk as $date)
                                            @foreach ($programs as $program)
                                                @php
                                                    $faculty = \App\Models\Faculty::where('program_id', $program->id)
                                                        ->where('name', 'LIKE', "% 4")
                                                        ->first();
                                                    $slotStartTime = Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i:s');
                                                    $slotEndTime = Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i:s');
                                                    $timetable = $timetables->firstWhere(function ($t) use ($faculty, $date, $slotStartTime, $slotEndTime) {
                                                        return $t->faculty_id == ($faculty ? $faculty->id : null) &&
                                                               $t->exam_date == $date &&
                                                               $t->start_time == $slotStartTime &&
                                                               $t->end_time == $slotEndTime;
                                                    });
                                                @endphp
                                                <td>
                                                    @if ($timetable)
                                                        <span class="badge badge-info p-2">{{ $timetable->course_code }} ({{ optional($timetable->venue)->name ?? 'N/A' }})</span>
                                                        <div class="mt-1">
                                                            <button class="btn btn-sm btn-info show-exam mr-1"
                                                                    data-id="{{ $timetable->id }}"
                                                                    data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                    data-course-code="{{ $timetable->course_code }}"
                                                                    data-exam-date="{{ $timetable->exam_date }}"
                                                                    data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                    data-venue-id="{{ $timetable->venue_id }}"
                                                                    data-group-selection="{{ $timetable->group_selection }}"
                                                                    data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                    title="Show Details">
                                                                <i class="fa fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-primary edit-exam mr-1"
                                                                    data-id="{{ $timetable->id }}"
                                                                    data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                    data-course-code="{{ $timetable->course_code }}"
                                                                    data-exam-date="{{ $timetable->exam_date }}"
                                                                    data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                    data-venue-id="{{ $timetable->venue_id }}"
                                                                    data-group-selection="{{ $timetable->group_selection }}"
                                                                    data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                    title="Edit Exam">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-exam"
                                                                    data-id="{{ $timetable->id }}"
                                                                    title="Delete Exam">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    @else
                                                        @if (4 <= $program->total_years)
                                                            <button class="btn btn-sm btn-success create-exam"
                                                                    data-program-id="{{ $program->id }}"
                                                                    data-year-num="4"
                                                                    data-date="{{ $date }}"
                                                                    data-time-slot="{{ json_encode($slot) }}">+</button>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-info">No examination setup found. Please create a setup to start scheduling exams.</div>
    @endif

    <!-- Setup Modal -->
    <div class="modal fade" id="setupModal" tabindex="-1" role="dialog" aria-labelledby="setupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="setupForm" action="{{ route('timetables.storeSetup') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="setupModalLabel">Examination Setup</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="type" class="form-label">Program Types</label>
                            <select class="form-control select2" id="type" name="type[]" multiple required>
                                <option value="Degree">Degree</option>
                                <option value="Non Degree">Non Degree</option>
                                <option value="Masters">Masters</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <select class="form-control" id="academic_year" name="academic_year" required>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year }}" {{ $year == '2024/2025' ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-control" id="semester" name="semester" required>
                                @foreach ($semesters as $sem)
                                    <option value="{{ $sem }}">{{ $sem }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                        <div class="form-group">
                            <label for="include_weekends" class="form-label">Include Weekends</label>
                            <input type="checkbox" id="include_weekends" name="include_weekends">
                        </div>
                        <div class="form-group">
                            <label for="programs" class="form-label">Programs</label>
                            <select class="form-control select2" id="programs" name="programs[]" multiple required>
                                @foreach ($allPrograms as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Time Slots</label>
                            <div id="timeSlots">
                                <div class="time-slot mb-2">
                                    <input type="text" class="form-control mb-1" name="time_slots[0][name]" placeholder="Slot Name" required>
                                    <input type="time" class="form-control mb-1" name="time_slots[0][start_time]" required>
                                    <input type="time" class="form-control" name="time_slots[0][end_time]" required>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="addTimeSlot()">Add Time Slot</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Setup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Setup Modal -->
    @if ($setup)
        <div class="modal fade" id="editSetupModal" tabindex="-1" role="dialog" aria-labelledby="editSetupModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="editSetupForm" action="{{ route('timetables.updateSetup', $setup->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title" id="editSetupModalLabel">Edit Examination Setup</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="edit_type" class="form-label">Program Types</label>
                                <select class="form-control select2" id="edit_type" name="type[]" multiple required>
                                    <option value="Degree" {{ in_array('Degree', $setup->type) ? 'selected' : '' }}>Degree</option>
                                    <option value="Non Degree" {{ in_array('Non Degree', $setup->type) ? 'selected' : '' }}>Non Degree</option>
                                    <option value="Masters" {{ in_array('Masters', $setup->type) ? 'selected' : '' }}>Masters</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_academic_year" class="form-label">Academic Year</label>
                                <select class="form-control" id="edit_academic_year" name="academic_year" required>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year }}" {{ $year == $setup->academic_year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_semester" class="form-label">Semester</label>
                                <select class="form-control" id="edit_semester" name="semester" required>
                                    @foreach ($semesters as $sem)
                                        <option value="{{ $sem }}" {{ $sem == $setup->semester ? 'selected' : '' }}>{{ $sem }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" value="{{ $setup->start_date }}" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" value="{{ $setup->end_date }}" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_include_weekends" class="form-label">Include Weekends</label>
                                <input type="checkbox" id="edit_include_weekends" name="include_weekends" {{ $setup->include_weekends ? 'checked' : '' }}>
                            </div>
                            <div class="form-group">
                                <label for="edit_programs" class="form-label">Programs</label>
                                <select class="form-control select2" id="edit_programs" name="programs[]" multiple required>
                                    @foreach ($allPrograms as $program)
                                        <option value="{{ $program->id }}" {{ in_array($program->id, $setup->programs) ? 'selected' : '' }}>{{ $program->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Time Slots</label>
                                <div id="editTimeSlots">
                                    @foreach ($setup->time_slots as $index => $slot)
                                        <div class="time-slot mb-2">
                                            <input type="text" class="form-control mb-1" name="time_slots[{{ $index }}][name]" value="{{ $slot['name'] }}" placeholder="Slot Name" required>
                                            <input type="time" class="form-control mb-1" name="time_slots[{{ $index }}][start_time]" value="{{ $slot['start_time'] }}" required>
                                            <input type="time" class="form-control" name="time_slots[{{ $index }}][end_time]" value="{{ $slot['end_time'] }}" required>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-secondary mt-2" onclick="addEditTimeSlot()">Add Time Slot</button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update Setup</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Create/Edit Exam Modal -->
    <div class="modal fade" id="examModal" tabindex="-1" role="dialog" aria-labelledby="examModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="examForm" method="POST">
                    @csrf
                    <input type="hidden" id="examId" name="exam_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="examModalLabel">Create Exam</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="faculty_id" id="faculty_id">
                        @if ($setup)
                            <div class="form-group">
                                <label for="exam_date" class="form-label">Exam Date</label>
                                <select class="form-control" id="exam_date" name="exam_date" required>
                                    @foreach ($days as $day)
                                        @php
                                            $carbonDay = Carbon::parse($day);
                                            $displayDay = $carbonDay->format('d-m') . ' (' . $carbonDay->format('l') . ')';
                                        @endphp
                                        <option value="{{ $day }}">{{ $displayDay }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="time_slot" class="form-label">Time Slot</label>
                                <select class="form-control" id="time_slot" name="time_slot" required>
                                    @foreach ($timeSlots as $slot)
                                        <option value="{{ json_encode(['name' => $slot['name'], 'start_time' => Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i')]) }}">
                                            {{ $slot['name'] }} ({{ $slot['start_time'] }} - {{ $slot['end_time'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="alert alert-warning">Please create an examination setup first.</div>
                        @endif
                        <div class="form-group">
                            <label for="course_code" class="form-label">Course</label>
                            <select class="form-control" id="course_code" name="course_code" required>
                                <option value="">Select Course</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="venue_id" class="form-label">Venue</label>
                            <select class="form-control" id="venue_id" name="venue_id" required>
                                @foreach ($venues as $venue)
                                    <option value="{{ $venue->id }}">{{ $venue->name }} (Capacity: {{ $venue->capacity }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="group_selection" class="form-label">Groups</label>
                            <select class="form-control select2" id="group_selection" name="group_selection[]" multiple required>
                                <option value="All Groups">All Groups</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lecturer_ids" class="form-label">Lecturers</label>
                            <select class="form-control select2" id="lecturer_ids" name="lecturer_ids[]" multiple required>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        @if ($setup)
                            <button type="submit" class="btn btn-primary">Save Exam</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this exam timetable entry?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Show Exam Modal -->
    <div class="modal fade" id="showExamModal" tabindex="-1" role="dialog" aria-labelledby="showExamModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showExamModalLabel">Exam Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Course Code:</strong> <span id="show_exam_course_code"></span></p>
                    <p><strong>Course Name:</strong> <span id="show_exam_course_name"></span></p>
                    <p><strong>Exam Date:</strong> <span id="show_exam_date"></span></p>
                    <p><strong>Time Slot:</strong> <span id="show_exam_time_slot"></span></p>
                    <p><strong>Venue:</strong> <span id="show_exam_venue"></span> (Capacity: <span id="show_exam_venue_capacity"></span>)</p>
                    <p><strong>Groups:</strong> <span id="show_exam_group_selection"></span></p>
                    <p><strong>Lecturers:</strong> <span id="show_exam_lecturers"></span></p>
                    <p><strong>Faculty:</strong> <span id="show_exam_faculty"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script>
        var $j = jQuery.noConflict();
        const showUrlTemplate = '{{ route('timetables.show', ['timetable' => 'ID']) }}';

        $j(document).ready(function() {
            $j('.select2').select2({
                placeholder: "Select options",
                allowClear: true,
                width: '100%'
            });

            $j('.create-exam').click(function() {
                @if (!$setup)
                    alert('Please create an examination setup first.');
                    return;
                @endif

                const programId = $j(this).data('program-id');
                const yearNum = $j(this).data('year-num');
                const date = $j(this).data('date');
                const timeSlot = $j(this).data('time-slot');

                console.log('Create exam clicked:', { programId, yearNum, date, timeSlot });

                $j.get(`{{ route('timetables.getFacultyByProgramYear', ['program_id' => ':programId', 'year_num' => ':yearNum']) }}`
                    .replace(':programId', programId)
                    .replace(':yearNum', yearNum), function(faculty) {
                        if (!faculty || !faculty.id) {
                            alert('Invalid year for this program.');
                            return;
                        }

                        $j('#examModalLabel').text('Create Exam');
                        $j('#examForm').attr('action', '{{ route('timetables.store') }}').find('[name="_method"]').remove();
                        $j('#examId').val('');
                        $j('#faculty_id').val(faculty.id);
                        $j('#exam_date').val(date);
                        const slotData = typeof timeSlot === 'string' ? JSON.parse(timeSlot) : timeSlot;
                        $j('#time_slot').val(JSON.stringify(slotData));
                        $j('#examForm').find('[name="start_time"], [name="end_time"]').remove();
                        $j('<input>').attr({
                            type: 'hidden',
                            name: 'start_time',
                            value: slotData.start_time + ':00' // Ensure H:i:s
                        }).appendTo('#examForm');
                        $j('<input>').attr({
                            type: 'hidden',
                            name: 'end_time',
                            value: slotData.end_time + ':00' // Ensure H:i:s
                        }).appendTo('#examForm');

                        $j.get(`{{ route('timetables.getFacultyCourses') }}?faculty_id=${faculty.id}`, function(data) {
                            console.log('Courses fetched:', data);
                            $j('#course_code').empty().append('<option value="">Select Course</option>');
                            data.course_codes.forEach(course => {
                                $j('#course_code').append(`<option value="${course.course_code}">${course.name}</option>`);
                            });
                        }).fail(function(xhr) {
                            console.error('Error fetching courses:', xhr.responseText);
                            alert('Failed to load courses.');
                        });

                        $j.get(`{{ route('timetables.getFacultyGroups') }}?faculty_id=${faculty.id}`, function(data) {
                            console.log('Groups fetched:', data);
                            $j('#group_selection').empty().append('<option value="All Groups">All Groups</option>');
                            data.groups.forEach(group => {
                                $j('#group_selection').append(`<option value="${group.group_name}">${group.group_name}</option>`);
                            });
                            $j('#group_selection').trigger('change');
                        }).fail(function(xhr) {
                            console.error('Error fetching groups:', xhr.responseText);
                            alert('Failed to load groups.');
                        });

                        $j('#course_code').off('change').on('change', function() {
                            const courseCode = $j(this).val();
                            console.log('Course selected:', courseCode);
                            if (courseCode) {
                                $j.get(`{{ route('timetables.getCourseLecturers') }}?course_code=${courseCode}`, function(data) {
                                    console.log('Lecturers fetched:', data);
                                    $j('#lecturer_ids').empty();
                                    data.lecturers.forEach(lecturer => {
                                        $j('#lecturer_ids').append(`<option value="${lecturer.id}">${lecturer.name}</option>`);
                                    });
                                    $j('#lecturer_ids').trigger('change');
                                }).fail(function(xhr) {
                                    console.error('Error fetching lecturers:', xhr.responseText);
                                    alert('Failed to load lecturers.');
                                });
                            } else {
                                $j('#lecturer_ids').empty().trigger('change');
                            }
                        });

                        $j('#examModal').modal('show');
                    }).fail(function(xhr) {
                        console.error('Error fetching faculty:', xhr.responseText);
                        alert('Failed to load faculty data.');
                    });
            });

            $j('.edit-exam').click(function() {
                const id = $j(this).data('id');
                const facultyId = $j(this).data('faculty-id');
                const courseCode = $j(this).data('course-code');
                const examDate = $j(this).data('exam-date');
                const timeSlot = $j(this).data('time-slot');
                const venueId = $j(this).data('venue-id');
                const groupSelection = $j(this).data('group-selection');
                const lecturerIds = typeof $j(this).data('lecturer-ids') === 'string' ? JSON.parse($j(this).data('lecturer-ids')) : $j(this).data('lecturer-ids');

                console.log('Edit exam - Timetable ID:', id, 'Course:', courseCode, 'Lecturer IDs:', lecturerIds, 'Time Slot:', timeSlot);

                $j('#examModalLabel').text('Edit Exam');
                $j('#examForm').attr('action', `{{ url('timetables') }}/${id}`).find('[name="_method"]').remove()
                    .end().append('<input type="hidden" name="_method" value="PUT">');
                $j('#examId').val(id);
                $j('#faculty_id').val(facultyId);
                $j('#exam_date').val(examDate);
                const timeSlotParsed = typeof timeSlot === 'string' ? JSON.parse(timeSlot) : timeSlot;
                $j('#time_slot').val(JSON.stringify(timeSlotParsed));
                $j('#venue_id').val(venueId);

                $j('#examForm').find('[name="start_time"], [name="end_time"]').remove();
                $j('<input>').attr({
                    type: 'hidden',
                    name: 'start_time',
                    value: timeSlotParsed.start_time + ':00' // Ensure H:i:s
                }).appendTo('#examForm');
                $j('<input>').attr({
                    type: 'hidden',
                    name: 'end_time',
                    value: timeSlotParsed.end_time + ':00' // Ensure H:i:s
                }).appendTo('#examForm');

                $j.get(`{{ route('timetables.getFacultyCourses') }}?faculty_id=${facultyId}`, function(data) {
                    console.log('Edit exam - Courses:', data);
                    $j('#course_code').empty().append('<option value="">Select Course</option>');
                    data.course_codes.forEach(course => {
                        $j('#course_code').append(`<option value="${course.course_code}" ${course.course_code === courseCode ? 'selected' : ''}>${course.name}</option>`);
                    });
                }).fail(function(xhr) {
                    console.error('Error fetching courses:', xhr.status, xhr.responseText);
                    alert('Failed to load courses.');
                });

                $j.get(`{{ route('timetables.getFacultyGroups') }}?faculty_id=${facultyId}`, function(data) {
                    console.log('Edit exam - Groups:', data);
                    $j('#group_selection').empty().append('<option value="All Groups">All Groups</option>');
                    data.groups.forEach(group => {
                        const isSelected = groupSelection && (groupSelection.split(',').includes(group.group_name) || groupSelection === group.group_name);
                        $j('#group_selection').append(`<option value="${group.group_name}" ${isSelected ? 'selected' : ''}>${group.group_name}</option>`);
                    });
                    $j('#group_selection').select2({
                        placeholder: "Select groups",
                        allowClear: true,
                        width: '100%'
                    }).trigger('change');
                }).fail(function(xhr) {
                    console.error('Error fetching groups:', xhr.status, xhr.responseText);
                    alert('Failed to load groups.');
                });

                $j.get(`{{ route('timetables.getCourseLecturers') }}?course_code=${courseCode}&timetable_id=${id}`, function(data) {
                    console.log('Edit exam - Lecturers:', data);
                    $j('#lecturer_ids').empty();
                    data.lecturers.forEach(lecturer => {
                        $j('#lecturer_ids').append(`<option value="${lecturer.id}" ${lecturer.selected || (lecturerIds && lecturerIds.includes(lecturer.id)) ? 'selected' : ''}>${lecturer.name}</option>`);
                    });
                    $j('#lecturer_ids').select2({
                        placeholder: "Select lecturers",
                        allowClear: true,
                        width: '100%'
                    }).trigger('change');
                }).fail(function(xhr) {
                    console.error('Error fetching lecturers:', xhr.status, xhr.responseText);
                    alert('Failed to load lecturers.');
                });

                $j('#course_code').off('change').on('change', function() {
                    const courseCode = $j(this).val();
                    console.log('Edit exam - Course changed:', courseCode);
                    if (courseCode) {
                        $j.get(`{{ route('timetables.getCourseLecturers') }}?course_code=${courseCode}&timetable_id=${id}`, function(data) {
                            console.log('Edit exam - Lecturers:', data);
                            $j('#lecturer_ids').empty();
                            data.lecturers.forEach(lecturer => {
                                $j('#lecturer_ids').append(`<option value="${lecturer.id}" ${lecturer.selected ? 'selected' : ''}>${lecturer.name}</option>`);
                            });
                            $j('#lecturer_ids').select2({
                                placeholder: "Select lecturers",
                                allowClear: true,
                                width: '100%'
                            }).trigger('change');
                        }).fail(function(xhr) {
                            console.error('Error fetching lecturers:', xhr.status, xhr.responseText);
                            alert('Failed to load lecturers.');
                        });
                    } else {
                        $j('#lecturer_ids').empty().select2({
                            placeholder: "Select lecturers",
                            allowClear: true,
                            width: '100%'
                        }).trigger('change');
                    }
                });

                $j('#examModal').modal('show');
            });

            $j('#examForm').on('submit', function(e) {
                e.preventDefault();
                const form = $j(this);
                const url = form.attr('action');
                const method = form.find('[name="_method"]').val() || 'POST';
                const data = form.serialize();

                console.log('Submitting form:', { url, method, data });

                $j.ajax({
                    url: url,
                    type: method,
                    data: data,
                    success: function(response) {
                        console.log('Form submission successful:', response);
                        $j('#examModal').modal('hide');
                        location.reload();
                    },
                    error: function(xhr) {
                        console.error('Form submission error:', xhr.status, xhr.responseText);
                        let errorMsg = xhr.responseJSON?.error || 'Unknown error';
                        if (xhr.responseJSON?.details) {
                            errorMsg += '\nDetails: ' + Object.values(xhr.responseJSON.details).flat().join('; ');
                        }
                        alert('Failed to save exam: ' + errorMsg);
                    }
                });
            });

            $j('.delete-exam').click(function() {
                const id = $j(this).data('id');
                console.log('Delete exam clicked:', id);
                $j('#deleteForm').attr('action', `{{ url('timetables') }}/${id}`);
                $j('#deleteModal').modal('show');
            });

            $j('.show-exam').click(function() {
                const id = $j(this).data('id');
                const lecturerIds = $j(this).data('lecturer-ids');
                console.log('Show exam - Timetable ID:', id, 'Lecturer IDs:', lecturerIds);
                $j.get(showUrlTemplate.replace('ID', id), function(data) {
                    console.log('Show exam - Response:', data);
                    const lecturerText = data.lecturers && data.lecturers.length ? data.lecturers.join(', ') : 'No lecturers assigned';
                    console.log('Show exam - Lecturer Text:', lecturerText);
                    $j('#show_exam_lecturers').text(lecturerText);
                    $j('#show_exam_course_code').text(data.course_code || 'N/A');
                    $j('#show_exam_course_name').text(data.course_name || 'N/A');
                    $j('#show_exam_date').text(data.exam_date ? moment(data.exam_date).format('DD-MM-YYYY (dddd)') : 'N/A');
                    $j('#show_exam_time_slot').text(data.start_time && data.end_time ? `${data.start_time} - ${data.end_time} (${data.time_slot_name || 'N/A'})` : 'N/A');
                    $j('#show_exam_venue').text(data.venue_name || 'N/A');
                    $j('#show_exam_venue_capacity').text(data.venue_capacity || 'N/A');
                    $j('#show_exam_group_selection').text(data.group_selection || 'N/A');
                    $j('#show_exam_faculty').text(data.faculty_name || 'N/A');
                    $j('#showExamModal').modal('show');
                }).fail(function(xhr) {
                    console.error('Show exam - Error:', xhr.status, xhr.responseText);
                    alert('Failed to load exam details.');
                });
            });

            let timeSlotIndex = 1;
            window.addTimeSlot = function() {
                const timeSlotHtml = `
                    <div class="time-slot mb-2">
                        <input type="text" class="form-control mb-1" name="time_slots[${timeSlotIndex}][name]" placeholder="Slot Name" required>
                        <input type="time" class="form-control mb-1" name="time_slots[${timeSlotIndex}][start_time]" required>
                        <input type="time" class="form-control" name="time_slots[${timeSlotIndex}][end_time]" required>
                    </div>`;
                $j('#timeSlots').append(timeSlotHtml);
                timeSlotIndex++;
            };

            let editTimeSlotIndex = {{ $setup ? count($setup->time_slots) : 0 }};
            window.addEditTimeSlot = function() {
                const timeSlotHtml = `
                    <div class="time-slot mb-2">
                        <input type="text" class="form-control mb-1" name="time_slots[${editTimeSlotIndex}][name]" placeholder="Slot Name" required>
                        <input type="time" class="form-control mb-1" name="time_slots[${editTimeSlotIndex}][start_time]" required>
                        <input type="time" class="form-control" name="time_slots[${editTimeSlotIndex}][end_time]" required>
                    </div>`;
                $j('#editTimeSlots').append(timeSlotHtml);
                editTimeSlotIndex++;
            };
        });
    </script>
@endsection

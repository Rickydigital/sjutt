@php
    use Carbon\Carbon;
@endphp

@extends('components.app-main-layout')

@section('styles')
<style>
    body {
        background-color: #f4f6f9;
    }

    .exam-table th, .exam-table td {
        border: 2px solid #dee2e6 !important;
        vertical-align: middle;
        text-align: center;
        min-width: 150px;
    }

    .exam-table th {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 3;
    }

    .exam-table .time-slot-header {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        position: sticky;
        left: 0;
        z-index: 4;
    }

    .exam-table .year-header {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        position: sticky;
        left: 150px;
        z-index: 4;
    }

    .exam-table caption {
        caption-side: top;
        font-size: 1.25rem;
        font-weight: bold;
        padding: 10px;
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        border-radius: 10px 10px 0 0;
    }

    .exam-table .empty-cell {
        height: 100px;
        background-color: #f8f9fa;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .exam-table .empty-cell:hover {
        background-color: #e9ecef;
    }

    .exam-table .course-cell {
        background: linear-gradient(135deg, #e2e8f0, #f8f9fa);
        transition: transform 0.2s;
    }

    .exam-table .course-cell:hover {
        transform: scale(1.02);
    }

    .action-icon {
        margin: 0 5px;
        font-size: 1.2rem;
        transition: color 0.3s;
    }

    .action-icon:hover {
        color: #007bff !important;
    }

    .card-header {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        border-radius: 10px 10px 0 0;
    }

    .modal-content {
        border-radius: 15px;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        border-radius: 15px 15px 0 0;
    }

    .form-control, .select2-container--classic .select2-selection--single,
    .select2-container--classic .select2-selection--multiple {
        border-radius: 8px;
        border: 1px solid #ced4da;
        transition: border-color 0.3s;
    }

    .form-control:focus, .select2-container--classic .select2-selection--single:focus,
    .select2-container--classic .select2-selection--multiple:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 5px rgba(111, 66, 193, 0.5);
    }

    .btn-primary {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        border: none;
        border-radius: 8px;
        transition: background 0.3s;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #4B2E83, #6f42c1);
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border: none;
        border-radius: 8px;
        transition: background 0.3s;
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #1e7e34, #28a745);
    }

    .btn-outline-danger {
        border-radius: 8px;
    }

    .select-all-option {
        font-weight: bold;
        background-color: #f0f0f0;
    }

    .alert-info {
        border-radius: 8px;
        background: linear-gradient(135deg, #d1e7ff, #e9f2ff);
        color: #004085;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .select2-container--classic .select2-selection--single .select2-selection__rendered {
        color: #333;
        font-weight: 500;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 8px;
        padding: 8px;
    }

    .select2-container--classic .select2-selection--single .select2-selection__rendered .select2-selection__choice {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .select2-container--classic .select2-results__option--highlighted {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
    }

    .select2-container--classic .select2-results__option--selected {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        font-weight: 600;
    }

    .select2-container--classic .select2-selection--single:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 5px rgba(111, 66, 193, 0.5);
    }
</style>
@endsection

@section('content')
<div class="container">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="fw-bold" style="color: #4B2E83;">
                <i class="fas fa-calendar-alt me-2"></i> Examination Timetables
            </h1>
            <div>
                @if ($setup)
                    <a href="{{ route('timetables.pdf', ['exam_type' => $selectedType]) }}" class="btn btn-success me-2">
                        <i class="fas fa-download me-1"></i> Download PDF
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editSetupModal">
                        <i class="fas fa-edit me-1"></i> Edit Setup
                    </button>
                @else
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#setupModal">
                        <i class="fas fa-plus me-1"></i> Create Setup
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Exam Type Filter -->
    @if ($setups->isNotEmpty())
        <div class="row mb-4">
            <div class="col-12 col-md-6 col-lg-4">
                <form method="GET" action="{{ route('timetables.index') }}" id="examTypeFilterForm">
                    <div class="mb-3">
                        <label for="exam_type" class="form-label fw-semibold">Select Exam Type</label>
                        <select class="form-control select2" id="exam_type" name="exam_type">
                            <option value="">Select Exam Type</option>
                            @foreach ($examTypes as $type)
                                <option value="{{ $type }}" {{ $selectedType == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Examination Timetable -->
    @if ($setup)
        @php
            $datesPerPage = 5;
            $dateChunks = array_chunk($days, $datesPerPage);
        @endphp
        @foreach ($dateChunks as $chunkIndex => $dateChunk)
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Type: {{ $selectedType }} ({{ $setup->academic_year }}) - Semester: {{ $setup->semester }}
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table exam-table mb-0">
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
                                                        <td class="{{ $timetable ? 'course-cell' : 'empty-cell' }}">
                                                            @if ($timetable)
                                                                <p class="fw-bold mb-1">{{ $timetable->course_code }}</p>
                                                                <p class="mb-2">{{ optional($timetable->venue)->name ?? 'N/A' }}</p>
                                                                <div class="d-flex justify-content-center flex-nowrap">
                                                                    <a href="#" class="action-icon show-exam"
                                                                       data-id="{{ $timetable->id }}"
                                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                                       title="Show Details">
                                                                        <i class="bi bi-eye-fill text-primary"></i>
                                                                    </a>
                                                                    <a href="#" class="action-icon edit-exam"
                                                                       data-id="{{ $timetable->id }}"
                                                                       data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                       data-course-code="{{ $timetable->course_code }}"
                                                                       data-exam-date="{{ $timetable->exam_date }}"
                                                                       data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                       data-venue-id="{{ $timetable->venue_id }}"
                                                                       data-group-selection="{{ $timetable->group_selection }}"
                                                                       data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                                       title="Edit Exam">
                                                                        <i class="bi bi-pencil-square text-primary"></i>
                                                                    </a>
                                                                    <form action="{{ route('timetables.destroy', $timetable->id) }}"
                                                                          method="POST" style="display:inline;"
                                                                          class="delete-exam-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="action-icon"
                                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                                title="Delete Exam">
                                                                            <i class="bi bi-trash text-danger"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            @else
                                                                @if (1 <= $program->total_years)
                                                                    <a href="#" class="create-exam"
                                                                       data-program-id="{{ $program->id }}"
                                                                       data-year-num="1"
                                                                       data-date="{{ $date }}"
                                                                       data-time-slot="{{ json_encode($slot) }}">
                                                                        <i class="bi bi-plus-circle fs-5"></i>
                                                                    </a>
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
                                                        <td class="{{ $timetable ? 'course-cell' : 'empty-cell' }}">
                                                            @if ($timetable)
                                                                <p class="fw-bold mb-1">{{ $timetable->course_code }}</p>
                                                                <p class="mb-2">{{ optional($timetable->venue)->name ?? 'N/A' }}</p>
                                                                <div class="d-flex justify-content-center flex-nowrap">
                                                                    <a href="#" class="action-icon show-exam"
                                                                       data-id="{{ $timetable->id }}"
                                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                                       title="Show Details">
                                                                        <i class="bi bi-eye-fill text-primary"></i>
                                                                    </a>
                                                                    <a href="#" class="action-icon edit-exam"
                                                                       data-id="{{ $timetable->id }}"
                                                                       data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                       data-course-code="{{ $timetable->course_code }}"
                                                                       data-exam-date="{{ $timetable->exam_date }}"
                                                                       data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                       data-venue-id="{{ $timetable->venue_id }}"
                                                                       data-group-selection="{{ $timetable->group_selection }}"
                                                                       data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                                       title="Edit Exam">
                                                                        <i class="bi bi-pencil-square text-primary"></i>
                                                                    </a>
                                                                    <form action="{{ route('timetables.destroy', $timetable->id) }}"
                                                                          method="POST" style="display:inline;"
                                                                          class="delete-exam-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="action-icon"
                                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                                title="Delete Exam">
                                                                            <i class="bi bi-trash text-danger"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            @else
                                                                @if (2 <= $program->total_years)
                                                                    <a href="#" class="create-exam"
                                                                       data-program-id="{{ $program->id }}"
                                                                       data-year-num="2"
                                                                       data-date="{{ $date }}"
                                                                       data-time-slot="{{ json_encode($slot) }}">
                                                                        <i class="bi bi-plus-circle fs-5"></i>
                                                                    </a>
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
                                                        <td class="{{ $timetable ? 'course-cell' : 'empty-cell' }}">
                                                            @if ($timetable)
                                                                <p class="fw-bold mb-1">{{ $timetable->course_code }}</p>
                                                                <p class="mb-2">{{ optional($timetable->venue)->name ?? 'N/A' }}</p>
                                                                <div class="d-flex justify-content-center flex-nowrap">
                                                                    <a href="#" class="action-icon show-exam"
                                                                       data-id="{{ $timetable->id }}"
                                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                                       title="Show Details">
                                                                        <i class="bi bi-eye-fill text-primary"></i>
                                                                    </a>
                                                                    <a href="#" class="action-icon edit-exam"
                                                                       data-id="{{ $timetable->id }}"
                                                                       data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                       data-course-code="{{ $timetable->course_code }}"
                                                                       data-exam-date="{{ $timetable->exam_date }}"
                                                                       data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                       data-venue-id="{{ $timetable->venue_id }}"
                                                                       data-group-selection="{{ $timetable->group_selection }}"
                                                                       data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                                       title="Edit Exam">
                                                                        <i class="bi bi-pencil-square text-primary"></i>
                                                                    </a>
                                                                    <form action="{{ route('timetables.destroy', $timetable->id) }}"
                                                                          method="POST" style="display:inline;"
                                                                          class="delete-exam-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="action-icon"
                                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                                title="Delete Exam">
                                                                            <i class="bi bi-trash text-danger"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            @else
                                                                @if (3 <= $program->total_years)
                                                                    <a href="#" class="create-exam"
                                                                       data-program-id="{{ $program->id }}"
                                                                       data-year-num="3"
                                                                       data-date="{{ $date }}"
                                                                       data-time-slot="{{ json_encode($slot) }}">
                                                                        <i class="bi bi-plus-circle fs-5"></i>
                                                                    </a>
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
                                                        <td class="{{ $timetable ? 'course-cell' : 'empty-cell' }}">
                                                            @if ($timetable)
                                                                <p class="fw-bold mb-1">{{ $timetable->course_code }}</p>
                                                                <p class="mb-2">{{ optional($timetable->venue)->name ?? 'N/A' }}</p>
                                                                <div class="d-flex justify-content-center flex-nowrap">
                                                                    <a href="#" class="action-icon show-exam"
                                                                       data-id="{{ $timetable->id }}"
                                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                                       title="Show Details">
                                                                        <i class="bi bi-eye-fill text-primary"></i>
                                                                    </a>
                                                                    <a href="#" class="action-icon edit-exam"
                                                                       data-id="{{ $timetable->id }}"
                                                                       data-faculty-id="{{ $faculty ? $faculty->id : '' }}"
                                                                       data-course-code="{{ $timetable->course_code }}"
                                                                       data-exam-date="{{ $timetable->exam_date }}"
                                                                       data-time-slot="{{ json_encode(['start_time' => Carbon::createFromFormat('H:i:s', $timetable->start_time)->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i:s', $timetable->end_time)->format('H:i'), 'name' => $slot['name']]) }}"
                                                                       data-venue-id="{{ $timetable->venue_id }}"
                                                                       data-group-selection="{{ $timetable->group_selection }}"
                                                                       data-lecturer-ids="{{ json_encode($timetable->lecturers->pluck('id')->toArray()) }}"
                                                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                                                       title="Edit Exam">
                                                                        <i class="bi bi-pencil-square text-primary"></i>
                                                                    </a>
                                                                    <form action="{{ route('timetables.destroy', $timetable->id) }}"
                                                                          method="POST" style="display:inline;"
                                                                          class="delete-exam-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="action-icon"
                                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                                title="Delete Exam">
                                                                            <i class="bi bi-trash text-danger"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            @else
                                                                @if (4 <= $program->total_years)
                                                                    <a href="#" class="create-exam"
                                                                       data-program-id="{{ $program->id }}"
                                                                       data-year-num="4"
                                                                       data-date="{{ $date }}"
                                                                       data-time-slot="{{ json_encode($slot) }}">
                                                                        <i class="bi bi-plus-circle fs-5"></i>
                                                                    </a>
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
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-info">
            No examination setup found. Please create a setup to start scheduling exams.
        </div>
    @endif

    <!-- Setup Modal -->
    <div class="modal fade" id="setupModal" tabindex="-1" aria-labelledby="setupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="setupForm" action="{{ route('timetables.storeSetup') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="setupModalLabel">Examination Setup</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Program Types <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="type" name="type[]" multiple required>
                                    <option value="Degree">Degree</option>
                                    <option value="Non Degree">Non Degree</option>
                                    <option value="Masters">Masters</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="academic_year" name="academic_year" required>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year }}" {{ $year == '2024/2025' ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="semester" name="semester" required>
                                    @foreach ($semesters as $sem)
                                        <option value="{{ $sem }}">{{ $sem }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="include_weekends" class="form-label">Include Weekends</label>
                                <input type="checkbox" id="include_weekends" name="include_weekends" class="form-check-input">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="programs" class="form-label">Programs <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="programs" name="programs[]" multiple required>
                                    @foreach ($allPrograms as $program)
                                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Time Slots <span class="text-danger">*</span></label>
                                <div id="timeSlots">
                                    <div class="time-slot mb-2">
                                        <input type="text" class="form-control mb-1" name="time_slots[0][name]" placeholder="Slot Name" required>
                                        <input type="time" class="form-control mb-1" name="time_slots[0][start_time]" required>
                                        <input type="time" class="form-control" name="time_slots[0][end_time]" required>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary mt-2" onclick="addTimeSlot()">Add Time Slot</button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Setup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Setup Modal -->
    @if ($setup)
        <div class="modal fade" id="editSetupModal" tabindex="-1" aria-labelledby="editSetupModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="editSetupForm" action="{{ route('timetables.updateSetup', $setup->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title" id="editSetupModalLabel">Edit Examination Setup</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_type" class="form-label">Program Types <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="edit_type" name="type[]" multiple required>
                                        <option value="Degree" {{ in_array('Degree', $setup->type) ? 'selected' : '' }}>Degree</option>
                                        <option value="Non Degree" {{ in_array('Non Degree', $setup->type) ? 'selected' : '' }}>Non Degree</option>
                                        <option value="Masters" {{ in_array('Masters', $setup->type) ? 'selected' : '' }}>Masters</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="edit_academic_year" name="academic_year" required>
                                        @foreach ($academicYears as $year)
                                            <option value="{{ $year }}" {{ $year == $setup->academic_year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_semester" class="form-label">Semester <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="edit_semester" name="semester" required>
                                        @foreach ($semesters as $sem)
                                            <option value="{{ $sem }}" {{ $sem == $setup->semester ? 'selected' : '' }}>{{ $sem }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="edit_start_date" name="start_date" value="{{ $setup->start_date }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="edit_end_date" name="end_date" value="{{ $setup->end_date }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_include_weekends" class="form-label">Include Weekends</label>
                                    <input type="checkbox" id="edit_include_weekends" name="include_weekends" class="form-check-input" {{ $setup->include_weekends ? 'checked' : '' }}>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="edit_programs" class="form-label">Programs <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="edit_programs" name="programs[]" multiple required>
                                        @foreach ($allPrograms as $program)
                                            <option value="{{ $program->id }}" {{ in_array($program->id, $setup->programs) ? 'selected' : '' }}>{{ $program->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Time Slots <span class="text-danger">*</span></label>
                                    <div id="editTimeSlots">
                                        @foreach ($setup->time_slots as $index => $slot)
                                            <div class="time-slot mb-2">
                                                <input type="text" class="form-control mb-1" name="time_slots[{{ $index }}][name]" value="{{ $slot['name'] }}" placeholder="Slot Name" required>
                                                <input type="time" class="form-control mb-1" name="time_slots[{{ $index }}][start_time]" value="{{ $slot['start_time'] }}" required>
                                                <input type="time" class="form-control" name="time_slots[{{ $index }}][end_time]" value="{{ $slot['end_time'] }}" required>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-primary mt-2" onclick="addEditTimeSlot()">Add Time Slot</button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update Setup</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Create/Edit Exam Modal -->
    <div class="modal fade" id="examModal" tabindex="-1" aria-labelledby="examModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
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
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="exam_date" class="form-label">Exam Date <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="exam_date" name="exam_date" required>
                                        @foreach ($days as $day)
                                            @php
                                                $carbonDay = Carbon::parse($day);
                                                $displayDay = $carbonDay->format('d-m') . ' (' . $carbonDay->format('l') . ')';
                                            @endphp
                                            <option value="{{ $day }}">{{ $displayDay }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="time_slot" class="form-label">Time Slot <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="time_slot" name="time_slot" required>
                                        @foreach ($timeSlots as $slot)
                                            <option value="{{ json_encode(['name' => $slot['name'], 'start_time' => Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i'), 'end_time' => Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i')]) }}">
                                                {{ $slot['name'] }} ({{ $slot['start_time'] }} - {{ $slot['end_time'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="course_code" class="form-label">Course <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="course_code" name="course_code" required>
                                        <option value="">Select Course</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="venue_id" class="form-label">Venue <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="venue_id" name="venue_id" required>
                                        @foreach ($venues as $venue)
                                            <option value="{{ $venue->id }}" data-capacity="{{ $venue->capacity }}">
                                                {{ $venue->name }} (Capacity: {{ $venue->capacity }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="group_selection" class="form-label">Groups <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="group_selection" name="group_selection[]" multiple required>
                                        <option value="" disabled>Select Groups</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lecturer_ids" class="form-label">Lecturers <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="lecturer_ids" name="lecturer_ids[]" multiple required>
                                        <option value="" disabled>Select Lecturers</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Exam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Show Exam Modal -->
    <div class="modal fade" id="showExamModal" tabindex="-1" aria-labelledby="showExamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showExamModalLabel">Exam Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Course Code</label>
                            <p id="show_course_code"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Course Name</label>
                            <p id="show_course_name"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Exam Date</label>
                            <p id="show_exam_date"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Time Slot</label>
                            <p id="show_time_slot"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Venue</label>
                            <p id="show_venue_name"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Venue Capacity</label>
                            <p id="show_venue_capacity"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Groups</label>
                            <p id="show_group_selection"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Lecturers</label>
                            <p id="show_lecturers"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Faculty</label>
                            <p id="show_faculty_name"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2 with custom template for course_code
        const initializeSelect2 = (selector, modalId = null) => {
            const options = {
                dropdownParent: modalId ? $(modalId) : null,
                theme: 'classic',
                placeholder: 'Select an option',
                allowClear: false,
                width: '100%'
            };

            if (selector === '#course_code') {
                options.templateResult = function(data) {
                    if (!data.id) return data.text;
                    return $('<span style="font-weight: 500;">' + data.text + '</span>');
                };
                options.templateSelection = function(data) {
                    return $('<span style="font-weight: 600; color: #4B2E83;">' + data.text + '</span>');
                };
            }

            $(selector).select2(options);
        };

        // Initialize Select2 for all dropdowns
        initializeSelect2('#exam_type');
        initializeSelect2('#type', '#setupModal');
        initializeSelect2('#academic_year', '#setupModal');
        initializeSelect2('#semester', '#setupModal');
        initializeSelect2('#programs', '#setupModal');
        initializeSelect2('#edit_type', '#editSetupModal');
        initializeSelect2('#edit_academic_year', '#editSetupModal');
        initializeSelect2('#edit_semester', '#editSetupModal');
        initializeSelect2('#edit_programs', '#editSetupModal');
        initializeSelect2('#exam_date', '#examModal');
        initializeSelect2('#time_slot', '#examModal');
        initializeSelect2('#course_code', '#examModal');
        initializeSelect2('#venue_id', '#examModal');
        initializeSelect2('#group_selection', '#examModal');
        initializeSelect2('#lecturer_ids', '#examModal');

        // Show Alert Function
        function showAlert(type, title, message) {
            Swal.fire({
                icon: type,
                title: title,
                text: message,
                timer: type === 'success' ? 1500 : undefined,
                showConfirmButton: type !== 'success',
            });
        }

        // Add Time Slot
        let timeSlotIndex = 1;
        window.addTimeSlot = function() {
            const timeSlotHtml = `
                <div class="time-slot mb-2">
                    <input type="text" class="form-control mb-1" name="time_slots[${timeSlotIndex}][name]" placeholder="Slot Name" required>
                    <input type="time" class="form-control mb-1" name="time_slots[${timeSlotIndex}][start_time]" required>
                    <input type="time" class="form-control" name="time_slots[${timeSlotIndex}][end_time]" required>
                    <button type="button" class="btn btn-outline-danger btn-sm mt-1" onclick="removeTimeSlot(this)">Remove</button>
                </div>`;
            $('#timeSlots').append(timeSlotHtml);
            timeSlotIndex++;
        };

        // Add Edit Time Slot
        let editTimeSlotIndex = {{ $setup ? count($setup->time_slots) : 1 }};
        window.addEditTimeSlot = function() {
            const timeSlotHtml = `
                <div class="time-slot mb-2">
                    <input type="text" class="form-control mb-1" name="time_slots[${editTimeSlotIndex}][name]" placeholder="Slot Name" required>
                    <input type="time" class="form-control mb-1" name="time_slots[${editTimeSlotIndex}][start_time]" required>
                    <input type="time" class="form-control" name="time_slots[${editTimeSlotIndex}][end_time]" required>
                    <button type="button" class="btn btn-outline-danger btn-sm mt-1" onclick="removeTimeSlot(this)">Remove</button>
                </div>`;
            $('#editTimeSlots').append(timeSlotHtml);
            editTimeSlotIndex++;
        };

        // Remove Time Slot
        window.removeTimeSlot = function(button) {
            $(button).closest('.time-slot').remove();
        };

        // Load Courses
        function loadCourses(facultyId, courseCode = null) {
            if (!facultyId) {
                $('#course_code').empty().append('<option value="">Select Course</option>').trigger('change');
                return;
            }
            $.ajax({
                url: '{{ route('timetables.getFacultyCourses') }}',
                method: 'GET',
                data: { faculty_id: facultyId },
                success: function(response) {
                    $('#course_code').empty().append('<option value="">Select Course</option>');
                    response.course_codes.forEach(course => {
                        const option = new Option(`${course.course_code} - ${course.name}`, course.course_code, false, course.course_code === courseCode);
                        $('#course_code').append(option);
                    });
                    $('#course_code').trigger('change');
                },
                error: function(xhr) {
                    console.error('Error fetching courses:', xhr.responseText);
                    showAlert('error', 'Error', 'Failed to load courses.');
                }
            });
        }

        // Load Groups
        function loadGroups(facultyId, selectedGroups = []) {
            if (!facultyId) {
                $('#group_selection').empty().append('<option value="" disabled>Select Groups</option>').trigger('change');
                return;
            }
            $.ajax({
                url: '{{ route('timetables.getFacultyGroups') }}',
                method: 'GET',
                data: { faculty_id: facultyId },
                success: function(response) {
                    $('#group_selection').empty().append('<option value="" disabled>Select Groups</option>');
                    response.groups.forEach(group => {
                        const isSelected = selectedGroups.includes(group.group_name);
                        const option = new Option(`${group.group_name} (${group.student_count} students)`, group.group_name, false, isSelected);
                        $('#group_selection').append(option);
                    });
                    $('#group_selection').trigger('change');
                },
                error: function(xhr) {
                    console.error('Error fetching groups:', xhr.responseText);
                    showAlert('error', 'Error', 'Failed to load groups.');
                }
            });
        }

        // Load Lecturers
        function loadLecturers(courseCode, timetableId = null, selectedLecturerIds = []) {
            if (!courseCode) {
                $('#lecturer_ids').empty().append('<option value="" disabled>Select Lecturers</option>').trigger('change');
                return;
            }
            $.ajax({
                url: '{{ route('timetables.getCourseLecturers') }}',
                method: 'GET',
                data: { course_code: courseCode, timetable_id: timetableId },
                success: function(response) {
                    $('#lecturer_ids').empty().append('<option value="" disabled>Select Lecturers</option>');
                    response.lecturers.forEach(lecturer => {
                        const isSelected = timetableId ? lecturer.selected : selectedLecturerIds.includes(lecturer.id);
                        const option = new Option(lecturer.name, lecturer.id, false, isSelected);
                        $('#lecturer_ids').append(option);
                    });
                    $('#lecturer_ids').trigger('change');
                },
                error: function(xhr) {
                    console.error('Error fetching lecturers:', xhr.responseText);
                    showAlert('error', 'Error', 'Failed to load lecturers.');
                }
            });
        }

        // Create Exam
        $('.create-exam').click(function(e) {
            e.preventDefault();
            @if (!$setup)
                showAlert('error', 'Error', 'Please create an examination setup first.');
                return;
            @endif

            const programId = $(this).data('program-id');
            const yearNum = $(this).data('year-num');
            const date = $(this).data('date');
            const timeSlot = $(this).data('time-slot');

            $.ajax({
                url: '{{ route('timetables.getFacultyByProgramYear', ['program_id' => ':programId', 'year_num' => ':yearNum']) }}'
                    .replace(':programId', programId)
                    .replace(':yearNum', yearNum),
                method: 'GET',
                success: function(faculty) {
                    if (!faculty || !faculty.id) {
                        showAlert('error', 'Error', 'Invalid year for this program.');
                        return;
                    }

                    $('#examModalLabel').text('Create Exam');
                    $('#examForm').attr('action', '{{ route('timetables.store') }}').find('[name="_method"]').remove();
                    $('#examId').val('');
                    $('#faculty_id').val(faculty.id);
                    $('#exam_date').val(date);
                    const slotData = typeof timeSlot === 'string' ? JSON.parse(timeSlot) : timeSlot;
                    $('#time_slot').val(JSON.stringify(slotData));
                    $('#examForm').find('[name="start_time"], [name="end_time"]').remove();
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'start_time',
                        value: slotData.start_time
                    }).appendTo('#examForm');
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'end_time',
                        value: slotData.end_time
                    }).appendTo('#examForm');
                    $('#venue_id').val('').trigger('change');
                    $('#course_code').val('').trigger('change');
                    $('#group_selection').val('').trigger('change');
                    $('#lecturer_ids').val('').trigger('change');

                    loadCourses(faculty.id);
                    loadGroups(faculty.id);
                    $('#examModal').modal('show');
                },
                error: function(xhr) {
                    console.error('Error fetching faculty:', xhr.responseText);
                    showAlert('error', 'Error', 'Failed to load faculty data.');
                }
            });
        });

        // Edit Exam
        $('.edit-exam').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const facultyId = $(this).data('faculty-id');
            const courseCode = $(this).data('course-code');
            const examDate = $(this).data('exam-date');
            const timeSlot = $(this).data('time-slot');
            const venueId = $(this).data('venue-id');
            const groupSelection = $(this).data('group-selection');
            const lecturerIds = typeof $(this).data('lecturer-ids') === 'string' ? JSON.parse($(this).data('lecturer-ids')) : $(this).data('lecturer-ids');

            $('#examModalLabel').text('Edit Exam');
            $('#examForm').attr('action', `{{ url('timetables') }}/${id}`).find('[name="_method"]').remove()
                .end().append('<input type="hidden" name="_method" value="PUT">');
            $('#examId').val(id);
            $('#faculty_id').val(facultyId);
            $('#exam_date').val(examDate);
            const timeSlotParsed = typeof timeSlot === 'string' ? JSON.parse(timeSlot) : timeSlot;
            $('#time_slot').val(JSON.stringify(timeSlotParsed));
            $('#venue_id').val(venueId).trigger('change');
            $('#examForm').find('[name="start_time"], [name="end_time"]').remove();
            $('<input>').attr({
                type: 'hidden',
                name: 'start_time',
                value: timeSlotParsed.start_time
            }).appendTo('#examForm');
            $('<input>').attr({
                type: 'hidden',
                name: 'end_time',
                value: timeSlotParsed.end_time
            }).appendTo('#examForm');

            const selectedGroups = groupSelection ? groupSelection.split(',') : [];
            loadCourses(facultyId, courseCode);
            loadGroups(facultyId, selectedGroups);
            loadLecturers(courseCode, id, lecturerIds);
            $('#examModal').modal('show');
        });

        // Load Lecturers on Course Change
        $('#course_code').on('change', function() {
            const courseCode = $(this).val();
            const timetableId = $('#examId').val();
            loadLecturers(courseCode, timetableId);
        });

        // Exam Form Submission
        $('#examForm').submit(function(e) {
            e.preventDefault();
            const form = $(this);
            $.ajax({
                url: form.attr('action'),
                method: form.find('[name="_method"]').val() || 'POST',
                data: form.serialize(),
                success: function(response) {
                    showAlert('success', 'Success', response.message || response.success);
                    $('#examModal').modal('hide');
                    window.location.reload();
                },
                error: function(xhr) {
                    console.error('Error saving exam:', xhr.responseText);
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Failed to save exam.';
                    const errorDetails = xhr.responseJSON && xhr.responseJSON.details ? xhr.responseJSON.details : {};
                    let detailedMessage = errorMessage;
                    if (Object.keys(errorDetails).length > 0) {
                        detailedMessage += '<ul>';
                        Object.values(errorDetails).forEach(error => {
                            detailedMessage += `<li>${error}</li>`;
                        });
                        detailedMessage += '</ul>';
                    }
                    showAlert('error', 'Error', detailedMessage);
                }
            });
        });

        // Delete Exam
        $('.delete-exam-form').submit(function(e) {
            e.preventDefault();
            const form = $(this);
            Swal.fire({
                title: 'Are you sure?',
                text: 'This exam will be deleted permanently!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: form.attr('action'),
                        method: 'POST',
                        data: form.serialize(),
                        success: function(response) {
                            showAlert('success', 'Success', response.success || 'Exam deleted successfully.');
                            window.location.reload();
                        },
                        error: function(xhr) {
                            console.error('Error deleting exam:', xhr.responseText);
                            showAlert('error', 'Error', xhr.responseJSON?.error || 'Failed to delete exam.');
                        }
                    });
                }
            });
        });

        // Show Exam Details
        $('.show-exam').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            $.ajax({
                url: `{{ url('timetables') }}/${id}`,
                method: 'GET',
                success: function(data) {
                    $('#show_course_code').text(data.course_code || 'N/A');
                    $('#show_course_name').text(data.course_name || 'N/A');
                    $('#show_exam_date').text(data.exam_date || 'N/A');
                    $('#show_time_slot').text(`${data.start_time} - ${data.end_time} (${data.time_slot_name})` || 'N/A');
                    $('#show_venue_name').text(data.venue_name || 'N/A');
                    $('#show_venue_capacity').text(data.venue_capacity || 'N/A');
                    $('#show_group_selection').text(data.group_selection || 'N/A');
                    $('#show_lecturers').text(data.lecturers.join(', ') || 'N/A');
                    $('#show_faculty_name').text(data.faculty_name || 'N/A');
                    $('#showExamModal').modal('show');
                },
                error: function(xhr) {
                    console.error('Error fetching exam details:', xhr.responseText);
                    showAlert('error', 'Error', 'Failed to load exam details.');
                }
            });
        });

        // Exam Type Filter
        $('#exam_type').on('change', function() {
            $('#examTypeFilterForm').submit();
        });

        // Initialize Tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    });
</script>
@endsection
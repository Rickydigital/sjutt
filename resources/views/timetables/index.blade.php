@php
use Carbon\Carbon;
@endphp

@extends('components.app-main-layout')

@section('styles')
<style>
    .setup-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 15px;
        overflow: hidden;
    }

    .setup-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .setup-card-header {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        padding: 20px;
    }

    .exam-table th,
    .exam-table td {
        border: 2px solid #dee2e6 !important;
        vertical-align: middle;
        text-align: center;
        padding: 12px;
    }

    .exam-table th {
        background: linear-gradient(135deg, #6f42c1, #4B2E83);
        color: white;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 3;
    }

    .exam-table .course-cell {
        background: linear-gradient(135deg, #e2e8f0, #f8f9fa);
        transition: all 0.3s;
        cursor: pointer;
    }

    .exam-table .course-cell:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .action-icon {
        margin: 0 5px;
        font-size: 1.2rem;
        transition: color 0.3s;
        cursor: pointer;
    }

    .action-icon:hover {
        color: #007bff !important;
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

    .form-control,
    .select2-container--classic .select2-selection--single {
        border-radius: 8px;
        border: 1px solid #ced4da;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 5px rgba(111, 66, 193, 0.5);
    }

    .badge-lg {
        padding: 8px 16px;
        font-size: 0.95rem;
    }

    .time-slot-container {
        border-left: 3px solid #6f42c1;
        padding-left: 15px;
        margin-bottom: 10px;
    }

    .venue-badge {
        display: inline-block;
        margin: 3px;
        padding: 5px 10px;
        background-color: #e9ecef;
        border-radius: 5px;
        font-size: 0.85rem;
    }

    .supervisor-badge {
        display: inline-block;
        margin: 3px;
        padding: 5px 10px;
        background-color: #d4edda;
        border-radius: 5px;
        font-size: 0.85rem;
    }

    .generate-options {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .modal .select2-container--open {
        z-index: 1070 !important;
    }

    .modal .select2-dropdown {
        z-index: 1070 !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="fw-bold" style="color: #4B2E83;">
                <i class="fas fa-calendar-alt me-2"></i> Examination Timetables
            </h1>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#setupModal">
                    <i class="fas fa-plus me-1"></i> Create New Setup
                </button>
            </div>
        </div>
    </div>

    @if ($setups->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-semibold mb-3">Existing Examination Setups</h4>
        </div>
        @foreach ($setups as $setup)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card setup-card h-100">
                <div class="setup-card-header">
                    <h5 class="mb-1">{{ $setup->semester->name ?? 'N/A' }}</h5>
                    <p class="mb-0 small">{{ $setup->academic_year }}</p>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <i class="fas fa-calendar me-2"></i>
                        <strong>Period:</strong> {{ Carbon::parse($setup->start_date)->format('M d, Y') }} - {{
                        Carbon::parse($setup->end_date)->format('M d, Y') }}
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Time Slots:</strong> {{ count($setup->time_slots) }}
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-calendar-check me-2"></i>
                        <strong>Weekends:</strong> {{ $setup->include_weekends ? 'Included' : 'Excluded' }}
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-list-ol me-2"></i>
                        <strong>Exams Scheduled:</strong> {{ $setup->examinationTimetables->count() }}
                    </p>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between">
                        <a href="#" class="btn btn-sm btn-primary view-setup" data-setup-id="{{ $setup->id }}">
                            <i class="fas fa-eye me-1"></i> View
                        </a>

                        <button class="btn btn-sm btn-warning edit-setup" data-setup-id="{{ $setup->id }}">
                            <i class="fas fa-edit me-1"></i> Edit
                        </button>
                        <form action="{{ route('examination.destroySetup', $setup->id) }}" method="POST"
                            class="d-inline delete-setup-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if ($setup)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #6f42c1, #4B2E83);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 text-white">
                            {{ $setup->semester->name ?? 'N/A' }} - {{ $setup->academic_year }}
                        </h4>
                        <div>
                            <button class="btn btn-light btn-sm me-2" data-bs-toggle="modal"
                                data-bs-target="#generateModal">
                                <i class="fas fa-magic me-1"></i> Auto-Generate
                            </button>
                            @if($setup)
                            <button class="btn btn-success btn-sm me-2" data-bs-toggle="modal"
                                data-bs-target="#exportPdfModal">
                                <i class="fas fa-file-pdf me-1"></i> Export PDF
                            </button>
                            @endif
                            <button class="btn btn-warning btn-sm me-2 edit-setup" data-setup-id="{{ $setup->id }}">
                                <i class="fas fa-edit me-1"></i> Edit Setup
                            </button>
                            <form action="{{ route('examination.clearTimetables', $setup->id) }}" method="POST"
                                class="d-inline clear-timetables-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt me-1"></i> Clear All
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Select Program to Continue</label>
                        <select class="form-control select2" id="filter_program" data-placeholder="Select a program">
                            <option value=""></option>
                            @foreach($programs as $p)
                            <option value="{{ $p->id }}" {{ ((string)$programId===(string)$p->id) ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Choose a program to view its timetable.</small>
                    </div>
                </div>

                @if(empty($programId))
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Select a program to continue.
                </div>
                @else
                @foreach($dateChunks as $chunkIndex => $chunkDays)
                <div class="table-responsive mb-4">
                    <table class="table table-bordered exam-table align-middle">
                        <thead>
                            <tr>
                                <th style="width:220px;">TIME</th>
                                <th style="width:180px;">CLASS</th>
                                @foreach($chunkDays as $d)
                                <th>
                                    {{ Carbon::parse($d)->format('d-m') }}
                                    ({{ strtoupper(Carbon::parse($d)->format('l')) }})
                                </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($timeSlots as $slot)
                            @php
                            $slotStart = Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i');
                            $slotEnd = Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i');
                            $slotLabel = ($slot['name'] ?? 'Session') . " ({$slotStart}-{$slotEnd})";
                            @endphp

                            @if($classes->isEmpty())
                            <tr>
                                <td class="fw-semibold" style="background:#4B2E83;color:#fff;">
                                    {{ $slotLabel }}
                                </td>
                                <td colspan="{{ 1 + count($chunkDays) }}" class="text-muted text-center">
                                    No classes found for this program.
                                </td>
                            </tr>
                            @else
                            @foreach($classes as $i => $class)
                            <tr>
                                {{-- TIME column with rowspan like screenshot --}}
                                @if($i === 0)
                                <td rowspan="{{ $classes->count() }}" class="fw-semibold"
                                    style="background:#4B2E83;color:#fff;">
                                    {{ $slotLabel }}
                                </td>
                                @endif

                                {{-- CLASS column --}}
                                <td class="fw-semibold" style="background:#5b3aa6;color:#fff;">
                                    {{ $class->name }}
                                </td>

                                {{-- date cells --}}
                                @foreach($chunkDays as $d)
                                @php
                                $dateKey = Carbon::parse($d)->format('Y-m-d');
                                $items = $grid[$class->id][$dateKey][$slotStart] ?? [];
                                @endphp

                                <td class="course-cell">
                                    @if(!empty($items))
                                    @foreach($items as $tt)
                                    <div class="mb-2">
                                        <div class="fw-bold">{{ $tt->course_code }}</div>

                                        {{-- venues --}}
                                        <div class="small">
                                            @foreach($tt->venues as $v)
                                            <span class="venue-badge">
                                                {{ $v->name }}
                                                ({{ $v->pivot->allocated_capacity ?? 0 }})
                                            </span>
                                            @endforeach
                                        </div>

                                        {{-- actions --}}
                                        <div class="mt-2">
                                            <a href="#" class="action-icon show-exam" data-id="{{ $tt->id }}"
                                                title="View">
                                                <i class="bi bi-eye-fill text-primary"></i>
                                            </a>

                                            <a href="#" class="action-icon edit-exam" data-id="{{ $tt->id }}"
                                                title="Edit">
                                                <i class="bi bi-pencil-fill text-warning"></i>
                                            </a>


                                            <form action="{{ route('timetables.destroy', $tt->id) }}" method="POST"
                                                class="d-inline delete-exam-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-icon border-0 bg-transparent"
                                                    title="Delete">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @endforeach
                                    @else
                                    {{-- plus icon like screenshot --}}
                                    <div class="text-center">
                                        <a href="#" class="text-decoration-none add-exam"
                                            data-faculty-id="{{ $class->id }}" data-exam-date="{{ $dateKey }}"
                                            data-start-time="{{ $slotStart }}" data-end-time="{{ $slotEnd }}"
                                            title="Add exam">
                                            <i class="bi bi-plus-circle" style="font-size:1.4rem;color:#6f42c1;"></i>
                                        </a>
                                    </div>

                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endforeach

                @endif

            </div>
        </div>
    </div>
    @else
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle me-2"></i>
        Select an examination setup above to view or manage its timetable.
    </div>
    @endif

    <div class="modal fade" id="setupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('timetables.storeSetup') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Examination Setup</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="semester_id" required>
                                    <option value="">Select Semester</option>
                                    @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="academic_year" placeholder="2024/2025"
                                    pattern="\d{4}/\d{4}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="include_weekends"
                                        id="include_weekends">
                                    <label class="form-check-label" for="include_weekends">
                                        Include Weekends
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Time Slots <span class="text-danger">*</span></label>
                                <div id="timeSlots">
                                    <div class="time-slot-container mb-3">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" name="time_slots[0][name]"
                                                    placeholder="Morning Session" required>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="time" class="form-control" name="time_slots[0][start_time]"
                                                    required>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="time" class="form-control" name="time_slots[0][end_time]"
                                                    required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTimeSlot()">
                                    <i class="fas fa-plus me-1"></i> Add Time Slot
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Setup</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    @if ($setup)
    <div class="modal fade" id="exportPdfModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="exportPdfForm" method="POST" action="{{ route('examination.export.pdf', $setup->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Export Examination Timetable</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Export For</label>
                            <select class="form-control select2" name="scope" id="export_scope" required
                                data-placeholder="Select option">
                                <option value=""></option>
                                <option value="all">All Programs</option>
                                <option value="single">Single Program</option>
                            </select>
                        </div>

                        <div class="mb-3" id="export_program_wrap" style="display:none;">
                            <label class="form-label fw-semibold">Select Program</label>
                            <select class="form-control select2" name="program_id" id="export_program_id"
                                data-placeholder="Select program">
                                <option value=""></option>
                                @foreach($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->short_name }} - {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Draft</label>
                            <select class="form-control select2" name="draft" required data-placeholder="Select draft">
                                <option value=""></option>
                                <option>First Draft</option>
                                <option>Second Draft</option>
                                <option>Third Draft</option>
                                <option>Fourth Draft</option>
                                <option>Fifth Draft</option>
                                <option selected>Final Draft</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-download me-1"></i> Download PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    <div class="modal fade" id="editSetupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editSetupForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Examination Setup</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Setup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($setup)
    <div class="modal fade" id="generateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="generateForm">
                    @csrf
                    <input type="hidden" name="exam_setup_id" value="{{ $setup->id }}">
                    <div class="modal-header">
                        <h5 class="modal-title">Auto-Generate Examination Timetable</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="generate-options">
                            <h6 class="fw-bold mb-3">Generation Options</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="program_id" id="program_id" required>
                                        <option value="all">All Programs</option>
                                        @foreach ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Faculty</label>
                                    <select class="form-control select2" name="faculty_id" id="faculty_id">
                                        <option value="all">All Faculties</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Time Priority <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <select class="form-control" name="time_priority[]" required>
                                            <option value="morning">Morning</option>
                                            <option value="afternoon">Afternoon</option>
                                            <option value="evening">Evening</option>
                                        </select>
                                        <select class="form-control" name="time_priority[]" required>
                                            <option value="afternoon">Afternoon</option>
                                            <option value="morning">Morning</option>
                                            <option value="evening">Evening</option>
                                        </select>
                                        <select class="form-control" name="time_priority[]" required>
                                            <option value="evening">Evening</option>
                                            <option value="morning">Morning</option>
                                            <option value="afternoon">Afternoon</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Venue Strategy <span class="text-danger">*</span></label>
                                    <select class="form-control" name="venue_strategy" id="venue_strategy" required>
                                        <option value="distribute">Distribute (ll Available venues)</option>
                                        <option value="single">Single (Selected venues only)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="selected_venues_container" style="display: none;">
                                    <label class="form-label">Select Venues</label>
                                    <select class="form-control select2" name="selected_venues[]" id="selected_venues"
                                        multiple>
                                        @foreach ($venues as $venue)
                                        <option value="{{ $venue->id }}">{{ $venue->name }} ({{ $venue->capacity }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-magic me-1"></i> Generate Timetable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="modal fade" id="viewExamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Examination Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="examDetailsContent">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="examFormModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="examForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="examFormMethod" value="POST">
                    <input type="hidden" name="exam_setup_id" id="exam_setup_id" value="{{ $setup?->id }}">
                    <input type="hidden" name="faculty_id" id="faculty_id_hidden">

                    <div class="modal-header">
                        <h5 class="modal-title" id="examFormTitle">Add Exam</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Exam Date</label>
                                <input type="date" class="form-control" name="exam_date" id="exam_date" required
                                    readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Start</label>
                                <input type="time" class="form-control" name="start_time" id="start_time" required
                                    readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">End</label>
                                <input type="time" class="form-control" name="end_time" id="end_time" required readonly>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Course</label>
                                <select class="form-control select2" name="course_code" id="course_code" required
                                    data-placeholder="Select course">
                                    <option value=""></option>
                                </select>
                                <small class="text-muted">Courses are loaded based on the selected class
                                    (faculty).</small>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Venues</label>
                                <select class="form-control select2" name="selected_venues[]" id="exam_venues" multiple
                                    required data-placeholder="Select venues">
                                    @foreach($venues as $v)
                                    <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->capacity }})</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="examFormSubmitBtn">Save</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewSetupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Setup Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="setupDetailsContent">
                    <!-- loaded by AJAX -->
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
<script>
    let timeSlotIndex = 1;

/* =========================
   HELPERS (SweetAlert + Ajax)
========================= */
function extractAjaxMessage(xhr, fallback = 'Something went wrong.') {
  try {
    const r = xhr.responseJSON;
    if (r?.message) return r.message;
    if (r?.errors) return Object.values(r.errors).flat().join(' ');
    if (typeof r === 'string') return r;
  } catch (e) {}
  return fallback;
}

function swalLoading(title = 'Please wait...', text = 'Processing...') {
  Swal.fire({
    title,
    text,
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => Swal.showLoading()
  });
}

// Fix Select2 in ALL modals - including #setupModal
$(document).on('shown.bs.modal', '.modal', function () {
    $(this).find('.select2').each(function () {
        if ($(this).data('select2')) {
            $(this).select2('destroy');
        }
        $(this).select2({
            theme: 'classic',
            width: '100%',
            dropdownParent: $(this).closest('.modal'),  // This is the magic line
            placeholder: $(this).data('placeholder') || 'Select an option',
            allowClear: true
        });
    });
});

function reinitSelect2($root, dropdownParent = null) {
  $root.find('.select2').each(function () {
    const $el = $(this);
    if ($el.data('select2')) $el.select2('destroy');

    $el.select2({
      theme: 'classic',
      width: '100%',
      dropdownParent: dropdownParent || $root,
      placeholder: $el.data('placeholder') || 'Select an option',
      allowClear: true
    });
  });
}


$(document).on('change', '#export_scope', function () {
  const v = $(this).val();
  if (v === 'single') {
    $('#export_program_wrap').slideDown();
    $('#export_program_id').prop('required', true);
  } else {
    $('#export_program_wrap').slideUp();
    $('#export_program_id').prop('required', false).val(null).trigger('change');
  }
});
/* =========================
   TIME SLOT ADDER
========================= */
window.addTimeSlot = function(containerId = 'timeSlots') {
  const html = `
    <div class="time-slot-container mb-3" data-index="${timeSlotIndex}">
      <div class="row align-items-center">
        <div class="col-md-4">
          <input type="text" class="form-control" name="time_slots[${timeSlotIndex}][name]"
                 placeholder="Session Name (e.g. Morning)" required>
        </div>
        <div class="col-md-3">
          <input type="time" class="form-control" name="time_slots[${timeSlotIndex}][start_time]" required>
        </div>
        <div class="col-md-3">
          <input type="time" class="form-control" name="time_slots[${timeSlotIndex}][end_time]" required>
        </div>
        <div class="col-md-2">
          <button type="button" class="btn btn-sm btn-danger remove-time-slot">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </div>`;
  $(`#${containerId}`).append(html);
  timeSlotIndex++;
};

$(document).ready(function() {

  /* =========================
     GLOBAL SELECT2 INIT
  ========================= */
  $('.select2').select2({
    theme: 'classic',
    width: '100%',
    placeholder: function() {
      return $(this).data('placeholder') || "Select an option";
    },
    allowClear: true
  });

  $(document).on('click', '.remove-time-slot', function() {
    $(this).closest('.time-slot-container').remove();
  });

  /* =========================
     PROGRAM FILTER (reload page)
  ========================= */
  $('#filter_program').on('change', function () {
    const programId = $(this).val();
    const params = new URLSearchParams(window.location.search);

    if (!params.get('setup_id')) {
      @if($setup)
        params.set('setup_id', '{{ $setup->id }}');
      @endif
    }

    if (!programId) params.delete('program_id');
    else params.set('program_id', programId);

    window.location.search = params.toString();
  });

  /* =========================
     VIEW SETUP MODAL
  ========================= */
  $(document).on('click', '.view-setup', function(e) {
    e.preventDefault();
    const setupId = $(this).data('setup-id');

    $('#setupDetailsContent').html('<div class="text-center py-4">Loading...</div>');
    $('#viewSetupModal').modal('show');

    $.get(`{{ url('/examination/setup') }}/${setupId}`)
      .done(function(data) {
        const slots = (data.time_slots || []).map(s =>
          `<li>${s.name}: ${s.start_time} - ${s.end_time}</li>`
        ).join('');

        const html = `
          <div class="row g-3">
            <div class="col-md-6"><strong>Semester:</strong> ${data.semester || 'N/A'}</div>
            <div class="col-md-6"><strong>Academic Year:</strong> ${data.academic_year || 'N/A'}</div>
            <div class="col-md-6"><strong>Start Date:</strong> ${data.start_date || 'N/A'}</div>
            <div class="col-md-6"><strong>End Date:</strong> ${data.end_date || 'N/A'}</div>
            <div class="col-md-6"><strong>Weekends:</strong> ${data.include_weekends ? 'Included' : 'Excluded'}</div>
            <div class="col-12">
              <strong>Time Slots:</strong>
              <ul class="mt-2 mb-0">${slots || '<li>No slots</li>'}</ul>
            </div>
          </div>
        `;
        $('#setupDetailsContent').html(html);
      })
      .fail(function(xhr) {
        console.log('Setup details failed:', xhr.status, xhr.responseText);
        $('#setupDetailsContent').html('<div class="alert alert-danger">Failed to load setup details.</div>');
      });
  });

  /* =========================
     EDIT SETUP MODAL (AJAX load)
  ========================= */
  $(document).on('click', '.edit-setup', function() {
    const setupId = $(this).data('setup-id');

    $.ajax({
      url: `{{ url('/examination/setup') }}/${setupId}/edit`,
      method: 'GET',
      success: function(data) {
        const form = $('#editSetupForm');
        form.attr('action', `/examination/setup/${setupId}`);

        let html = `
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Semester</label>
              <select class="form-control select2" name="semester_id" required data-placeholder="Select semester">
                <option value=""></option>
                ${data.semesters.map(s => `<option value="${s.id}" ${s.id == data.setup.semester_id ? 'selected' : ''}>${s.name}</option>`).join('')}
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Academic Year</label>
              <input type="text" class="form-control" name="academic_year" value="${data.setup.academic_year}" required pattern="\\d{4}/\\d{4}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Start Date</label>
              <input type="date" class="form-control" name="start_date" value="${data.setup.start_date}" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">End Date</label>
              <input type="date" class="form-control" name="end_date" value="${data.setup.end_date}" required>
            </div>
            <div class="col-md-12 mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="include_weekends" id="edit_include_weekends" ${data.setup.include_weekends ? 'checked' : ''}>
                <label class="form-check-label" for="edit_include_weekends">Include Weekends</label>
              </div>
            </div>
            <div class="col-md-12 mb-3">
              <label class="form-label">Time Slots</label>
              <div id="editTimeSlots">`;

        (data.setup.time_slots || []).forEach((slot, index) => {
          html += `
            <div class="time-slot-container mb-3" data-index="${index}">
              <div class="row align-items-center">
                <div class="col-md-4">
                  <input type="text" class="form-control" name="time_slots[${index}][name]" value="${slot.name}" required>
                </div>
                <div class="col-md-3">
                  <input type="time" class="form-control" name="time_slots[${index}][start_time]" value="${slot.start_time}" required>
                </div>
                <div class="col-md-3">
                  <input type="time" class="form-control" name="time_slots[${index}][end_time]" value="${slot.end_time}" required>
                </div>
                <div class="col-md-2">
                  <button type="button" class="btn btn-sm btn-danger remove-time-slot">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>`;
        });

        html += `
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addTimeSlot('editTimeSlots')">
                <i class="fas fa-plus me-1"></i> Add Time Slot
              </button>
            </div>
          </div>`;

        $('#editSetupModal .modal-body').html(html);
        reinitSelect2($('#editSetupModal'), $('#editSetupModal'));
        $('#editSetupModal').modal('show');
      },
      error: function() {
        Swal.fire('Error', 'Could not load setup data', 'error');
      }
    });
  });

  /* =========================
     CONFIRM DELETE / CLEAR
  ========================= */
  $('.delete-setup-form').submit(function(e) {
    e.preventDefault();
    const form = this;
    Swal.fire({
      title: 'Are you sure?',
      text: "This will delete the setup and ALL associated timetables!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) form.submit();
    });
  });

  $('.clear-timetables-form').submit(function(e) {
    e.preventDefault();
    const form = this;
    Swal.fire({
      title: 'Clear timetable?',
      text: "All scheduled exams in this setup will be removed!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Yes, clear everything'
    }).then((result) => {
      if (result.isConfirmed) form.submit();
    });
  });

  $(document).on('submit', '.delete-exam-form', function(e) {
    e.preventDefault();
    const form = this;
    Swal.fire({
      title: 'Delete this exam?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Yes, delete'
    }).then((result) => {
      if (result.isConfirmed) form.submit();
    });
  });

  /* =========================
     SHOW EXAM MODAL (VIEW)
  ========================= */
  $(document).on('click', '.show-exam', function(e) {
    e.preventDefault();
    const examId = $(this).data('id');

    $('#examDetailsContent').html(
      '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-3">Loading...</p></div>'
    );
    $('#viewExamModal').modal('show');

    $.ajax({
      url: `{{ url('/timetables') }}/${examId}`,
      method: 'GET',
      success: function(data) {
        let venuesHtml = (data.venues || []).map(v =>
          `<span class="venue-badge me-2 mb-1">${v.name} (${v.pivot?.allocated_capacity ?? '?'} seats)</span>`
        ).join('');

        let supervisorsHtml = (data.supervisors || []).map(s =>
          `<span class="supervisor-badge me-2 mb-1">${s.name} <small>(${s.pivot?.supervisor_role || 'Invigilator'})</small></span>`
        ).join('');

        const html = `
          <div class="row g-3">
            <div class="col-md-8">
              <h5 class="mb-1">${data.course_code} - ${(data.course?.name || 'Unknown')}</h5>
              <p class="text-muted mb-3">${(data.program?.short_name || '')} • ${(data.faculty?.name || '')}</p>
            </div>
            <div class="col-md-4 text-md-end">
              <h6 class="mb-1">${new Date(data.exam_date).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}</h6>
              <h6>${data.start_time} – ${data.end_time}</h6>
            </div>
            <div class="col-12">
              <hr class="my-2">
              <strong>Venues:</strong><br>
              ${venuesHtml || '<span class="text-muted">No venues assigned</span>'}
            </div>
            <div class="col-12">
              <strong>Supervisors / Invigilators:</strong><br>
              ${supervisorsHtml || '<span class="text-muted">No supervisors assigned</span>'}
            </div>
          </div>`;

        $('#examDetailsContent').html(html);
      },
      error: function() {
        $('#examDetailsContent').html('<div class="alert alert-danger">Failed to load examination details.</div>');
      }
    });
  });

  /* =========================
     GENERATE MODAL OPTIONS
  ========================= */
  $('#venue_strategy').on('change', function() {
    if ($(this).val() === 'single') {
      $('#selected_venues_container').slideDown();
      $('#selected_venues').prop('required', true);
    } else {
      $('#selected_venues_container').slideUp();
      $('#selected_venues').prop('required', false);
    }
  });

 $('#program_id').on('change', function() {
  const programId = $(this).val() || 'all';
  const facultySelect = $('#faculty_id');

  facultySelect.prop('disabled', true).html('<option value="all">All Faculties</option>');

  $.get('{{ route("examination.getFacultiesByProgram") }}', { program_id: programId })
    .done(function(data) {
      let options = '<option value="all">All Faculties</option>';
      (data.faculties || []).forEach(f => options += `<option value="${f.id}">${f.name}</option>`);
      facultySelect.html(options).prop('disabled', false);
    })
    .fail(function() {
      facultySelect.html('<option value="">Error loading faculties</option>');
    });
});

 
  /* =========================
     EXAM FORM MODAL (CREATE/EDIT)
  ========================= */
  function initExamModalSelect2() {
    reinitSelect2($('#examFormModal'), $('#examFormModal'));
  }

  function loadCoursesForFaculty(facultyId, selectedCourseCode = null) {
    $('#course_code').html('<option value=""></option>');

    $.get('{{ route("examination.getFacultyCourses") }}', { faculty_id: facultyId })
      .done(function(resp){
        let options = '<option value=""></option>';

        (resp.course_codes || []).forEach(c => {
          // you MUST return cross_catering from controller for this label to work
          const tag = c.cross_catering ? ' [Cross]' : '';
          options += `<option value="${c.course_code}">${c.course_code} - ${c.name}${tag}</option>`;
        });

        $('#course_code').html(options);

        if (selectedCourseCode) $('#course_code').val(selectedCourseCode).trigger('change');
        else $('#course_code').val(null).trigger('change');
      })
      .fail(function(){
        $('#course_code').html('<option value="">Failed to load courses</option>');
      });
  }

  // OPEN CREATE modal from "+"
  $(document).on('click', '.add-exam', function(e){
    e.preventDefault();

    const facultyId = $(this).data('faculty-id');
    const examDate  = $(this).data('exam-date');
    const startTime = $(this).data('start-time');
    const endTime   = $(this).data('end-time');

    $('#examFormTitle').text('Add Exam');
    $('#examFormSubmitBtn').text('Save');

    $('#examForm').attr('action', '{{ route("timetables.store") }}');
    $('#examFormMethod').val('POST');

    $('#faculty_id_hidden').val(facultyId);
    $('#exam_date').val(examDate);
    $('#start_time').val(startTime);
    $('#end_time').val(endTime);

    $('#exam_venues').val(null).trigger('change');
    $('#course_code').val(null).trigger('change');

    initExamModalSelect2();
    loadCoursesForFaculty(facultyId);

    $('#examFormModal').modal('show');
  });

  // OPEN EDIT modal from pencil
  $(document).on('click', '.edit-exam', function(e){
    e.preventDefault();

    const examId = $(this).data('id');

    $('#examFormTitle').text('Edit Exam');
    $('#examFormSubmitBtn').text('Update');

    const updateUrl = `{{ url('/timetables') }}/${examId}`;
    $('#examForm').attr('action', updateUrl);
    $('#examFormMethod').val('PUT');

    initExamModalSelect2();

    $.ajax({
      url: `{{ url('/timetables') }}/${examId}`,
      method: 'GET',
      success: function(data){
        // IMPORTANT: your controller show() should return faculty_id
        const facultyId = data.faculty_id || data.faculty?.id;

        $('#faculty_id_hidden').val(facultyId);
        $('#exam_date').val((data.exam_date || '').substring(0,10));
        $('#start_time').val((data.start_time || '').substring(0,5));
        $('#end_time').val((data.end_time || '').substring(0,5));

        const venueIds = (data.venues || []).map(v => String(v.id));
        $('#exam_venues').val(venueIds).trigger('change');

        loadCoursesForFaculty(facultyId, data.course_code);

        $('#examFormModal').modal('show');
      },
      error: function(){
        Swal.fire('Error', 'Failed to load exam for editing.', 'error');
      }
    });
  });

  /* =========================
     SAVE EXAM (CREATE + EDIT) -> SweetAlert uses controller message
  ========================= */
  $('#examForm').on('submit', function(e){
    e.preventDefault();

    const form = $(this);
    const actionUrl = form.attr('action');
    const method = ($('#examFormMethod').val() || 'POST').toUpperCase();

    swalLoading(method === 'PUT' ? 'Updating exam...' : 'Saving exam...', 'Please wait');

    $.ajax({
      url: actionUrl,
      method: 'POST', // always POST; Laravel uses _method
      data: form.serialize() + `&_method=${method}`,
      success: function(resp){
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: resp.message || 'Saved successfully.',
          timer: 2500
        }).then(() => location.reload());
      },
      error: function(xhr){
        const msg = extractAjaxMessage(xhr, 'Failed to save exam.');
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
      }
    });
  });

  /* =========================
     GENERATE TIMETABLE -> SweetAlert loading + controller message
  ========================= */
  $('#generateModal').on('show.bs.modal', function () {
    $('#program_id').trigger('change');
    reinitSelect2($('#generateModal'), $('#generateModal'));
  });

  $('#generateForm').on('submit', function(e) {
    e.preventDefault();

    const form = $(this);

    Swal.fire({
      title: 'Generate Timetable?',
      text: "This may take a few moments depending on the number of courses.",
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Generate',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (!result.isConfirmed) return;

      // SWEETALERT LOADING (instead of button loader)
      swalLoading('Generating timetable...', 'Please wait');

      $.ajax({
        url: '{{ route("timetables.generate") }}',
        method: 'POST',
        data: form.serialize(),
        success: function(response) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.message || 'Timetable generated successfully.',
            timer: 2500
          }).then(() => location.reload());
        },
        error: function(xhr) {
          const msg = extractAjaxMessage(xhr, 'An error occurred while generating the timetable.');
          Swal.fire('Error', msg, 'error');
        }
      });
    });
  });

  /* =========================
     TOOLTIP INIT
  ========================= */
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  [...tooltipTriggerList].forEach(el => new bootstrap.Tooltip(el));

});
</script>
@endsection
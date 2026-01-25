@extends('components.app-main-layout')

@section('content')
    <style>
        /* Existing styles with enhancements */
        .timetable-table th,
        .timetable-table td {
            border: 2px solid #dee2e6 !important;
            vertical-align: middle;
            text-align: center;
        }

        .timetable-table th {
            background: linear-gradient(135deg, #6f42c1, #4B2E83);
            color: white;
            font-weight: 600;
        }

        .timetable-table .empty-cell {
            height: 100px;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .timetable-table .empty-cell:hover {
            background-color: #e9ecef;
        }

        .timetable-table .course-cell {
            background: linear-gradient(135deg, #e2e8f0, #f8f9fa);
            transition: transform 0.2s;
            display: inline-block;
            width: auto;
            margin: 2px;
            padding: 5px;
            vertical-align: top;
            border-radius: 5px;
        }

        .timetable-table .course-cell:hover {
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

        .form-control,
        .select2-container--classic .select2-selection--single,
        .select2-container--classic .select2-selection--multiple {
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }

        .form-control:focus,
        .select2-container--classic .select2-selection--single:focus,
        .select2-container--classic .select2-selection--multiple:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 5px rgba(111, 66, 193, 0.5);
        }

        .select-all-option {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .add-session-container {
            margin-top: 5px;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-block;
            vertical-align: top;
        }

        .add-session-container:hover {
            background-color: #e9ecef;
        }

        .course-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 5px;
        }

        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .select2-container--open {
            z-index: 9999 !important;
        }

        .select2-dropdown {
            z-index: 10000 !important;
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .modal-lg .select2-container {
            width: 100% !important;
        }
    </style>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="fw-bold" style="color: #4B2E83;">
                <i class="fas fa-clock me-2"></i> Timetable Management
            </h1>
            <div>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#generateTimetableModal">
                    <i class="fas fa-magic me-1"></i> Generate
                </button>
                <a href="{{ route('cross-cating.index') }}" class="btn btn-primary me-2">
                    <i class="fas fa-users me-1"></i> Cross Cating
                </a>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#importTimetableModal">
                    <i class="fas fa-download me-1"></i> Import
                </button>

                   <div class="dropdown d-inline-block">
    <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-upload me-1"></i> Export
    </button>
    <ul class="dropdown-menu">
        @foreach(['First Draft', 'Second Draft', 'Third Draft', 'Fourth Draft','Pre Final', 'Final Draft'] as $draft)
            <li>
                <a class="dropdown-item" href="javascript:void(0)" onclick="exportTimetable('{{ $draft }}')">
                    {{ $draft }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
            </div>
        </div>
    </div>

    <!-- Timetable Semester Management Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0 text-white">Timetable Semester Management</h5>
        </div>
        <div class="card-body">
            <p>Current Timetable Semester: <span id="current-semester">{{ $timetableSemester ? $timetableSemester->semester->name . ' (' . $timetableSemester->academic_year . ')' : 'None' }}</span></p>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addTimetableSemesterModal">Add Timetable Semester</button>
            <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#editTimetableSemesterModal" {{ $timetableSemester ? '' : 'disabled' }}>Update Timetable Semester</button>
        </div>
    </div>

    <!-- Faculty Filter -->
    <div class="row mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <form method="GET" action="{{ route('timetable.index') }}" id="facultyFilterForm">
                <div class="mb-3">
                    <label for="faculty" class="form-label fw-semibold">Select Faculty</label>
                    <select name="faculty" id="faculty" class="form-control select2">
                        <option value="">Select a Faculty</option>
                        @if (empty($faculties))
                            <option value="" disabled>No faculties available</option>
                        @else
                            @foreach ($faculties as $id => $name)
                                <option value="{{ $id }}" {{ $facultyId == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @if (empty($faculties))
                        <small class="text-danger">No faculties found. Please add faculties in the system.</small>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Timetable Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0 text-white">
                        {{ $facultyId ? $faculties[$facultyId] : 'Timetable Overview' }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table timetable-table mb-0">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Time</th>
                                    @foreach ($days as $day)
                                        <th>{{ $day }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $activitiesByDay = $timetables->groupBy('day')->map(function ($group) {
                                        return $group->sortBy('time_start');
                                    });
                                    $occupiedUntil = array_fill_keys($days, -1);
                                @endphp
                                @foreach ($timeSlots as $i => $slotStart)
                                    <tr>
                                        <td>{{ $slotStart }}-{{ date('H:i', strtotime($slotStart) + 3600) }}</td>
                                        @foreach ($days as $day)
                                            @if ($i > $occupiedUntil[$day])
                                                @php
                                                    $activitiesForDay = $activitiesByDay->get($day, collect());
                                                    $activities = $activitiesForDay->filter(function ($act) use ($slotStart) {
                                                        return substr($act->time_start, 0, 5) == $slotStart;
                                                    });
                                                    $maxDuration = $activities->max(function ($act) {
                                                        return (strtotime($act->time_end) - strtotime($act->time_start)) / 3600;
                                                    }) ?? 1;
                                                    $rowspan = ceil($maxDuration);
                                                    $occupiedUntil[$day] = $i + $rowspan - 1;
                                                @endphp
                                                <td rowspan="{{ $rowspan }}">
                                                    @if ($activities->isNotEmpty())
                                                        <div class="course-container">
                                                            @foreach ($activities as $activity)
                                                                <div class="course-cell p-2">
                                                                    <p class="fw-bold mb-1">{{ $activity->course_code }}</p>
                                                                    <p class="mb-1">{{ $activity->activity }}</p>
                                                                    <p class="mb-1">{{ $activity->group_selection }}</p>
                                                                    <p class="mb-2">{{ $activity->venue->name }}</p>
                                                                    <div class="d-flex justify-content-center">
                                                                        <a href="#" class="action-icon show-timetable"
                                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                                            data-id="{{ $activity->id }}" title="View Details">
                                                                            <i class="bi bi-eye-fill text-primary"></i>
                                                                        </a>
                                                                        <a href="#" class="action-icon edit-timetable"
                                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                                            data-id="{{ $activity->id }}" title="Edit">
                                                                            <i class="bi bi-pencil-square text-primary"></i>
                                                                        </a>
                                                                        <form action="{{ route('timetable.destroy', $activity->id) }}"
                                                                            method="POST" style="display:inline;"
                                                                            class="delete-timetable-form">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" data-bs-toggle="tooltip"
                                                                                data-bs-placement="top" class="action-icon"
                                                                                title="Delete">
                                                                                <i class="bi bi-trash text-danger"></i>
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <div class="add-session-container add-timetable"
                                                            data-day="{{ $day }}"
                                                            data-time="{{ $slotStart }}"
                                                            data-faculty="{{ $facultyId }}">
                                                            <i class="bi bi-plus-circle fs-5"></i>
                                                        </div>
                                                    @else
                                                        <div class="add-timetable empty-cell"
                                                            data-day="{{ $day }}"
                                                            data-time="{{ $slotStart }}"
                                                            data-faculty="{{ $facultyId }}">
                                                            <i class="bi bi-plus-circle fs-5"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                            @endif
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

    <!-- Generate Timetable Modal -->
    <div class="modal fade" id="generateTimetableModal" tabindex="-1" aria-labelledby="generateTimetableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateTimetableModalLabel">Generate Timetable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="generateTimetableForm" method="POST" action="{{ route('timetable.generate') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="generate_faculty_id" class="form-label">Faculty <span class="text-danger">*</span></label>
                                <select name="faculty_id" id="generate_faculty_id" class="select2 form-control" required>
                                    <option value="">Select Faculty</option>
                                    @foreach ($faculties as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="generate_venues" class="form-label">Venues <span class="text-danger">*</span></label>
                                <select name="venues[]" id="generate_venues" class="select2 form-control" multiple required>
                                    @foreach ($venues as $venue)
                                        <option value="{{ $venue->id }}" data-capacity="{{ $venue->capacity }}">
                                            {{ $venue->name }} (Capacity: {{ $venue->capacity }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Courses <span class="text-danger">*</span></label>
                                <div id="course-selections">
                                    <div class="course-selection mb-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <select name="courses[]" class="select2 form-control course-code" required>
                                                    <option value="">Select Course</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <select name="lecturers[]" class="select2 form-control lecturer-select" required>
                                                    <option value="">Select Lecturer</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="activities[]" class="select2 form-control activity-select" required>
                                                    <option value="">Select Activity</option>
                                                    <option value="Lecture">Lecture</option>
                                                    <option value="Practical">Practical</option>
                                                    <option value="Workshop">Workshop</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger remove-course"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <select name="groups[0][]" class="select2 form-control group-selection" multiple required>
                                                    <option value="All Groups" class="select-all-option">All Groups</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary mt-2" id="add-course">Add Course</button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Timetable Modal -->
    <div class="modal fade" id="addTimetableModal" tabindex="-1" aria-labelledby="addTimetableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTimetableModalLabel">Add Timetable Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addTimetableForm" method="POST" action="{{ route('timetable.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="faculty_id" id="modal_faculty_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_day" class="form-label">Day</label>
                                <input type="text" name="day" id="modal_day" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_time_start" class="form-label">Start Time</label>
                                <input type="text" name="time_start" id="modal_time_start" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_time_end" class="form-label">End Time <span class="text-danger">*</span></label>
                                <select name="time_end" id="modal_time_end" class="form-control" required>
                                    <option value="">Select End Time</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                                <select name="course_code" id="modal_course_code" class="select2 form-control" required>
                                    <option value="">Select Course Code</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_lecturer_id" class="form-label">Lecturer <span class="text-danger">*</span></label>
                                <select name="lecturer_id" id="modal_lecturer_id" class="select2 form-control" required>
                                    <option value="">Select Lecturer</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_activity" class="form-label">Activity <span class="text-danger">*</span></label>
                                <select name="activity" id="modal_activity" class="select2 form-control" required>
                                    <option value="">Select Activity</option>
                                    <option value="Lecture">Lecture</option>
                                    <option value="Practical">Practical</option>
                                    <option value="Workshop">Workshop</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_venue_id" class="form-label">Venue <span class="text-danger">*</span></label>
                                <select name="venue_id" id="modal_venue_id" class="select2 form-control" required>
                                    <option value="">Loading available venues...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_group_selection" class="form-label">Group Selection <span class="text-danger">*</span></label>
                                <select name="group_selection[]" id="modal_group_selection" class="select2 form-control" multiple required>
                                    <option value="All Groups" class="select-all-option">All Groups</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_timetable_semester_id" class="form-label">Timetable Semester <span class="text-danger">*</span></label>
                                <select name="timetable_semester_id" id="modal_timetable_semester_id" class="select2 form-control" required>
                                    <option value="">Select Timetable Semester</option>
                                    @foreach ($timetableSemesters as $semester)
                                        <option value="{{ $semester->id }}">{{ $semester->semester->name }} ({{ $semester->academic_year }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Timetable Modal -->
    <div class="modal fade" id="editTimetableModal" tabindex="-1" aria-labelledby="editTimetableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTimetableModalLabel">Edit Timetable Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTimetableForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_modal_id">
                        <input type="hidden" name="faculty_id" id="edit_modal_faculty_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_day" class="form-label">Day</label>
                                <input type="text" name="day" id="edit_modal_day" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_time_start" class="form-label">Start Time</label>
                                <input type="text" name="time_start" id="edit_modal_time_start" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_time_end" class="form-label">End Time <span class="text-danger">*</span></label>
                                <select name="time_end" id="edit_modal_time_end" class="select2 form-control" required>
                                    <option value="">Select End Time</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                                <select name="course_code" id="edit_modal_course_code" class="select2 form-control" required>
                                    <option value="">Select Course Code</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_lecturer_id" class="form-label">Lecturer <span class="text-danger">*</span></label>
                                <select name="lecturer_id" id="edit_modal_lecturer_id" class="select2 form-control" required>
                                    <option value="">Select Lecturer</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_activity" class="form-label">Activity <span class="text-danger">*</span></label>
                                <select name="activity" id="edit_modal_activity" class="select2 form-control" required>
                                    <option value="">Select Activity</option>
                                    <option value="Lecture">Lecture</option>
                                    <option value="Practical">Practical</option>
                                    <option value="Workshop">Workshop</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_venue_id" class="form-label">Venue <span class="text-danger">*</span></label>
                                <select name="venue_id" id="edit_modal_venue_id" class="select2 form-control" required>
                                    <option value="">Loading available venues...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_group_selection" class="form-label">Group Selection <span class="text-danger">*</span></label>
                                <select name="group_selection[]" id="edit_modal_group_selection" class="select2 form-control" multiple required>
                                    <option value="All Groups" class="select-all-option">All Groups</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_timetable_semester_id" class="form-label">Timetable Semester <span class="text-danger">*</span></label>
                                <select name="timetable_semester_id" id="edit_modal_timetable_semester_id" class="select2 form-control" required>
                                    <option value="">Select Timetable Semester</option>
                                    @foreach ($timetableSemesters as $semester)
                                        <option value="{{ $semester->id }}">{{ $semester->semester->name }} ({{ $semester->academic_year }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Show Timetable Modal -->
    <div class="modal fade" id="showTimetableModal" tabindex="-1" aria-labelledby="showTimetableModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showTimetableModalLabel">Timetable Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Course Code:</strong> <span id="show_course_code"></span></p>
                    <p><strong>Course Name:</strong> <span id="show_course_name"></span></p>
                    <p><strong>Activity:</strong> <span id="show_activity"></span></p>
                    <p><strong>Day:</strong> <span id="show_day"></span></p>
                    <p><strong>Time:</strong> <span id="show_time_start"></span> - <span id="show_time_end"></span></p>
                    <p><strong>Venue:</strong> <span id="show_venue"></span> (Capacity: <span id="show_capacity"></span>)</p>
                    <p><strong>Groups:</strong> <span id="show_groups"></span></p>
                    <p><strong>Lecturer:</strong> <span id="show_lecturer"></span></p>
                    <p><strong>Faculty:</strong> <span id="show_faculty"></span></p>
                    <p><strong>Semester:</strong> <span id="show_timetable_semester"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Timetable Semester Modal -->
    <div class="modal fade" id="addTimetableSemesterModal" tabindex="-1" aria-labelledby="addTimetableSemesterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTimetableSemesterModalLabel">Add Timetable Semester</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addTimetableSemesterForm" method="POST" action="{{ route('timetable-semesters.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_semester_id" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select name="semester_id" id="add_semester_id" class="select2 form-control" required>
                                <option value="">Select Semester</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="add_academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <input type="text" name="academic_year" id="add_academic_year" class="form-control" placeholder="e.g., 2025/2026" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="add_start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="add_end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Timetable Semester Modal -->
    <div class="modal fade" id="editTimetableSemesterModal" tabindex="-1" aria-labelledby="editTimetableSemesterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTimetableSemesterModalLabel">Update Timetable Semester</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTimetableSemesterForm" method="POST" action="{{ route('timetable-semesters.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_semester_id_hidden">
                        <div class="mb-3">
                            <label for="edit_semester_id" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select name="semester_id" id="edit_semester_id" class="select2 form-control" required>
                                <option value="">Select Semester</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <input type="text" name="academic_year" id="edit_academic_year" class="form-control" placeholder="e.g., 2025/2026" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Timetable Modal -->
    <div class="modal fade" id="importTimetableModal" tabindex="-1" aria-labelledby="importTimetableModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importTimetableModalLabel">Import Timetable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importTimetableForm" method="POST" action="{{ route('timetable.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="import_file" class="form-label">Upload Excel File <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="mb-3">
                            <label for="import_timetable_semester_id" class="form-label">Timetable Semester <span class="text-danger">*</span></label>
                            <select name="timetable_semester_id" id="import_timetable_semester_id" class="select2 form-control" required>
                                <option value="">Select Timetable Semester</option>
                                @foreach ($timetableSemesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->semester->name }} ({{ $semester->academic_year }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
    
    window.exportTimetable = function(draft) {
            const url = `{{ route('timetable.export') }}?draft=${encodeURIComponent(draft)}`;
            window.location.href = url;
        };
        $(document).ready(function() {
            const faculties = @json($faculties);
            const timetableSemesters = @json($timetableSemesters);
            let availableCourses = [];

            $('[data-bs-toggle="tooltip"]').tooltip();

            const initializeSelect2 = (selector, modalId = null) => {
                $(selector).select2({
                    dropdownParent: modalId ? $(modalId) : $('body'),
                    theme: 'classic',
                    placeholder: 'Select an option',
                    allowClear: true,
                    width: '100%'
                });
            };

            // Initialize all Select2
            initializeSelect2('#faculty');
            initializeSelect2('#generate_faculty_id', '#generateTimetableModal');
            initializeSelect2('#generate_venues', '#generateTimetableModal');
            initializeSelect2('.course-code', '#generateTimetableModal');
            initializeSelect2('.lecturer-select', '#generateTimetableModal');
            initializeSelect2('.group-selection', '#generateTimetableModal');
            initializeSelect2('.activity-select', '#generateTimetableModal');
            initializeSelect2('#modal_course_code', '#addTimetableModal');
            initializeSelect2('#modal_lecturer_id', '#addTimetableModal');
            initializeSelect2('#modal_venue_id', '#addTimetableModal');
            initializeSelect2('#modal_group_selection', '#addTimetableModal');
            initializeSelect2('#modal_time_end', '#addTimetableModal');
            initializeSelect2('#modal_activity', '#addTimetableModal');
            initializeSelect2('#modal_timetable_semester_id', '#addTimetableModal');
            initializeSelect2('#edit_modal_course_code', '#editTimetableModal');
            initializeSelect2('#edit_modal_lecturer_id', '#editTimetableModal');
            initializeSelect2('#edit_modal_venue_id', '#editTimetableModal');
            initializeSelect2('#edit_modal_group_selection', '#editTimetableModal');
            initializeSelect2('#edit_modal_time_end', '#editTimetableModal');
            initializeSelect2('#edit_modal_activity', '#editTimetableModal');
            initializeSelect2('#edit_modal_timetable_semester_id', '#editTimetableModal');
            initializeSelect2('#add_semester_id', '#addTimetableSemesterModal');
            initializeSelect2('#edit_semester_id', '#editTimetableSemesterModal');
            initializeSelect2('#import_timetable_semester_id', '#importTimetableModal');

            // ————————————————————————
            // UNIFIED: "All Groups" = Exclusive
            // ————————————————————————
            function setupAllGroupsExclusive(selectors) {
                selectors.forEach(selector => {
                    $(document).on('select2:select', selector, function (e) {
                        const val = e.params.data.id;
                        const $select = $(this);
                        if (val === 'All Groups') {
                            $select.val(['All Groups']).trigger('change');
                        } else {
                            const current = $select.val() || [];
                            if (current.includes('All Groups')) {
                                $select.val(current.filter(v => v !== 'All Groups')).trigger('change');
                            }
                        }
                    });
                });
            }
            setupAllGroupsExclusive([
                '#modal_group_selection',
                '#edit_modal_group_selection',
                '.group-selection'
            ]);

            // Faculty filter
            $('#faculty').on('select2:select', function() {
                $('#facultyFilterForm').submit();
            });

            // Reindex courses
            const reindexCourses = () => {
                $('.course-selection').each(function(index) {
                    $(this).find('.course-code').attr('name', `courses[${index}]`);
                    $(this).find('.lecturer-select').attr('name', `lecturers[${index}]`);
                    $(this).find('.group-selection').attr('name', `groups[${index}][]`);
                    $(this).find('.activity-select').attr('name', `activities[${index}]`);
                });
            };

            $('#add-course').click(function() {
                const index = $('.course-selection').length;
                const newCourse = `
                    <div class="course-selection mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <select name="courses[${index}]" class="select2 form-control course-code" required>
                                    <option value="">Select Course</option>
                                    ${availableCourses.map(c => `<option value="${c.course_code}">${c.course_code}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="lecturers[${index}]" class="select2 form-control lecturer-select" required>
                                    <option value="">Select Lecturer</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="activities[${index}]" class="select2 form-control activity-select" required>
                                    <option value="">Select Activity</option>
                                    <option value="Lecture">Lecture</option>
                                    <option value="Practical">Practical</option>
                                    <option value="Workshop">Workshop</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger remove-course"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <select name="groups[${index}][]" class="select2 form-control group-selection" multiple required>
                                    <option value="All Groups" class="select-all-option">All Groups</option>
                                </select>
                            </div>
                        </div>
                    </div>`;
                $('#course-selections').append(newCourse);
                initializeSelect2(`.course-selection:last .course-code`, '#generateTimetableModal');
                initializeSelect2(`.course-selection:last .lecturer-select`, '#generateTimetableModal');
                initializeSelect2(`.course-selection:last .group-selection`, '#generateTimetableModal');
                initializeSelect2(`.course-selection:last .activity-select`, '#generateTimetableModal');

                const facultyId = $('#generate_faculty_id').val();
                if (facultyId) {
                    $.ajax({
                        url: '{{ route('timetables.getFacultyGroups') }}',
                        method: 'GET',
                        data: { faculty_id: facultyId },
                        success: function(response) {
                            const $groupSelect = $(`.course-selection:last .group-selection`);
                            $groupSelect.empty().append('<option value="All Groups" class="select-all-option">All Groups</option>');
                            response.groups.forEach(g => $groupSelect.append(new Option(g.group_name, g.group_name)));
                            $groupSelect.trigger('change');
                        }
                    });
                }
            });

            $(document).on('click', '.remove-course', function() {
                if ($('.course-selection').length > 1) {
                    $(this).closest('.course-selection').remove();
                    reindexCourses();
                }
            });

            $('#generate_faculty_id').on('change', function() {
                const facultyId = $(this).val();
                if (!facultyId) {
                    availableCourses = [];
                    $('.course-code, .group-selection').empty().append('<option value="">Select</option>').trigger('change');
                    return;
                }
                $.ajax({
                    url: '{{ route('timetables.getFacultyCourses') }}',
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: function(response) {
                        availableCourses = response.course_codes;
                        $('.course-code').each(function() {
                            const $select = $(this);
                            const current = $select.val();
                            $select.empty().append('<option value="">Select Course</option>');
                            availableCourses.forEach(c => {
                                $select.append(new Option(c.course_code, c.course_code, false, c.course_code === current));
                            });
                            $select.trigger('change');
                        });
                    }
                });
                $.ajax({
                    url: '{{ route('timetables.getFacultyGroups') }}',
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: function(response) {
                        $('.group-selection').each(function() {
                            const $select = $(this);
                            const current = $select.val() || [];
                            $select.empty().append('<option value="All Groups" class="select-all-option">All Groups</option>');
                            response.groups.forEach(g => {
                                $select.append(new Option(g.group_name, g.group_name, false, current.includes(g.group_name)));
                            });
                            $select.trigger('change');
                        });
                    }
                });
            });

            $(document).on('change', '.course-code', function() {
                const courseCode = $(this).val();
                const $lecturerSelect = $(this).closest('.course-selection').find('.lecturer-select');
                $lecturerSelect.empty().append('<option value="">Select Lecturer</option>').trigger('change');
                if (!courseCode) return;
                $.ajax({
                    url: '{{ route('timetables.getCourseLecturers') }}',
                    method: 'GET',
                    data: { course_code: courseCode },
                    success: function(response) {
                        response.lecturers.forEach(l => {
                            $lecturerSelect.append(new Option(l.name, l.id));
                        });
                        $lecturerSelect.prop('disabled', response.lecturers.length === 0);
                        $lecturerSelect.trigger('change');
                    }
                });
            });

            // Add Modal: "+" click
            $(document).on('click', '.add-timetable', function() {
                const facultyId = $(this).data('faculty');
                if (!facultyId) return showAlert('error', 'Error', 'Select faculty first.');

                $('#modal_faculty_id').val(facultyId);
                $('#modal_day').val($(this).data('day'));
                $('#modal_time_start').val($(this).data('time'));

                const timeSlots = @json($timeSlots);
                const startIndex = timeSlots.indexOf($(this).data('time'));
                $('#modal_time_end').empty().append('<option value="">Select End Time</option>');
                timeSlots.slice(startIndex + 1).forEach(t => $('#modal_time_end').append(new Option(t, t)));
                initializeSelect2('#modal_time_end', '#addTimetableModal');

                $.ajax({
                    url: '{{ route('timetables.getFacultyCourses') }}',
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: function(response) {
                        $('#modal_course_code').empty().append('<option value="">Select Course Code</option>');
                        response.course_codes.forEach(c => $('#modal_course_code').append(new Option(c.course_code, c.course_code)));
                        $('#modal_course_code').trigger('change');
                    }
                });

                $.ajax({
                    url: '{{ route('timetables.getFacultyGroups') }}',
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: function(response) {
                        const $select = $('#modal_group_selection');
                        $select.empty().append('<option value="All Groups" class="select-all-option">All Groups</option>');
                        response.groups.forEach(g => $select.append(new Option(g.group_name, g.group_name)));
                        $select.val(null).trigger('change');
                    }
                });

                $('#modal_lecturer_id').empty().append('<option value="">Select Lecturer</option>').trigger('change');
                $('#modal_activity').val('').trigger('change');
                $('#modal_timetable_semester_id').val('').trigger('change');
                $('#addTimetableModal').modal('show');
            });

            // Edit Modal
            $(document).on('click', '.edit-timetable', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: '{{ route('timetable.show', ':id') }}'.replace(':id', id),
                    method: 'GET',
                    success: function(data) {
                        $('#editTimetableForm').attr('action', '{{ route('timetable.update', ':id') }}'.replace(':id', id));
                        $('#edit_modal_id').val(id);
                        $('#edit_modal_faculty_id').val(data.faculty_id);
                        $('#edit_modal_day').val(data.day);
                        $('#edit_modal_time_start').val(data.time_start);

                        const timeSlots = @json($timeSlots);
                        const startIndex = timeSlots.indexOf(data.time_start);
                        $('#edit_modal_time_end').empty().append('<option value="">Select End Time</option>');
                        timeSlots.slice(startIndex + 1).forEach(t => {
                            $('#edit_modal_time_end').append(new Option(t, t, false, t === data.time_end));
                        });
                        initializeSelect2('#edit_modal_time_end', '#editTimetableModal');
                        $('#edit_modal_time_end').val(data.time_end).trigger('change');

                        $.ajax({
                            url: '{{ route('timetables.getFacultyCourses') }}',
                            method: 'GET',
                            data: { faculty_id: data.faculty_id },
                            success: function(response) {
                                $('#edit_modal_course_code').empty().append('<option value="">Select Course Code</option>');
                                response.course_codes.forEach(c => {
                                    $('#edit_modal_course_code').append(new Option(c.course_code, c.course_code, false, c.course_code === data.course_code));
                                });
                                $('#edit_modal_course_code').trigger('change');
                            }
                        });

                        if (data.course_code) {
                            $.ajax({
                                url: '{{ route('timetables.getCourseLecturers') }}',
                                method: 'GET',
                                data: { course_code: data.course_code },
                                success: function(response) {
                                    $('#edit_modal_lecturer_id').empty().append('<option value="">Select Lecturer</option>');
                                    response.lecturers.forEach(l => {
                                        $('#edit_modal_lecturer_id').append(new Option(l.name, l.id, false, l.id == data.lecturer_id));
                                    });
                                    $('#edit_modal_lecturer_id').trigger('change');
                                }
                            });
                        }

                        $.ajax({
                            url: '{{ route('timetables.getFacultyGroups') }}',
                            method: 'GET',
                            data: { faculty_id: data.faculty_id },
                            success: function(response) {
                                const $select = $('#edit_modal_group_selection');
                                $select.empty().append('<option value="All Groups" class="select-all-option">All Groups</option>');
                                const saved = data.group_selection ? data.group_selection.split(',') : [];

                                response.groups.forEach(g => $select.append(new Option(g.group_name, g.group_name)));

                                if (saved.length === 1 && saved[0] === 'All Groups') {
                                    $select.val(['All Groups']);
                                } else {
                                    $select.val(saved.filter(v => v !== 'All Groups'));
                                }
                                $select.trigger('change');
                            }
                        });

                        $('#edit_modal_activity').val(data.activity || '').trigger('change');
                        $('#edit_modal_venue_id').data('current', data.venue_id || '');
                        $('#edit_modal_timetable_semester_id').val(data.timetable_semester_id || '').trigger('change');

                        $('#editTimetableModal').modal('show');
                    }
                });
            });

            // Course → Lecturer
            ['#modal_course_code', '#edit_modal_course_code'].forEach(s => {
                $(s).on('change', function() {
                    const code = $(this).val();
                    const target = s === '#modal_course_code' ? '#modal_lecturer_id' : '#edit_modal_lecturer_id';
                    $(target).empty().append('<option value="">Select Lecturer</option>').trigger('change');
                    if (!code) return;
                    $.ajax({
                        url: '{{ route('timetables.getCourseLecturers') }}',
                        method: 'GET',
                        data: { course_code: code },
                        success: function(r) {
                            r.lecturers.forEach(l => $(target).append(new Option(l.name, l.id)));
                            $(target).prop('disabled', r.lecturers.length === 0);
                            $(target).trigger('change');
                        }
                    });
                });
            });

            
$('#generateTimetableForm').on('submit', function(e) {
    e.preventDefault();

    // Disable button to prevent double click
    const $submitBtn = $(this).find('button[type="submit"]');
    $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');

    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                html: `<div class="text-left">${response.message || 'Timetable generated successfully!'}</div>`,
                confirmButtonColor: '#6f42c1',
                timer: 5000,
                timerProgressBar: true
            }).then(() => {
                location.reload(); // Reload to see new timetable
            });
        },
        error: function(xhr) {
            let errorMsg = 'An error occurred while generating the timetable.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                if (xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
            }

            Swal.fire({
                icon: 'error',
                title: 'Generation Failed',
                html: `<div class="text-left">${errorMsg}</div>`,
                confirmButtonColor: '#dc3545'
            });
        },
        complete: function() {
            $submitBtn.prop('disabled', false).html('Generate');
        }
    });
});

            // Form submissions (Add/Edit)
            ['#addTimetableForm', '#editTimetableForm'].forEach(f => {
                $(f).on('submit', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(r) {
                            showAlert('success', 'Success', r.message);
                            $(f).closest('.modal').modal('hide');
                            location.reload();
                        },
                        error: function(xhr) {
                            const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>');
                            showAlert('error', 'Error', msg);
                        }
                    });
                });
            });

            

            const showAlert = (type, title, msg) => {
                Swal.fire({ icon: type, title, html: `<div class="text-left">${msg}</div>`, confirmButtonText: 'OK', confirmButtonColor: type === 'error' ? '#dc3545' : '#6f42c1' });
            };
            

        });
    function loadAvailableVenues(modalPrefix, excludeId = null) {
    const day = $(`#${modalPrefix}_day`).val();
    const start = $(`#${modalPrefix}_time_start`).val();
    const end = $(`#${modalPrefix}_time_end`).val();
    const facultyId = $(`#${modalPrefix}_faculty_id`).val(); // or modal_faculty_id / edit_modal_faculty_id

    const $venueSelect = $(`#${modalPrefix}_venue_id`);

    if (!day || !start || !end || !facultyId) {
        $venueSelect.empty().append('<option value="">Select time and day first</option>').trigger('change');
        return;
    }

    $.ajax({
        url: '{{ route('timetables.available-venues') }}',
        method: 'GET',
        data: {
            day: day,
            time_start: start,
            time_end: end,
            faculty_id: facultyId,
            exclude_id: excludeId
        },
        success: function(response) {
            $venueSelect.empty().append('<option value="">Select Venue</option>');
            response.venues.forEach(function(venue) {
                $venueSelect.append(new Option(venue.text, venue.id));
            });
            $venueSelect.trigger('change');
        },
        error: function() {
            $venueSelect.empty().append('<option value="">Error loading venues</option>');
            showAlert('error', 'Error', 'Could not load available venues.');
        }
    });
}

// For Add Modal
$(document).on('change', '#modal_day, #modal_time_start, #modal_time_end', function() {
    loadAvailableVenues('modal');
});

// For Edit Modal
$(document).on('change', '#edit_modal_day, #edit_modal_time_start, #edit_modal_time_end', function() {
    const excludeId = $('#edit_modal_id').val();
    loadAvailableVenues('edit_modal', excludeId);
});

// Trigger on modal open (in case values are pre-filled)
$('#addTimetableModal').on('shown.bs.modal', function() {
    loadAvailableVenues('modal');
});

$('#editTimetableModal').on('shown.bs.modal', function() {
    const excludeId = $('#edit_modal_id').val();
    loadAvailableVenues('edit_modal', excludeId);
});
    
    </script>
@endsection
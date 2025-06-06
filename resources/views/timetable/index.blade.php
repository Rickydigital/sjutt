@extends('components.app-main-layout')

@section('content')
    <style>
        /* Existing styles unchanged */
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
            display: inline-block; /* Changed to inline-block for side-by-side */
            width: auto; /* Allow dynamic width based on content */
            margin: 2px; /* Add small margin for spacing */
            padding: 5px; /* Ensure padding for content */
            vertical-align: top; /* Align to top for consistency */
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
        .select2-container--classic .select2-selection--single {
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }

        .form-control:focus,
        .select2-container--classic .select2-selection--single:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 5px rgba(111, 66, 193, 0.5);
        }

        .select2-container--classic .select2-selection--multiple {
            border-radius: 8px;
            border: 1px solid #ced4da;
        }

        .select-all-option {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        /* Add new styles for the "+" icon in occupied slots */
        .add-session-container {
            margin-top: 5px; /* Reduced margin for better fit */
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-block; /* Align with course-cell */
            vertical-align: top;
        }

        .add-session-container:hover {
            background-color: #e9ecef;
        }

        /* New style for the container of side-by-side cells */
        .course-container {
            display: flex;
            justify-content: space-between; /* Space out items evenly */
            flex-wrap: wrap; /* Allow wrapping if too many items */
            padding: 5px;
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
                    <i class="fas fa-magic me-1"></i> Generate Timetable
                </button>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#importTimetableModal">
                    <i class="fas fa-download me-1"></i> Import
                </button>
                <a href="{{ route('timetable.export') }}" class="btn btn-success">
                    <i class="fas fa-upload me-1"></i> Export
                </a>
            </div>
        </div>
    </div>

    <!-- Faculty Filter -->
    <div class="row mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <form method="GET" action="{{ route('timetable.index') }}">
                <div class="mb-3">
                    <label for="faculty" class="form-label fw-semibold">Select Faculty</label>
                    <select name="faculty" id="faculty" class="form-control select2" onchange="this.form.submit()">
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
                                                        <!-- Add "+" icon below the sessions -->
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
                            <div class="col-md-12 mb-3 venue-selection">
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
                                        <div class="row">
                                            <div class="col-md-4">
                                                <select name="courses[]" class="select2 form-control course-code" required>
                                                    <option value="">Select Course</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="sessions_per_week[]" class="form-control" placeholder="Sessions/Week" min="1" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="hours_per_session[]" class="form-control" placeholder="Hours/Session" min="1" max="3" required>
                                            </div>
                                            <div class="col-md-3">
                                                <select name="lecturers[]" class="select2 form-control lecturer-select" required>
                                                    <option value="">Select Lecturer</option>
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
    <div class="modal fade" id="addTimetableModal" tabindex="-1" aria-labelledby="addTimetableModalLabel"
        aria-hidden="true">
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
                                <input type="text" name="time_start" id="modal_time_start" class="form-control"
                                    readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_time_end" class="form-label">End Time <span
                                        class="text-danger">*</span></label>
                                <select name="time_end" id="modal_time_end" class="form-control" required>
                                    <option value="">Select End Time</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_course_code" class="form-label">Course Code <span
                                        class="text-danger">*</span></label>
                                <select name="course_code" id="modal_course_code" class="select2 form-control" required>
                                    <option value="">Select Course Code</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_lecturer_id" class="form-label">Lecturer <span
                                        class="text-danger">*</span></label>
                                <select name="lecturer_id" id="modal_lecturer_id" class="select2 form-control" required>
                                    <option value="">Select Lecturer</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_activity" class="form-label">Activity <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="activity" id="modal_activity" class="form-control"
                                    value="Lecture" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_venue_id" class="form-label">Venue <span
                                        class="text-danger">*</span></label>
                                <select name="venue_id" id="modal_venue_id" class="select2 form-control" required>
                                    @foreach ($venues as $venue)
                                        <option value="{{ $venue->id }}" data-capacity="{{ $venue->capacity }}">
                                            {{ $venue->name }} (Capacity: {{ $venue->capacity }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_group_selection" class="form-label">Group Selection <span
                                        class="text-danger">*</span></label>
                                <select name="group_selection[]" id="modal_group_selection" class="select2 form-control"
                                    multiple required>
                                    <option value="All Groups" class="select-all-option">All Groups</option>
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
    <div class="modal fade" id="editTimetableModal" tabindex="-1" aria-labelledby="editTimetableModalLabel"
        aria-hidden="true">
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
                        <input type="hidden" name="faculty_id" id="edit_modal_faculty_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_day" class="form-label">Day</label>
                                <input type="text" name="day" id="edit_modal_day" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_time_start" class="form-label">Start Time</label>
                                <input type="text" name="time_start" id="edit_modal_time_start" class="form-control"
                                    readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_time_end" class="form-label">End Time <span
                                        class="text-danger">*</span></label>
                                <select name="time_end" id="edit_modal_time_end" class="form-control" required>
                                    <option value="">Select End Time</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_course_code" class="form-label">Course Code <span
                                        class="text-danger">*</span></label>
                                <select name="course_code" id="edit_modal_course_code" class="select2 form-control"
                                    required>
                                    <option value="">Select Course Code</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_lecturer_id" class="form-label">Lecturer <span
                                        class="text-danger">*</span></label>
                                <select name="lecturer_id" id="edit_modal_lecturer_id" class="select2 form-control"
                                    required>
                                    <option value="">Select Lecturer</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_activity" class="form-label">Activity <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="activity" id="edit_modal_activity" class="form-control"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_venue_id" class="form-label">Venue <span
                                        class="text-danger">*</span></label>
                                <select name="venue_id" id="edit_modal_venue_id" class="select2 form-control" required>
                                    @foreach ($venues as $venue)
                                        <option value="{{ $venue->id }}" data-capacity="{{ $venue->capacity }}">
                                            {{ $venue->name }} (Capacity: {{ $venue->capacity }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_modal_group_selection" class="form-label">Group Selection <span
                                        class="text-danger">*</span></label>
                                <select name="group_selection[]" id="edit_modal_group_selection"
                                    class="select2 form-control" multiple required>
                                    <option value="All Groups" class="select-all-option">All Groups</option>
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
    <div class="modal fade" id="showTimetableModal" tabindex="-1" aria-labelledby="showTimetableModalLabel"
        aria-hidden="true">
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
                    <p><strong>Venue:</strong> <span id="show_venue"></span> (Capacity: <span
                            id="show_venue_capacity"></span>)</p>
                    <p><strong>Groups:</strong> <span id="show_group_details"></span></p>
                    <p><strong>Lecturer:</strong> <span id="show_lecturer"></span></p>
                    <p><strong>Faculty:</strong> <span id="show_faculty"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Timetable Modal -->
    <div class="modal fade" id="importTimetableModal" tabindex="-1" aria-labelledby="importTimetableModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importTimetableModalLabel">Import Timetable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importTimetableForm" method="POST" action="{{ route('timetable.import') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="import_file" class="form-label">Upload Excel File <span
                                    class="text-danger">*</span></label>
                            <input type="file" name="file" id="import_file" class="form-control"
                                accept=".xlsx,.xls,.csv" required>
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
        $(document).ready(function() {
            // Debug: Log faculties data
            const faculties = @json($faculties);
            console.log('Faculties available:', faculties);

            // Store available courses globally
            let availableCourses = [];

            // Ensure Bootstrap modal initialization
            $('#editTimetableModal').on('shown.bs.modal', function () {
                console.log('Edit modal opened');
            });

            // Initialize Select2
            const initializeSelect2 = (selector, modalId = null) => {
                $(selector).select2({
                    dropdownParent: modalId ? $(modalId) : null,
                    theme: 'classic',
                    placeholder: 'Select an option',
                    allowClear: false,
                    width: '100%'
                });
            };

            initializeSelect2('#modal_course_code', '#addTimetableModal');
            initializeSelect2('#modal_lecturer_id', '#addTimetableModal');
            initializeSelect2('#modal_venue_id', '#addTimetableModal');
            initializeSelect2('#modal_group_selection', '#addTimetableModal');
            initializeSelect2('#edit_modal_course_code', '#editTimetableModal');
            initializeSelect2('#edit_modal_lecturer_id', '#editTimetableModal');
            initializeSelect2('#edit_modal_venue_id', '#editTimetableModal');
            initializeSelect2('#edit_modal_group_selection', '#editTimetableModal');
            initializeSelect2('#faculty');
            initializeSelect2('#generate_faculty_id', '#generateTimetableModal');
            initializeSelect2('#generate_venues', '#generateTimetableModal');
            initializeSelect2('.course-code', '#generateTimetableModal');
            initializeSelect2('.lecturer-select', '#generateTimetableModal');
            initializeSelect2('.group-selection', '#generateTimetableModal');

            // Handle faculty filter change
            $('#faculty').on('select2:select', function() {
                const facultyId = $(this).val();
                console.log('Faculty selected:', facultyId);
                if (facultyId) {
                    $('#facultyFilterForm').submit();
                } else {
                    console.log('No faculty selected, clearing timetable');
                    // Optionally redirect to clear timetable: window.location = '{{ route('timetable.index') }}';
                }
            });

            // Show SweetAlert2 if no faculties
            if (Object.keys(faculties).length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Faculties',
                    html: '<div class="text-left">No faculties are available. Please add faculties in the system.</div>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#6f42c1',
                    background: '#fff',
                    customClass: {
                        popup: 'rounded-lg shadow-lg',
                        confirmButton: 'btn btn-primary'
                    }
                });
            };

            // Function to reindex course selections
            const reindexCourses = () => {
                $('.course-selection').each(function(index) {
                    $(this).find('.course-code').attr('name', `courses[${index}]`);
                    $(this).find('input[name^="sessions_per_week"]').attr('name', `sessions_per_week[${index}]`);
                    $(this).find('input[name^="hours_per_session"]').attr('name', `hours_per_session[${index}]`);
                    $(this).find('.lecturer-select').attr('name', `lecturers[${index}]`);
                    $(this).find('.group-selection').attr('name', `groups[${index}][]`);
                });
            };

            // Add new course selection
            $('#add-course').click(function() {
                const index = $('.course-selection').length; // Use current count as index
                const newCourse = `
                    <div class="course-selection mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <select name="courses[${index}]" class="select2 form-control course-code" required>
                                    <option value="">Select Course</option>
                                    ${availableCourses.map(course => `<option value="${course.course_code}">${course.course_code}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="sessions_per_week[${index}]" class="form-control" placeholder="Sessions/Week" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="hours_per_session[${index}]" class="form-control" placeholder="Hours/Session" min="1" max="3" required>
                            </div>
                            <div class="col-md-3">
                                <select name="lecturers[${index}]" class="select2 form-control lecturer-select" required>
                                    <option value="">Select Lecturer</option>
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

                // Populate groups for the new section if faculty is selected
                const facultyId = $('#generate_faculty_id').val();
                if (facultyId) {
                    $.ajax({
                        url: '{{ route('timetables.getFacultyGroups') }}',
                        method: 'GET',
                        data: { faculty_id: facultyId },
                        success: function(response) {
                            const $groupSelect = $(`.course-selection:last .group-selection`);
                            $groupSelect.empty().append('<option value="All Groups" class="select-all-option">All Groups</option>');
                            response.groups.forEach(group => {
                                $groupSelect.append(new Option(group.group_name, group.group_name));
                            });
                            $groupSelect.trigger('change');
                        },
                        error: function(xhr) {
                            showAlert('error', 'Error', 'Failed to load groups for new course section.');
                        }
                    });
                }
            });

            // Remove course selection
            $(document).on('click', '.remove-course', function() {
                if ($('.course-selection').length > 1) {
                    $(this).closest('.course-selection').remove();
                    reindexCourses(); // Reindex after removal
                }
            });

            // Fetch courses and groups on faculty change
            $('#generate_faculty_id').on('change', function() {
                const facultyId = $(this).val();
                if (!facultyId) {
                    availableCourses = [];
                    $('.course-code').empty().append('<option value="">Select Course</option>').trigger('change');
                    $('.group-selection').empty().append('<option value="All Groups" class="select-all-option">All Groups</option>').trigger('change');
                    return;
                }

                $.ajax({
                    url: '{{ route('timetables.getFacultyCourses') }}',
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: function(response) {
                        availableCourses = response.course_codes; // Store courses globally
                        $('.course-code').each(function() {
                            const $select = $(this);
                            $select.empty().append('<option value="">Select Course</option>');
                            availableCourses.forEach(course => {
                                $select.append(new Option(course.course_code, course.course_code));
                            });
                            $select.trigger('change');
                        });

                        $.ajax({
                            url: '{{ route('timetables.getFacultyGroups') }}',
                            method: 'GET',
                            data: { faculty_id: facultyId },
                            success: function(response) {
                                $('.group-selection').each(function() {
                                    const $select = $(this);
                                    $select.empty().append('<option value="All Groups" class="select-all-option">All Groups</option>');
                                    response.groups.forEach(group => {
                                        $select.append(new Option(group.group_name, group.group_name));
                                    });
                                    $select.trigger('change');
                                });
                            },
                            error: function(xhr) {
                                showAlert('error', 'Error', 'Failed to load groups.');
                            }
                        });
                    },
                    error: function(xhr) {
                        availableCourses = [];
                        showAlert('error', 'Error', 'Failed to load courses.');
                    }
                });
            });

            // Fetch lecturers on course change
            $(document).on('change', '.course-code', function() {
                const courseCode = $(this).val();
                const $lecturerSelect = $(this).closest('.course-selection').find('.lecturer-select');
                $lecturerSelect.empty().append('<option value="">Select Lecturer</option>').trigger('change');

                if (!courseCode) {
                    $lecturerSelect.prop('disabled', true);
                    return;
                }

                $.ajax({
                    url: '{{ route('timetables.getCourseLecturers') }}',
                    method: 'GET',
                    data: { course_code: courseCode },
                    success: function(response) {
                        $lecturerSelect.empty().append('<option value="">Select Lecturer</option>');
                        response.lecturers.forEach(lecturer => {
                            $lecturerSelect.append(new Option(lecturer.name, lecturer.id));
                        });
                        $lecturerSelect.prop('disabled', response.lecturers.length === 0);
                    },
                    error: function(xhr) {
                        showAlert('error', 'Error', 'Failed to load lecturers.');
                    }
                });
            });

            // Handle form submission
            $('#generateTimetableForm').on('submit', function(e) {
                e.preventDefault();
                reindexCourses(); // Ensure indices are correct before submission
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        showAlert('success', 'Success', 'Timetable generated successfully. Review the results below.');
                        $('#generateTimetableModal').modal('hide');
                        location.reload(); // Reload to show updated timetable
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || { error: ['Failed to generate timetable.'] };
                        const errorMessage = Object.values(errors).flat().join('<br>');
                        showAlert('error', 'Error', errorMessage);
                    }
                });
            });

            // Handle Select All for group selection
            ['#modal_group_selection', '#edit_modal_group_selection'].forEach(selector => {
                $(selector).on('change', function() {
                    if ($(this).val() && $(this).val().includes('All Groups')) {
                        $(this).find('option').not('[value="All Groups"]').prop('selected', true);
                        $(this).trigger('change');
                    }
                });
            });

            // Show SweetAlert2 for errors or success
            const showAlert = (type, title, message) => {
                Swal.fire({
                    icon: type,
                    title: title,
                    html: `<div class="text-left">${message}</div>`,
                    confirmButtonText: 'OK',
                    confirmButtonColor: type === 'error' ? '#dc3545' : '#6f42c1',
                    background: '#fff',
                    customClass: {
                        popup: 'rounded-lg shadow-lg',
                        confirmButton: 'btn btn-primary'
                    }
                });
            };

            // Handle "+" click to open add modal
            $(document).on('click', '.add-timetable', function(e) {
                e.preventDefault();
                const facultyId = $(this).data('faculty');
                if (!facultyId) {
                    showAlert('error', 'Error', 'Please select a faculty first.');
                    return;
                }
                const day = $(this).data('day');
                const timeStart = $(this).data('time');

                $('#modal_faculty_id').val(facultyId);
                $('#modal_day').val(day);
                $('#modal_time_start').val(timeStart);

                const timeSlots = @json($timeSlots);
                const startIndex = timeSlots.indexOf(timeStart);
                const endOptions = timeSlots.slice(startIndex + 1);
                $('#modal_time_end').empty().append('<option value="">Select End Time</option>');
                endOptions.forEach(time => {
                    $('#modal_time_end').append(new Option(time, time));
                });

                $.ajax({
                    url: '{{ route('timetables.getFacultyCourses') }}',
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: function(response) {
                        $('#modal_course_code').empty().append('<option value="">Select Course Code</option>');
                        response.course_codes.forEach(course => {
                            $('#modal_course_code').append(new Option(course.course_code, course.course_code));
                        });
                        $('#modal_course_code').trigger('change');
                    },
                    error: function(xhr) {
                        console.error('Error fetching courses:', xhr.responseText);
                        showAlert('error', 'Error', 'Failed to load courses.');
                    }
                });

                $.ajax({
                    url: '{{ route('timetables.getFacultyGroups') }}',
                    method: 'GET',
                    data: { faculty_id: facultyId },
                    success: function(response) {
                        $('#modal_group_selection').empty().append('<option value="All Groups" class="select-all-option">All Groups</option>');
                        response.groups.forEach(group => {
                            $('#modal_group_selection').append(new Option(group.group_name, group.group_name));
                        });
                        $('#modal_group_selection').trigger('change');
                    },
                    error: function(xhr) {
                        console.error('Error fetching groups:', xhr.responseText);
                        showAlert('error', 'Error', 'Failed to load groups.');
                    }
                });

                $('#modal_lecturer_id').empty().append('<option value="">Select Lecturer</option>').trigger('change');
                $('#addTimetableModal').modal('show');
            });

            // Handle course code change to fetch lecturers
            ['#modal_course_code', '#edit_modal_course_code'].forEach(selector => {
                $(selector).on('change', function() {
                    const courseCode = $(this).val();
                    const $lecturerSelect = $(selector === '#modal_course_code' ? '#modal_lecturer_id' : '#edit_modal_lecturer_id');
                    $lecturerSelect.empty().append('<option value="">Select Lecturer</option>').trigger('change');

                    if (!courseCode) {
                        $lecturerSelect.prop('disabled', true);
                        return;
                    }

                    $.ajax({
                        url: '{{ route('timetables.getCourseLecturers') }}',
                        method: 'GET',
                        data: { course_code: courseCode },
                        success: function(response) {
                            $lecturerSelect.empty().append('<option value="">Select Lecturer</option>');
                            response.lecturers.forEach(lecturer => {
                                $lecturerSelect.append(new Option(lecturer.name, lecturer.id));
                            });
                            $lecturerSelect.prop('disabled', response.lecturers.length === 0);
                        },
                        error: function(xhr) {
                            console.error('Error fetching lecturers:', xhr.responseText);
                            showAlert('error', 'Error', 'Failed to load lecturers.');
                        }
                    });
                });
            });

            // Handle form submission (add and edit)
            ['#addTimetableForm', '#editTimetableForm'].forEach(formId => {
                $(formId).on('submit', function(e) {
                    e.preventDefault();
                    const $form = $(this);
                    const venueId = $form.find('[name="venue_id"]').val();
                    const venueCapacityRaw = $form.find('option[value="' + venueId + '"]').data('capacity');
                    console.log('Venue ID:', venueId, 'Raw Capacity:', venueCapacityRaw); // Debug log
                    const venueCapacity = parseInt(venueCapacityRaw) || 0;
                    const groups = $form.find('[name="group_selection[]"]').val();

                    if (!groups || groups.length === 0) {
                        showAlert('error', 'Error', 'Please select at least one group.');
                        return;
                    }

                    $.ajax({
                        url: $form.attr('action'),
                        method: 'POST',
                        data: $form.serialize(),
                        success: function(response) {
                            showAlert('success', 'Success', response.message);
                            $form.closest('.modal').modal('hide');
                            location.reload();
                        },
                        error: function(xhr) {
                            console.error('Form submission error:', xhr.responseJSON);
                            const errors = xhr.responseJSON?.errors || { error: ['Unknown error occurred.'] };
                            const errorMessage = Object.values(errors).flat().join('<br>');
                            showAlert('error', 'Error', errorMessage);
                        }
                    });
                });
            });

            // Handle edit timetable click
            $(document).on('click', '.edit-timetable', function(e) {
                e.preventDefault();
                const timetableId = $(this).data('id');
                console.log('Edit timetable clicked, ID:', timetableId); // Debug log
                $.ajax({
                    url: '{{ route('timetable.show', ':id') }}'.replace(':id', timetableId),
                    method: 'GET',
                    success: function(data) {
                        console.log('Timetable data fetched:', data); // Debug log
                        $('#editTimetableForm').attr('action', '{{ route('timetable.update', ':id') }}'.replace(':id', timetableId));
                        $('#edit_modal_faculty_id').val(data.faculty_id);
                        $('#edit_modal_day').val(data.day);
                        // Format time_start to ensure HH:mm
                        const timeStart = moment(data.time_start, ['H:mm:ss', 'H:mm']).format('HH:mm');
                        $('#edit_modal_time_start').val(timeStart);
                        $('#edit_modal_time_end').empty().append('<option value="">Select End Time</option>');
                        const timeSlots = @json($timeSlots);
                        const startIndex = timeSlots.indexOf(timeStart);
                        timeSlots.slice(startIndex + 1).forEach(time => {
                            const timeEnd = moment(data.time_end, ['H:mm:ss', 'H:mm']).format('HH:mm');
                            $('#edit_modal_time_end').append(new Option(time, time, false, time === timeEnd));
                        });

                        $.ajax({
                            url: '{{ route('timetables.getFacultyCourses') }}',
                            method: 'GET',
                            data: { faculty_id: data.faculty_id },
                            success: function(response) {
                                $('#edit_modal_course_code').empty().append('<option value="">Select Course Code</option>');
                                response.course_codes.forEach(course => {
                                    $('#edit_modal_course_code').append(new Option(course.course_code, course.course_code, false, course.course_code === data.course_code));
                                });
                                $('#edit_modal_course_code').trigger('change');
                            },
                            error: function(xhr) {
                                console.error('Error fetching courses:', xhr.responseText);
                                showAlert('error', 'Error', 'Failed to load courses.');
                            }
                        });

                        $.ajax({
                            url: '{{ route('timetables.getFacultyGroups') }}',
                            method: 'GET',
                            data: { faculty_id: data.faculty_id },
                            success: function(response) {
                                $('#edit_modal_group_selection').empty().append('<option value="All Groups" class="select-all-option">All Groups</option>');
                                const selectedGroups = data.group_selection ? data.group_selection.split(',') : [];
                                response.groups.forEach(group => {
                                    $('#edit_modal_group_selection').append(new Option(group.group_name, group.group_name, false, selectedGroups.includes(group.group_name)));
                                });
                                $('#edit_modal_group_selection').trigger('change');
                            },
                            error: function(xhr) {
                                console.error('Error fetching groups:', xhr.responseText);
                                showAlert('error', 'Error', 'Failed to load groups.');
                            }
                        });

                        $('#edit_modal_activity').val(data.activity);
                        $('#edit_modal_venue_id').val(data.venue_id).trigger('change');
                        $('#editTimetableModal').modal('show');
                    },
                    error: function(xhr) {
                        console.error('Error fetching timetable:', xhr.responseText);
                        showAlert('error', 'Error', 'Failed to load timetable details.');
                    }
                });
            });

            // Handle show timetable click
            $(document).on('click', '.show-timetable', function(e) {
                e.preventDefault();
                const timetableId = $(this).data('id');
                $.ajax({
                    url: '{{ route('timetable.show', ':id') }}'.replace(':id', timetableId),
                    method: 'GET',
                    success: function(data) {
                        $('#show_course_code').text(data.course_code);
                        $('#show_course_name').text(data.name || 'N/A');
                        $('#show_activity').text(data.activity);
                        $('#show_day').text(data.day);
                        $('#show_time_start').text(data.time_start);
                        $('#show_time_end').text(data.time_end);
                        $('#show_venue').text(data.venue?.name || 'N/A');
                        $('#show_venue_capacity').text(data.venue?.capacity || 'N/A');
                        $('#show_group_details').text(data.group_details || 'N/A');
                        $('#show_lecturer').text(data.lecturer?.name || 'N/A');
                        $('#show_faculty').text(data.faculty?.name || 'N/A');
                        $('#showTimetableModal').modal('show');
                    },
                    error: function(xhr) {
                        console.error('Error fetching timetable:', xhr.responseText);
                        showAlert('error', 'Error', 'Failed to load timetable details.');
                    }
                });
            });

            // Handle delete confirmation
            $(document).on('submit', '.delete-timetable-form', function(e) {
                e.preventDefault();
                const $form = $(this);
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This timetable entry will be deleted permanently.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6f42c1',
                    confirmButtonText: 'Yes, delete it!',
                    customClass: {
                        popup: 'rounded-lg shadow-lg',
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-primary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: $form.attr('action'),
                            method: 'POST',
                            data: $form.serialize(),
                            success: function() {
                                showAlert('success', 'Success', 'Timetable entry deleted successfully.');
                                location.reload();
                            },
                            error: function(xhr) {
                                console.error('Error deleting timetable:', xhr.responseText);
                                showAlert('error', 'Error', 'Failed to delete timetable.');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection


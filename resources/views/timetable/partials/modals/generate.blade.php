<div class="modal fade" id="generateTimetableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Timetable</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="generateTimetableForm" method="POST" action="{{ route('timetable.generate') }}" class="d-flex flex-column h-100 generate-timetable-form">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">Timetable Setup <span class="text-danger">*</span></label>
                            <select name="setup_id" id="generate_setup_id" class="form-control" required>
                                <option value="">Select Setup</option>
                                @foreach($timetableSemesters as $setup)
                                <option value="{{ $setup->id }}" {{ (string)($selectedSetupId ?? $timetableSemester?->
                                    id) === (string)$setup->id ? 'selected' : '' }}>
                                    {{ $setup->semester->name ?? 'Unknown Semester' }} • {{ $setup->academic_year }} •
                                    {{ ucfirst($setup->status ?? 'draft') }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Faculty <span class="text-danger">*</span></label>
                            <select name="faculty_id" id="generate_faculty_id" class="form-control" required>
                                <option value="">Select Faculty</option>
                                @foreach($faculties as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="generation_mode" id="generation_mode_hidden">

                        <div class="col-lg-12">
                            <label class="form-label">Venues <span class="text-danger">*</span></label>
                            <select name="venues[]" id="generate_venues" class="form-control" multiple required>
                                @foreach($venues as $venue)
                                <option value="{{ $venue->id }}">
                                    {{ $venue->name }} (Capacity: {{ $venue->capacity }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Course Sessions <span class="text-danger">*</span></label>
                            <div id="course-selections">
                                <div class="tt-course-selection course-selection">
                                    <div class="row g-3">
                                        <div class="col-lg-3">
                                            <label class="form-label">Course</label>
                                            <select name="courses[0]" class="form-control course-code" required>
                                                <option value="">Select Course</option>
                                            </select>
                                            <div class="tt-cross-note" style="display:none;">
                                                Cross-catering course detected. Generation will apply shared session logic.
                                            </div>

                                            <div class="tt-course-complete-note text-danger" style="display:none;">
                                                Lecture sessions for this course are already complete. Practical and Workshop can still be added manually.
                                            </div>
                                        </div>

                                        <div class="col-lg-3">
                                            <label class="form-label">Assigned User</label>
                                            <select name="lecturers[0]" class="form-control lecturer-select" required>
                                                <option value="">Select User</option>
                                            </select>
                                        </div>

                                        <div class="col-lg-2">
                                            <label class="form-label">Activity</label>
                                            <select name="activities[0]" class="form-control activity-select" required>
                                                <option value="">Select Activity</option>
                                                <option value="Lecture">Lecture</option>
                                                <option value="Practical">Practical</option>
                                                <option value="Workshop">Workshop</option>
                                            </select>
                                        </div>

                                        <div class="col-lg-3">
                                            <label class="form-label">Groups</label>
                                            <select name="groups[0][]" class="form-control group-selection" multiple
                                                required>
                                                <option value="All Groups">All Groups</option>
                                            </select>
                                            <div class="tt-form-note mt-1">
                                                If the selected setup is from another semester, you may be asked whether
                                                to keep, shift, or swap course structure.
                                            </div>
                                        </div>

                                        <div class="col-lg-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-danger w-100 remove-course">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn tt-btn tt-btn-soft mt-2" id="add-course">
                                <i class="bi bi-plus-circle me-1"></i> Add Course
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary tt-btn"
                        data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn tt-btn tt-btn-primary" id="generateSubmitBtn">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #generateTimetableModal .select2-container {
        width: 100% !important;
    }

    #generateTimetableModal .select2-container .selection {
        display: block;
        width: 100%;
    }

    #generateTimetableModal .select2-container--classic .select2-selection--single,
    #generateTimetableModal .select2-container--classic .select2-selection--multiple {
        width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box;
    }

    #generateTimetableModal .select2-dropdown {
        z-index: 9999 !important;
        box-sizing: border-box;
    }

    #generateTimetableModal .tt-course-selection {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    #generateTimetableModal .modal-body {
        overflow-x: hidden;
    }

    #generateTimetableModal .select2-container {
    width: 100% !important;
}

#generateTimetableModal .select2-container .selection {
    display: block;
    width: 100%;
}

#generateTimetableModal .select2-container--classic .select2-selection--single,
#generateTimetableModal .select2-container--classic .select2-selection--multiple {
    width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box;
}

#generateTimetableModal .select2-container--classic .select2-selection--multiple {
    min-height: 44px !important;
    max-height: 110px;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}

#generateTimetableModal .select2-dropdown {
    z-index: 9999 !important;
    box-sizing: border-box;
}

#generateTimetableModal .tt-course-selection {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

#generateTimetableModal .modal-body {
    overflow-y: auto;
    overflow-x: hidden;
    max-height: calc(100vh - 210px);
}

#generateTimetableModal .select2-results__options {
    overscroll-behavior: contain;
}
</style>
<div class="modal fade" id="editTimetableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Timetable Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="editTimetableForm" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_modal_id">
                    <input type="hidden" name="faculty_id" id="edit_modal_faculty_id">
                    <input type="hidden" name="setup_id" id="edit_modal_setup_id" value="{{ $timetableSemester?->id }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Day</label>
                            <input type="text" name="day" id="edit_modal_day" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Time</label>
                            <input type="text" name="time_start" id="edit_modal_time_start" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <select name="time_end" id="edit_modal_time_end" class="form-control" required>
                                <option value="">Select End Time</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Course Code <span class="text-danger">*</span></label>
                            <select name="course_code" id="edit_modal_course_code" class="form-control" required>
                                <option value="">Select Course Code</option>
                            </select>
                            <div class="tt-cross-note" id="edit_modal_cross_note">
                                Editing this cross-catering session may update all related faculties.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Assigned User <span class="text-danger">*</span></label>
                            <select name="lecturer_id" id="edit_modal_lecturer_id" class="form-control" required>
                                <option value="">Select User</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Activity <span class="text-danger">*</span></label>
                            <select name="activity" id="edit_modal_activity" class="form-control" required>
                                <option value="">Select Activity</option>
                                <option value="Lecture">Lecture</option>
                                <option value="Practical">Practical</option>
                                <option value="Workshop">Workshop</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Venue <span class="text-danger">*</span></label>
                            <select name="venue_id[]" id="edit_modal_venue_id" class="form-control" multiple required>
                            </select>
                            <small class="text-muted">Select one or more available venues.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Group Selection <span class="text-danger">*</span></label>
                            <select name="group_selection[]" id="edit_modal_group_selection" class="form-control" multiple required>
                                <option value="All Groups">All Groups</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary tt-btn" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn tt-btn tt-btn-primary">Update Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
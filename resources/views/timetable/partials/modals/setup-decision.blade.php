<div class="modal fade" id="setupDecisionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Semester Change Decision</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="setupDecisionMessage" class="mb-3"></div>

                <div class="mb-3">
                    <label class="form-label">Choose Handling Mode</label>
                    <select id="generation_mode" class="form-control">
                        <option value="">Select an option</option>
                        <option value="keep_current">Keep current course structure</option>
                        <option value="shift_previous">Shift previous timetable</option>
                        <option value="swap_courses">Swap to selected semester courses</option>
                    </select>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <h6>Current Courses</h6>
                        <div id="currentCoursesBox" class="border rounded p-2 small bg-light"></div>
                    </div>

                    <div class="col-md-6">
                        <h6>Selected Setup Courses</h6>
                        <div id="targetCoursesBox" class="border rounded p-2 small bg-light"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn tt-btn tt-btn-primary" id="confirmSetupDecisionBtn">
                    Continue
                </button>
            </div>
        </div>
    </div>
</div>
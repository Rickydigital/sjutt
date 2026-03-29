<div class="modal fade" id="showTimetableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Timetable Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light fw-bold">Main Session</div>
                            <div class="card-body">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr><th style="width: 170px;">Course Code</th><td id="show_course_code"></td></tr>
                                        <tr><th>Course Name</th><td id="show_course_name"></td></tr>
                                        <tr><th>Activity</th><td id="show_activity"></td></tr>
                                        <tr><th>Day</th><td id="show_day"></td></tr>
                                        <tr><th>Time</th><td><span id="show_time_start"></span> - <span id="show_time_end"></span></td></tr>
                                        <tr><th>Venue(s)</th><td id="show_venue"></td></tr>
                                        <tr><th>Groups</th><td id="show_groups"></td></tr>
                                        <tr><th>Group Details</th><td id="show_group_details"></td></tr>
                                        <tr><th>Assigned User</th><td id="show_lecturer"></td></tr>
                                        <tr><th>Faculty</th><td id="show_faculty"></td></tr>
                                        <tr><th>Semester</th><td id="show_timetable_semester"></td></tr>
                                        <tr><th>Cross-catering</th><td id="show_cross_mode"></td></tr>
                                        <tr><th>Related Rows</th><td id="show_cross_related_count"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm mb-3" id="cross_faculties_card" style="display:none;">
                            <div class="card-header bg-light fw-bold">Cross-Catering Faculties</div>
                            <div class="card-body">
                                <div id="show_cross_faculties"></div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm" id="collision_card" style="display:none;">
                            <div class="card-header bg-light fw-bold">Collision Analysis</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="mb-2">Assigned User Collisions</h6>
                                    <div id="show_collision_lecturer"></div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="mb-2">Faculty Collisions</h6>
                                    <div id="show_collision_faculty"></div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="mb-2">Group Collisions</h6>
                                    <div id="show_collision_group"></div>
                                </div>

                                <div>
                                    <h6 class="mb-2">Venue Collisions</h6>
                                    <div id="show_collision_venue"></div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm" id="no_extra_info_card">
                            <div class="card-header bg-light fw-bold">Extra Details</div>
                            <div class="card-body text-muted">
                                No additional cross-catering or collision details found.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary tt-btn" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
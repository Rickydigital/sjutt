<div class="modal fade" id="addTimetableSemesterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Timetable Semester Setup</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="addTimetableSemesterForm" method="POST" action="{{ route('timetable-semesters.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester_id" id="add_semester_id" class="form-control" required>
                            <option value="">Select Semester</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <input type="text" name="academic_year" id="add_academic_year" class="form-control" placeholder="e.g. 2025/2026" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="add_start_date" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="add_end_date" class="form-control" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary tt-btn" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn tt-btn tt-btn-primary">Create Setup</button>
                </div>
            </form>
        </div>
    </div>
</div>
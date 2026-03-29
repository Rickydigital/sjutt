<div class="modal fade" id="editTimetableSemesterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Selected Timetable Setup</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="editTimetableSemesterForm"
                  method="POST"
                  action="{{ $timetableSemester ? route('timetable-semesters.update', $timetableSemester->id) : '#' }}">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <input type="hidden" name="id" value="{{ $timetableSemester->id ?? '' }}">

                    <div class="mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester_id" id="edit_semester_id" class="form-control" required>
                            <option value="">Select Semester</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}"
                                    {{ $timetableSemester && (int)$timetableSemester->semester_id === (int)$semester->id ? 'selected' : '' }}>
                                    {{ $semester->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <input type="text"
                               name="academic_year"
                               id="edit_academic_year"
                               class="form-control"
                               value="{{ $timetableSemester->academic_year ?? '' }}"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date"
                               name="start_date"
                               id="edit_start_date"
                               class="form-control"
                               value="{{ $timetableSemester && $timetableSemester->start_date ? \Illuminate\Support\Carbon::parse($timetableSemester->start_date)->format('Y-m-d') : '' }}"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date"
                               name="end_date"
                               id="edit_end_date"
                               class="form-control"
                               value="{{ $timetableSemester && $timetableSemester->end_date ? \Illuminate\Support\Carbon::parse($timetableSemester->end_date)->format('Y-m-d') : '' }}"
                               required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="draft" {{ $timetableSemester && $timetableSemester->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ $timetableSemester && $timetableSemester->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="archived" {{ $timetableSemester && $timetableSemester->status === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary tt-btn" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn tt-btn tt-btn-primary">Update Setup</button>
                </div>
            </form>
        </div>
    </div>
</div>
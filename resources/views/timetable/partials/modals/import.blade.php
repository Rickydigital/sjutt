<div class="modal fade" id="importTimetableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Timetable</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="importTimetableForm" method="POST" action="{{ route('timetable.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Upload File <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Setup</label>
                        <select name="setup_id" class="form-control">
                            <option value="">Use active setup</option>
                            @foreach($timetableSemesters as $setup)
                                <option value="{{ $setup->id }}"
                                    {{ (string)($selectedSetupId ?? $timetableSemester?->id) === (string)$setup->id ? 'selected' : '' }}>
                                    {{ $setup->semester->name ?? 'Unknown Semester' }} • {{ $setup->academic_year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tt-form-note">
                        Import can target the selected setup or fall back to the currently active setup.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary tt-btn" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn tt-btn tt-btn-primary">Import File</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="tt-card">
    <div class="tt-card-header">
        <span><i class="fas fa-filter me-2"></i> Filter Timetable</span>
    </div>

    <div class="tt-card-body">
        <form method="GET" action="{{ route('timetable.index') }}" id="facultyFilterForm">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label for="faculty" class="form-label">Faculty</label>
                    <select name="faculty" id="faculty" class="form-control">
                        <option value="">Select Faculty</option>
                        @foreach($faculties as $id => $name)
                            <option value="{{ $id }}" {{ (string)$facultyId === (string)$id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4">
                    <label for="setup_id_filter" class="form-label">Setup</label>
                    <select name="setup_id" id="setup_id_filter" class="form-control">
                        <option value="">Select Setup</option>
                        @foreach($timetableSemesters as $setup)
                            <option value="{{ $setup->id }}"
                                {{ (string)($selectedSetupId ?? $timetableSemester?->id) === (string)$setup->id ? 'selected' : '' }}>
                                {{ $setup->semester->name ?? 'Unknown Semester' }} • {{ $setup->academic_year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4 d-flex align-items-end">
                    <button type="submit" class="btn tt-btn tt-btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Load Timetable
                    </button>
                </div>
            </div>
        </form>

        <div class="tt-inline-note mt-3">
            Click any empty slot to add a timetable entry. For cross-catering courses, the selected action may apply to all related faculties.
        </div>
    </div>
</div>
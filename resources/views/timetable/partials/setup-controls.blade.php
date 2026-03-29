<div class="tt-card">
    <div class="tt-card-header">
        <span><i class="fas fa-sliders-h me-2"></i> Timetable Setup Control</span>

        <div class="tt-toolbar">
            <button class="btn tt-btn tt-btn-primary" data-bs-toggle="modal" data-bs-target="#generateTimetableModal">
                <i class="fas fa-magic me-1"></i> Generate
            </button>

            <button class="btn tt-btn tt-btn-soft" data-bs-toggle="modal" data-bs-target="#addTimetableSemesterModal">
                <i class="fas fa-plus-circle me-1"></i> Add Setup
            </button>

            <button class="btn tt-btn tt-btn-soft" data-bs-toggle="modal" data-bs-target="#importTimetableModal">
                <i class="fas fa-file-import me-1"></i> Import
            </button>

            <div class="dropdown">
                <button class="btn tt-btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-file-export me-1"></i> Export
                </button>
                <ul class="dropdown-menu">
                    @foreach(['First Draft', 'Second Draft', 'Third Draft', 'Fourth Draft', 'Pre Final', 'Final Draft'] as $draft)
                        <li>
                            <a class="dropdown-item"
                               href="javascript:void(0)"
                               onclick="exportTimetable('{{ $draft }}')">
                                {{ $draft }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="tt-card-body">
        <div class="row g-3">
            <div class="col-lg-5">
                <label class="form-label">Selected Setup</label>
                <form method="GET" action="{{ route('timetable.index') }}" id="setupFilterForm">
                    <input type="hidden" name="faculty" value="{{ $facultyId }}">
                    <select name="setup_id" id="setup_id" class="form-control">
                        <option value="">Select timetable setup</option>
                        @foreach($timetableSemesters as $setup)
                            <option value="{{ $setup->id }}"
                                {{ (string)($selectedSetupId ?? $timetableSemester?->id) === (string)$setup->id ? 'selected' : '' }}>
                                {{ $setup->semester->name ?? 'Unknown Semester' }}
                                • {{ $setup->academic_year }}
                                • {{ ucfirst($setup->status ?? 'draft') }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <div class="tt-form-note mt-2">
                    Select any setup to load its timetable, even if it is not currently active.
                </div>
            </div>

            <div class="col-lg-7">
                <label class="form-label">Setup Actions</label>
                <div class="d-flex flex-wrap gap-2">
                    @if($timetableSemester)
                        <button class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editTimetableSemesterModal">
                            <i class="fas fa-pen me-1"></i> Edit Selected
                        </button>

                        @if(($timetableSemester->status ?? null) !== 'active')
                            <button class="btn btn-success"
                                    id="activateSelectedSetupBtn"
                                    data-id="{{ $timetableSemester->id }}">
                                <i class="fas fa-check-circle me-1"></i> Activate
                            </button>
                        @else
                            <button class="btn btn-success" disabled>
                                <i class="fas fa-check-circle me-1"></i> Active Setup
                            </button>
                        @endif

                        <button class="btn btn-outline-danger"
                                id="deleteSelectedSetupBtn"
                                data-id="{{ $timetableSemester->id }}">
                            <i class="fas fa-trash me-1"></i> Delete
                        </button>
                    @endif
                </div>

                <div class="tt-form-note mt-2">
                    You can edit, activate, or remove the selected setup directly from here.
                </div>
            </div>
        </div>

        <div class="tt-stat-grid mt-4">
            <div class="tt-stat">
                <div class="tt-stat-label">Selected Setup</div>
                <div class="tt-stat-value">
                    {{ $timetableSemester ? (($timetableSemester->semester->name ?? 'Unknown') . ' • ' . $timetableSemester->academic_year) : 'None' }}
                </div>
            </div>

            <div class="tt-stat">
                <div class="tt-stat-label">Status</div>
                <div class="tt-stat-value">
                    {{ $timetableSemester ? ucfirst($timetableSemester->status ?? 'draft') : 'N/A' }}
                </div>
            </div>

            <div class="tt-stat">
                <div class="tt-stat-label">Available Setups</div>
                <div class="tt-stat-value">{{ collect($timetableSemesters)->count() }}</div>
            </div>

            <div class="tt-stat">
                <div class="tt-stat-label">Displayed Entries</div>
                <div class="tt-stat-value">{{ collect($timetables ?? [])->count() }}</div>
            </div>
        </div>
    </div>
</div>
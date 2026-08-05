@if($setup)
<div class="modal fade" id="manageWeekBlocksModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Manage Week Blocks</h5>
                    <small class="text-muted">Generate consecutive weeks or manage existing blocks without crowding the Almanac table.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <ul class="nav nav-tabs" id="weekBlockTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#generateWeeksPane" type="button">
                            Generate Weeks
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="existingWeekBlocksTab" data-bs-toggle="tab" data-bs-target="#existingWeekBlocksPane" type="button">
                            Existing Week Blocks
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#manualWeekBlockPane" type="button">
                            Add One Block
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    <div class="tab-pane fade show active" id="generateWeeksPane">
                        <form
                                id="generateWeeksForm"
                                method="POST"
                                action="{{ route('almanac.week-blocks.generate', $setup) }}"
                            >
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Programme Group</label>
                                    <select name="almanac_program_group_id" class="form-select" required>
                                        <option value="">Select programme group</option>
                                        @foreach($setup->programGroups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Block Type</label>
                                    <select name="block_type" class="form-select" required>
                                        @foreach(['teaching','examination','registration','orientation','fieldwork','clinical','holiday','break','other'] as $type)
                                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Label Name</label>
                                    <input name="label_name" class="form-control" value="Week" placeholder="Week, Exam, Rotation" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Background</label>
                                    <input type="color" name="background_color" class="form-control form-control-color" value="#bfdbfe">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Text</label>
                                    <input type="color" name="text_color" class="form-control form-control-color" value="#000000">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Notes</label>
                                    <input name="notes" class="form-control" placeholder="Optional note applied to generated blocks">
                                </div>
                            </div>

                            <div class="alert alert-light border mt-3 mb-0">
                                The numbering is automatic. For example, Label Name
                                <strong>Week</strong> generates
                                <strong>Week 1, Week 2, Week 3</strong> up to the selected end date.
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-magic"></i> Generate Weeks
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="existingWeekBlocksPane">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-muted small" id="weekBlocksCount">Loading...</div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshWeekBlocksBtn">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Programme Group</th>
                                        <th>Label</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Type</th>
                                        <th style="width:140px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="weekBlocksTableBody">
                                    <tr><td colspan="6" class="text-center text-muted py-4">Loading week blocks...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="manualWeekBlockPane">
                        <form
                                id="manualWeekBlockForm"
                                method="POST"
                                action="{{ route('almanac.week-blocks.store', $setup) }}"
                            >
                            @csrf
                            @include('almanac.partials.week-block-fields')
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-success">Save Week Block</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

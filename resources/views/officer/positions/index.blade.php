@extends('officer.layouts.app')

@section('title', 'Positions - ' . $election->title)

@section('content')

<div class="page-inner">
    <div class="page-header">
        <h4 class="page-title">Election Positions</h4>
        <ul class="breadcrumbs">
            <li class="nav-home"><a href="{{ route('officer.dashboard') }}"><i class="bi bi-house-door-fill"></i></a></li>
            <li class="separator"><i class="bi bi-chevron-right"></i></li>
            <li class="nav-item"><a href="{{ route('officer.elections.index') }}">Elections</a></li>
            <li class="separator"><i class="bi bi-chevron-right"></i></li>
            <li class="nav-item"><span>{{ Str::limit($election->title, 40) }}</span></li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">{{ $election->title }}</h4>
                        <small class="text-muted">
                            {{ $election->start_date?->format('d M Y') }} — {{ $election->end_date?->format('d M Y') }}
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-{{ $election->is_active ? 'success' : 'secondary' }} px-3 py-2">
                            {{ $election->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPositionModal">
                            <i class="bi bi-plus-lg"></i> Add Position
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if ($positions->isEmpty())
                        <div class="text-center py-5 my-5">
                            <i class="bi bi-clipboard-x display-1 text-muted opacity-50"></i>
                            <h5 class="mt-4 text-muted">No positions added yet for this election</h5>
                            <p class="text-muted mb-4">Positions are based on predefined roles with fixed scope and limits.</p>
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPositionModal">
                                <i class="bi bi-plus-circle"></i> Add First Position
                            </button>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Position</th>
                                        <th>Scope</th>
                                        <th>Targets</th>
                                       
                                        <th>Enabled</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($positions as $position)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $position->definition->name }}</div>
                                                <small class="text-muted">{{ $position->definition->description ?? '—' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ match($position->scope_type) { 'general' => 'secondary', 'faculty' => 'info', 'program' => 'primary', default => 'dark' } }} text-uppercase">
                                                    {{ strtoupper($position->scope_type) }}
                                                </span>
                                            </td>
                                            <td class="text-muted">
                                                @if ($position->scope_type === 'general') All voters
                                                @elseif ($position->scope_type === 'faculty' && $position->faculties->isNotEmpty())
                                                    {{ $position->faculties->count() === 1 ? $position->faculties->first()->name : $position->faculties->count() . ' faculties' }}
                                                @elseif ($position->scope_type === 'program' && $position->programs->isNotEmpty())
                                                    {{ $position->programs->count() === 1 ? $position->programs->first()->name : $position->programs->count() . ' programs' }}
                                                @else —
                                                @endif
                                            </td>

                                            <td>
                                                <span class="badge bg-{{ $position->is_enabled ? 'success' : 'danger' }}">
                                                    {{ $position->is_enabled ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-icon btn-sm btn-warning me-1 edit-position-btn"
                                                        data-bs-toggle="modal" data-bs-target="#editPositionModal"
                                                        data-id="{{ $position->id }}"
                                                        data-definition-id="{{ $position->position_definition_id }}"
                                                        data-scope="{{ $position->scope_type }}"
                                                        data-max="{{ $position->max_candidates ?? '' }}"
                                                        data-faculties="{{ json_encode($position->faculties->pluck('id')->toArray()) }}"
                                                        data-programs="{{ json_encode($position->programs->pluck('id')->toArray()) }}"
                                                        data-enabled="{{ $position->is_enabled ? '1' : '0' }}">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Position Modal -->
<div class="modal fade" id="addPositionModal" tabindex="-1" aria-labelledby="addPositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPositionModalLabel">Add Position to Election</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('officer.elections.positions.store', $election) }}" method="POST">
                @csrf
                <input type="hidden" name="scope_type" id="scope_type_hidden_add" value="">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Position Definition *</label>
                        <select name="position_definition_id" id="position_definition_id_add" class="form-select select2" required>
                            <option value="">— Select —</option>
                            @foreach ($definitions as $def)
                                <option value="{{ $def->id }}"
                                        data-scope="{{ $def->default_scope_type }}"
                                        data-max="{{ $def->max_candidates ?? '' }}">
                                    {{ $def->name }} — {{ ucfirst($def->default_scope_type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Scope</label>
                            <div class="form-control-plaintext" id="display_scope_add">—</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Max Candidates (optional)</label>
                            <input type="number" name="max_candidates" id="max_candidates_add" class="form-control" min="1" placeholder="Blank = no limit">
                            <small class="text-muted">Default: <span id="default_max_add">—</span></small>
                        </div>
                    </div>

                    <div class="scope-target scope-faculty mb-4" style="display:none;" id="scope-faculty-add">
                        <label class="form-label fw-bold">Faculties *</label>
                        <select name="faculty_ids[]" id="faculty_ids_add" class="form-select select2" multiple>
                            <option value="all">— Select All —</option>
                            @foreach ($faculties as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="scope-target scope-program mb-4" style="display:none;" id="scope-program-add">
                        <label class="form-label fw-bold">Programs *</label>
                        <select name="program_ids[]" id="program_ids_add" class="form-select select2" multiple>
                            <option value="all">— Select All —</option>
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Position Modal - now full editing like add -->
<div class="modal fade" id="editPositionModal" tabindex="-1" aria-labelledby="editPositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPositionModalLabel">Edit Position</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPositionForm" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="scope_type" id="scope_type_hidden_edit" value="">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Position Definition *</label>
                        <select name="position_definition_id" id="position_definition_id_edit" class="form-select select2" required>
                            <option value="">— Select —</option>
                            @foreach ($definitions as $def)
                                <option value="{{ $def->id }}"
                                        data-scope="{{ $def->default_scope_type }}"
                                        data-max="{{ $def->max_candidates ?? '' }}">
                                    {{ $def->name }} — {{ ucfirst($def->default_scope_type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Scope</label>
                            <div class="form-control-plaintext" id="display_scope_edit">—</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Max Candidates (optional)</label>
                            <input type="number" name="max_candidates" id="max_candidates_edit" class="form-control" min="1" placeholder="Blank = no limit">
                            <small class="text-muted">Default: <span id="default_max_edit">—</span></small>
                        </div>
                    </div>

                    <div class="scope-target scope-faculty mb-4" style="display:none;" id="scope-faculty-edit">
                        <label class="form-label fw-bold">Faculties *</label>
                        <select name="faculty_ids[]" id="faculty_ids_edit" class="form-select select2" multiple>
                            <option value="all">— Select All —</option>
                            @foreach ($faculties as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="scope-target scope-program mb-4" style="display:none;" id="scope-program-edit">
                        <label class="form-label fw-bold">Programs *</label>
                        <select name="program_ids[]" id="program_ids_edit" class="form-select select2" multiple>
                            <option value="all">— Select All —</option>
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" id="is_enabled_edit" name="is_enabled" value="1">
                            <label class="form-check-label" for="is_enabled_edit">Enabled</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {

    // Shared Select2 init
   function initSelect2(container) {
    $(container).find('select.select2').each(function () {
        const $el = $(this);

        // destroy if already initialized
        if ($el.hasClass("select2-hidden-accessible")) {
            $el.select2('destroy');
        }

        const id = $el.attr('id') || '';
        let ph = "Select...";
        if (id.includes('faculty')) ph = "Faculties (or all)";
        if (id.includes('program')) ph = "Programs (or all)";

        const $modal = $el.closest('.modal');

        $el.select2({
            width: '100%',
            placeholder: ph,
            allowClear: true,
            dropdownParent: $modal.length ? $modal : $(document.body),
        });
    });
}

    // Add modal
    $('#addPositionModal').on('shown.bs.modal', function() {
        initSelect2(this);
        $('#position_definition_id_add').trigger('change');
    });

    // Edit modal - populate fields when button clicked
    $(document).on('click', '.edit-position-btn', function() {
        const btn = $(this);

        // Set form action
        $('#editPositionForm').attr('action', '{{ route("officer.elections.positions.update", [$election, ":id"]) }}'
            .replace(':id', btn.data('id')));

        // Populate fields
        $('#position_definition_id_edit').val(btn.data('definition-id')).trigger('change');
        $('#max_candidates_edit').val(btn.data('max') || '');
        $('#is_enabled_edit').prop('checked', btn.data('enabled') == '1');

        // Pre-select current targets
        const faculties = btn.data('faculties') || [];
        const programs  = btn.data('programs')  || [];

        setTimeout(() => {
            $('#faculty_ids_edit').val(faculties).trigger('change');
            $('#program_ids_edit').val(programs).trigger('change');
        }, 300); // wait for Select2 init

        $('#editPositionModal').modal('show');
    });

    $('#editPositionModal').on('shown.bs.modal', function() {
        initSelect2(this);
        $('#position_definition_id_edit').trigger('change');
    });

    // Shared definition change handler
    function handleDefinitionChange($select, prefix) {
        const selected = $select.find(':selected');
        const scope = (selected.data('scope') || '').toLowerCase();
        const max   = selected.data('max') || '';

        $(`#display_scope_${prefix}`).text(scope ? scope.charAt(0).toUpperCase() + scope.slice(1) : '—');

        const maxText = max ? `${max} candidate${Number(max) > 1 ? 's' : ''}` : 'No limit';
        $(`#default_max_${prefix}`).text(maxText);

        $(`#max_candidates_${prefix}`).val(max);
        $(`#scope_type_hidden_${prefix}`).val(scope);

        // IMPORTANT: limit hide/show to the modal of this select
        const $modal = $select.closest('.modal');
        $modal.find('.scope-target').hide();

        if (scope === 'faculty') $modal.find(`#scope-faculty-${prefix}`).fadeIn(200);
        if (scope === 'program') $modal.find(`#scope-program-${prefix}`).fadeIn(200);
    }

    $('#position_definition_id_add').on('change', function () {
    handleDefinitionChange($(this), 'add');
    });

    $('#position_definition_id_edit').on('change', function () {
        handleDefinitionChange($(this), 'edit');
    });


    // Select All behavior
    function setupSelectAll($select) {
    let syncing = false;

    $select.off('select2:select.selectAll change.selectAll');

    $select.on('select2:select.selectAll', function (e) {
        if (e.params.data.id === 'all') {
            syncing = true;
            $(this).find('option:not([value="all"])').prop('selected', true);
            $(this).trigger('change');
            syncing = false;
        }
    });

    $select.on('change.selectAll', function () {
        if (syncing) return;

        const $real = $(this).find('option:not([value="all"])');
        const allSelected = $real.length && $real.filter(':selected').length === $real.length;

        syncing = true;
        $(this).find('option[value="all"]').prop('selected', allSelected);
        $(this).trigger('change.select2');
        syncing = false;
    });
}

    $('#addPositionModal, #editPositionModal').on('shown.bs.modal', function() {
        setupSelectAll($('#faculty_ids_add'));
        setupSelectAll($('#program_ids_add'));
        setupSelectAll($('#faculty_ids_edit'));
        setupSelectAll($('#program_ids_edit'));
    });

    // Delete confirmation
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        Swal.fire({
            title: 'Delete position?',
            text: "This cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete'
        }).then(r => { if (r.isConfirmed) form.submit(); });
    });

    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endsection

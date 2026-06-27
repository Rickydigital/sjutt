{{-- resources/views/officer/elections/candidates.blade.php --}}
@extends('officer.layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex flex-row justify-content-between align-items-center">
            <div>
                <strong class="card-title">Election Candidates</strong>
                <small class="text-muted d-block">
                    {{ $election->title }} —
                    {{ optional($election->start_date)->format('Y-m-d') }} to {{
                    optional($election->end_date)->format('Y-m-d') }}
                    ({{ strtoupper($election->status) }})
                </small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('officer.elections.show', $election) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>

                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#addCandidateModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Candidate
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">

        {{-- Validation errors --}}
        @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Fix the following:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($positions->isEmpty())
        <p class="text-center mb-0">No positions found for this election. Add positions first.</p>
        @else

        {{-- Positions + candidates --}}
        <div class="accordion" id="positionsAccordion">
            @foreach ($positions as $pos)
            @php
            $scope = $pos->scope_type ?? 'general';
            $scopeLabel = strtoupper($scope);

            // Candidates already grouped by election_position_id in controller
            $posCandidates = $candidates[$pos->id] ?? collect();

            // Determine "scope targets" display
            $targetsText = '—';
            if ($scope === 'faculty') {
            $targetsText = $pos->faculties->pluck('name')->implode(', ') ?: '—';
            } elseif ($scope === 'program') {
            $targetsText = $pos->programs->pluck('name')->implode(', ') ?: '—';
            }

            $max = $pos->max_candidates ?? null;
            @endphp

            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_{{ $pos->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse_{{ $pos->id }}" aria-expanded="false"
                        aria-controls="collapse_{{ $pos->id }}">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-semibold">{{ $pos->definition?->name ?? 'Position' }}</span>
                                <span class="badge bg-secondary ms-2">{{ $scopeLabel }}</span>
                                @if($max)
                                <span class="badge bg-info ms-1">MAX: {{ $max }}</span>
                                @endif
                                <div class="text-muted small">
                                    Scope Targets: {{ $targetsText }}
                                </div>
                            </div>
                            <div class="text-muted">
                                Candidates: <span class="fw-semibold">{{ $posCandidates->count() }}</span>
                            </div>
                        </div>
                    </button>
                </h2>

                <div id="collapse_{{ $pos->id }}" class="accordion-collapse collapse"
                    aria-labelledby="heading_{{ $pos->id }}" data-bs-parent="#positionsAccordion">
                    <div class="accordion-body">

                        @if ($posCandidates->isEmpty())
                        <p class="text-center mb-0 text-muted">No candidates added for this position yet.</p>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped table-sm align-middle">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th style="width: 35%">Student</th>
                                        <th style="width: 15%">Reg No</th>
                                        <th style="width: 20%">Faculty</th>
                                        <th style="width: 20%">Program</th>
                                        <th style="width: 10%" class="text-end">Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($posCandidates as $cand)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column gap-2">

                                                {{-- MAIN CANDIDATE --}}
                                                <div class="d-flex align-items-start gap-3">
                                                    @php
                                                    $name = trim(($cand->student?->first_name ?? '') . ' ' .
                                                    ($cand->student?->last_name ?? ''));
                                                    $initials = collect(explode(' ', $name))
                                                    ->filter()
                                                    ->map(fn($n) => strtoupper(substr($n, 0, 1)))
                                                    ->take(2)
                                                    ->implode('');

                                                    $vice = $cand->vice ?? null;
                                                    $vStudent = $vice?->student;
                                                    $vName = trim(($vStudent?->first_name ?? '') . ' ' .
                                                    ($vStudent?->last_name ?? ''));
                                                    $vInitials = collect(explode(' ', $vName))
                                                    ->filter()
                                                    ->map(fn($n) => strtoupper(substr($n, 0, 1)))
                                                    ->take(2)
                                                    ->implode('');
                                                    @endphp

                                                    @if ($cand->photo)
                                                    <img src="{{ asset('storage/' . $cand->photo) }}"
                                                        alt="Candidate photo" class="rounded-circle" width="48"
                                                        height="48" style="object-fit: cover;">
                                                    @else
                                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold"
                                                        style="width:48px;height:48px;">
                                                        {{ $initials ?: 'NA' }}
                                                    </div>
                                                    @endif

                                                    <div class="min-w-0">
                                                        <div class="fw-semibold text-truncate">
                                                            {{ $name ?: 'Unknown Student' }}
                                                        </div>

                                                        @if ($cand->description)
                                                        <div class="text-muted small">
                                                            {{ Str::limit($cand->description, 80) }}
                                                        </div>
                                                        @endif

                                                        <div class="text-muted small">
                                                            Candidate ID: {{ $cand->id }}
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- VICE CANDIDATE (ONLY IF EXISTS) --}}
                                                @if($vice && $vStudent)
                                                <div class="d-flex align-items-start gap-3 ms-4 ps-2 border-start">
                                                    @if ($vice->photo)
                                                    <img src="{{ asset('storage/' . $vice->photo) }}" alt="Vice photo"
                                                        class="rounded-circle" width="40" height="40"
                                                        style="object-fit: cover;">
                                                    @else
                                                    <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center fw-bold"
                                                        style="width:40px;height:40px;">
                                                        {{ $vInitials ?: 'V' }}
                                                    </div>
                                                    @endif

                                                    <div class="min-w-0">
                                                        <div class="fw-semibold small text-truncate">
                                                            Vice: {{ $vName ?: 'Unknown Vice' }}
                                                        </div>

                                                        <div class="text-muted small">
                                                            {{ $vStudent?->reg_no ?? '—' }}
                                                        </div>

                                                        @if($vice->description)
                                                        <div class="text-muted small">
                                                            {{ Str::limit($vice->description, 80) }}
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                @endif

                                            </div>
                                        </td>



                                        <td>{{ $cand->student?->reg_no ?? '—' }}</td>

                                        <td>{{ $cand->student?->faculty?->name ?? '—' }}</td>

                                        <td>{{ $cand->student?->program?->name ?? '—' }}</td>

                                        <td class="text-end">
                                            <form
                                                action="{{ route('officer.elections.candidates.destroy', [$election, $cand]) }}"
                                                method="POST" class="d-inline delete-candidate-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="action-icon text-danger border-0 bg-transparent"
                                                    title="Remove Candidate">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Quick add candidate for this position (optional inline) --}}
                        <hr class="my-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Add candidate directly to: <span class="fw-semibold">{{ $pos->definition?->name ??
                                    'Position' }}</span>
                            </div>

                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#addCandidateModal" data-position="{{ $pos->id }}">
                                <i class="bi bi-plus-circle me-1"></i> Add Candidate Here
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @endif
    </div>
</div>

{{-- =========================
ADD CANDIDATE MODAL
========================== --}}
<div class="modal fade" id="addCandidateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Candidate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" enctype="multipart/form-data"
                action="{{ route('officer.elections.candidates.store', $election) }}">

                @csrf

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <select name="election_position_id" class="form-select select2-position"
                                data-modal="#addCandidateModal" required>
                                <option value="">-- choose position --</option>
                                @foreach ($positions as $pos)
                                @php
                                $scope = strtoupper($pos->scope_type ?? 'general');
                                $defName = $pos->definition?->name ?? 'Position';
                                @endphp
                                <option value="{{ $pos->id }}">
                                    {{ $defName }} — {{ $scope }}
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">
                                Candidate eligibility is checked automatically based on scope.
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Student</label>
                            <select name="student_id" class="form-select select2-student"
                                data-modal="#addCandidateModal" required>
                                <option value="">-- choose student --</option>
                                @foreach ($students as $st)
                                <option value="{{ $st->id }}" data-faculty="{{ $st->faculty?->name ?? '' }}"
                                    data-program="{{ $st->program?->name ?? '' }}">
                                    {{ $st->first_name }} {{ $st->last_name }} ({{ $st->reg_no }})
                                </option>
                                @endforeach
                            </select>

                            <div class="mt-2 p-2 border rounded bg-light">
                                <div class="small text-muted">Auto-detected from student profile:</div>
                                <div class="small">
                                    Faculty: <span id="previewFaculty" class="fw-semibold">—</span><br>
                                    Program: <span id="previewProgram" class="fw-semibold">—</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Candidate Photo</label>

                            <input type="file" id="photoFileInput" name="photo" class="form-control" accept="image/*">

                            <div id="photoCropperWrap" class="d-none mt-2">
                                <div style="max-height:280px;overflow:hidden;background:#111;border-radius:8px;">
                                    <img id="photoCropperImg" style="display:block;max-width:100%;">
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-success btn-sm" id="photoCropBtn">
                                        <i class="bi bi-check-circle me-1"></i> Crop & Use
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="photoChangeBtn">
                                        <i class="bi bi-arrow-repeat me-1"></i> Change Image
                                    </button>
                                </div>
                            </div>

                            <div id="photoCroppedWrap" class="d-none mt-2 align-items-center gap-3" style="display:none!important">
                                <img id="photoCroppedPreview" class="rounded-circle border" style="width:72px;height:72px;object-fit:cover;">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="photoRecropBtn">
                                    <i class="bi bi-crop me-1"></i> Re-crop
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Candidate Description / Manifesto</label>
                            <textarea name="description" rows="4" class="form-control"
                                placeholder="Why should students vote for this candidate?"></textarea>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold">Vice Candidate (Optional)</div>
                            <small class="text-muted">If not selected, candidate will be saved without vice.</small>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Vice Student (Optional)</label>
                                <select name="vice_student_id" class="form-select select2-vice-student"
                                    data-modal="#addCandidateModal">
                                    <option value="">-- choose vice student (optional) --</option>
                                    @foreach ($students as $st)
                                    <option value="{{ $st->id }}" data-faculty="{{ $st->faculty?->name ?? '' }}"
                                        data-program="{{ $st->program?->name ?? '' }}">
                                        {{ $st->first_name }} {{ $st->last_name }} ({{ $st->reg_no }})
                                    </option>
                                    @endforeach
                                </select>

                                <div class="mt-2 p-2 border rounded bg-light">
                                    <div class="small text-muted">Vice profile preview:</div>
                                    <div class="small">
                                        Faculty: <span id="previewViceFaculty" class="fw-semibold">—</span><br>
                                        Program: <span id="previewViceProgram" class="fw-semibold">—</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vice Photo (Optional)</label>

                                <input type="file" id="vicePhotoFileInput" name="vice_photo" class="form-control" accept="image/*">

                                <div id="viceCropperWrap" class="d-none mt-2">
                                    <div style="max-height:240px;overflow:hidden;background:#111;border-radius:8px;">
                                        <img id="viceCropperImg" style="display:block;max-width:100%;">
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-success btn-sm" id="viceCropBtn">
                                            <i class="bi bi-check-circle me-1"></i> Crop & Use
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="viceChangeBtn">
                                            <i class="bi bi-arrow-repeat me-1"></i> Change Image
                                        </button>
                                    </div>
                                </div>

                                <div id="viceCroppedWrap" class="d-none mt-2 align-items-center gap-3" style="display:none!important">
                                    <img id="viceCroppedPreview" class="rounded-circle border" style="width:64px;height:64px;object-fit:cover;">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="viceRecropBtn">
                                        <i class="bi bi-crop me-1"></i> Re-crop
                                    </button>
                                </div>

                                <small class="text-muted d-block mt-1">
                                    If you don’t upload, the vice can still be saved without a photo.
                                </small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Vice Description (Optional)</label>
                                <textarea name="vice_description" rows="3" class="form-control"
                                    placeholder="Vice manifesto / short bio (optional)"></textarea>
                            </div>
                        </div>



                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        You don’t select Faculty/Program here. It is taken from the student automatically.
                        The system will block invalid scope and max-candidates limits.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add Candidate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

{{-- Cropper.js --}}
<link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.delete-candidate-form').forEach(form => {
        form.addEventListener('submit', function(e){
            e.preventDefault();
            Swal.fire({
                title: 'Remove this candidate?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });

    function initSelect2($el, $modal, placeholder) {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            minimumResultsForSearch: 0
        });
    }

    $('#addCandidateModal').on('shown.bs.modal', function (event) {
        const $modal = $(this);

        const $pos  = $modal.find('.select2-position');
        const $stu  = $modal.find('.select2-student');
        const $vice = $modal.find('.select2-vice-student');

        initSelect2($pos,  $modal, 'Choose position');
        initSelect2($stu,  $modal, 'Choose student');
        initSelect2($vice, $modal, 'Choose vice (optional)');

        // If clicked "Add Candidate Here" button
        const button = event.relatedTarget;
        if (button && button.getAttribute('data-position')) {
            const posId = button.getAttribute('data-position');
            $pos.val(posId).trigger('change');
        }

        // Main student preview
        function updateStudentPreview() {
            const selected = $stu.find('option:selected');
            document.getElementById('previewFaculty').textContent = selected.data('faculty') || '—';
            document.getElementById('previewProgram').textContent = selected.data('program') || '—';
        }
        $stu.off('change.preview').on('change.preview', updateStudentPreview);
        updateStudentPreview();

        // Vice student preview
        function updateVicePreview() {
            const selected = $vice.find('option:selected');
            document.getElementById('previewViceFaculty').textContent = selected.data('faculty') || '—';
            document.getElementById('previewViceProgram').textContent = selected.data('program') || '—';
        }
        $vice.off('change.vpreview').on('change.vpreview', updateVicePreview);
        updateVicePreview();
    });

    // Optional: clear modal inputs when hidden (prevents old data staying)
    $('#addCandidateModal').on('hidden.bs.modal', function () {
        const $m = $(this);
        $m.find('form')[0].reset();
        $m.find('.select2-position, .select2-student, .select2-vice-student').val(null).trigger('change');
        document.getElementById('previewFaculty').textContent = '—';
        document.getElementById('previewProgram').textContent = '—';
        document.getElementById('previewViceFaculty').textContent = '—';
        document.getElementById('previewViceProgram').textContent = '—';
        resetCropper(photoCropper, 'photoFileInput', 'photoCropperWrap', 'photoCroppedWrap');
        resetCropper(viceCropper,  'vicePhotoFileInput', 'viceCropperWrap', 'viceCroppedWrap');
        photoCropper = null;
        viceCropper  = null;
    });

    // ── Cropper setup ────────────────────────────────────────────────
    let photoCropper = null;
    let viceCropper  = null;

    function setupCropper(opts) {
        const { fileInputId, cropperWrapId, cropperImgId, cropBtnId, changeBtnId,
                croppedWrapId, croppedPreviewId, recropBtnId } = opts;

        let cropperInstance = null;
        const fileInput      = document.getElementById(fileInputId);
        const cropperWrap    = document.getElementById(cropperWrapId);
        const cropperImg     = document.getElementById(cropperImgId);
        const croppedWrap    = document.getElementById(croppedWrapId);
        const croppedPreview = document.getElementById(croppedPreviewId);

        // When user picks a file
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                // Tear down previous instance
                if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }

                cropperImg.src = e.target.result;
                fileInput.classList.add('d-none');
                croppedWrap.classList.add('d-none');
                croppedWrap.style.removeProperty('display');
                cropperWrap.classList.remove('d-none');

                cropperInstance = new Cropper(cropperImg, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 0.85,
                    movable: true,
                    zoomable: true,
                    scalable: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                });

                // Return the instance so the caller can hold a reference
                if (fileInputId === 'photoFileInput') photoCropper = cropperInstance;
                else viceCropper = cropperInstance;
            };
            reader.readAsDataURL(file);
        });

        // Crop & Use
        document.getElementById(cropBtnId).addEventListener('click', function () {
            if (!cropperInstance) return;
            cropperInstance.getCroppedCanvas({ width: 600, height: 600 }).toBlob(blob => {
                const croppedFile = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(croppedFile);
                fileInput.files = dt.files;

                // Show circular preview
                croppedPreview.src = URL.createObjectURL(blob);
                cropperWrap.classList.add('d-none');
                croppedWrap.classList.remove('d-none');
                croppedWrap.style.display = 'flex';
            }, 'image/jpeg', 0.92);
        });

        // Change image — show file input again
        document.getElementById(changeBtnId).addEventListener('click', function () {
            if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
            cropperWrap.classList.add('d-none');
            fileInput.value = '';
            fileInput.classList.remove('d-none');
        });

        // Re-crop from the already-picked file
        document.getElementById(recropBtnId).addEventListener('click', function () {
            const file = fileInput.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
                cropperImg.src = e.target.result;
                croppedWrap.classList.add('d-none');
                croppedWrap.style.removeProperty('display');
                cropperWrap.classList.remove('d-none');
                cropperInstance = new Cropper(cropperImg, {
                    aspectRatio: 1, viewMode: 1, autoCropArea: 0.85,
                    movable: true, zoomable: true, scalable: false,
                });
                if (fileInputId === 'photoFileInput') photoCropper = cropperInstance;
                else viceCropper = cropperInstance;
            };
            reader.readAsDataURL(file);
        });
    }

    function resetCropper(instance, fileInputId, cropperWrapId, croppedWrapId) {
        if (instance) instance.destroy();
        const fi = document.getElementById(fileInputId);
        if (fi) { fi.value = ''; fi.classList.remove('d-none'); }
        const cw = document.getElementById(cropperWrapId);
        if (cw) cw.classList.add('d-none');
        const dw = document.getElementById(croppedWrapId);
        if (dw) { dw.classList.add('d-none'); dw.style.removeProperty('display'); }
    }

    setupCropper({
        fileInputId:      'photoFileInput',
        cropperWrapId:    'photoCropperWrap',
        cropperImgId:     'photoCropperImg',
        cropBtnId:        'photoCropBtn',
        changeBtnId:      'photoChangeBtn',
        croppedWrapId:    'photoCroppedWrap',
        croppedPreviewId: 'photoCroppedPreview',
        recropBtnId:      'photoRecropBtn',
    });

    setupCropper({
        fileInputId:      'vicePhotoFileInput',
        cropperWrapId:    'viceCropperWrap',
        cropperImgId:     'viceCropperImg',
        cropBtnId:        'viceCropBtn',
        changeBtnId:      'viceChangeBtn',
        croppedWrapId:    'viceCroppedWrap',
        croppedPreviewId: 'viceCroppedPreview',
        recropBtnId:      'viceRecropBtn',
    });

});
</script>

@endsection
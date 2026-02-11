@extends('components.app-main-layout')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1 fw-bold">Voters Summary</h4>
            <div class="text-muted">
                Election: <span class="fw-semibold">{{ $election->title }}</span>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>

            <button type="button" class="btn btn-danger btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#downloadVotersPdfModal">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF
            </button>

            {{-- If you have officer results show page, use this instead:
            <a href="{{ route('officer.results.show', $election) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-bar-chart-fill me-1"></i> Results
            </a>
            --}}
        </div>
    </div>

    {{-- Small stat cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Voters (distinct students)</div>
                    <div class="fs-4 fw-bold">{{ number_format($totalVoters ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Votes Cast (all rows)</div>
                    <div class="fs-4 fw-bold">{{ number_format($totalVotes ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Average Votes per Voter</div>
                    <div class="fs-4 fw-bold">
                        {{ ($totalVoters ?? 0) > 0 ? number_format(($totalVotes ?? 0) / $totalVoters, 2) : '0.00' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-header">
            <strong>Filter</strong>
            <small class="text-muted d-block">Search by name/reg no and filter by faculty/program.</small>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}"
                           placeholder="Reg no or student name">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Faculty</label>
                    <select name="faculty_id" class="form-select" id="filterFaculty">
                        <option value="">All Faculties</option>
                        @foreach(($faculties ?? collect()) as $f)
                            <option value="{{ $f->id }}" @selected((int)request('faculty_id') === (int)$f->id)>
                                {{ $f->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Program</label>
                    <select name="program_id" class="form-select" id="filterProgram">
                        <option value="">All Programs</option>
                        @foreach(($programs ?? collect()) as $p)
                            <option value="{{ $p->id }}" @selected((int)request('program_id') === (int)$p->id)>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Apply
                    </button>
                    <a href="{{ route('officer.results.voters', $election) }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Students who voted</strong>
            <small class="text-muted">
                Showing {{ method_exists($rows, 'total') ? number_format($rows->total()) : 0 }} records
            </small>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-sm align-middle mb-0">
                <thead class="bg-primary text-white">
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 20%">Student</th>
                        <th style="width: 15%">Reg No</th>
                        <th style="width: 20%">Faculty</th>
                        <th style="width: 20%">Program</th>
                        <th style="width: 8%" class="text-end">Total Votes</th>
                        <th style="width: 12%" class="text-end">Categories</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $i => $r)
                        @php
                            $index = method_exists($rows,'firstItem') ? ($rows->firstItem() + $i) : ($i + 1);
                        @endphp

                        <tr>
                            <td>{{ $index }}</td>

                            <td class="fw-semibold">
                                {{ $r->student_name }}
                                <div class="text-muted small">ID: {{ $r->student_id }}</div>
                            </td>

                            <td>{{ $r->reg_no ?? '—' }}</td>

                            <td>{{ $r->faculty_name ?? '—' }}</td>

                            <td>{{ $r->program_name ?? '—' }}</td>

                            <td class="text-end fw-bold">{{ (int)$r->total_votes }}</td>

                            <td class="text-end">
                                {{-- quick badges --}}
                                <span class="badge bg-success">G: {{ (int)$r->global_votes }}</span>
                                <span class="badge bg-warning text-dark">F: {{ (int)$r->faculty_votes }}</span>
                                <span class="badge bg-info text-dark">P: {{ (int)$r->program_votes }}</span>
                                <div class="text-muted small mt-1">
                                    Distinct: {{ (int)$r->categories_participated }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No voters found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-body">
            @if(method_exists($rows,'links'))
                {{ $rows->links('vendor.pagination.bootstrap-5') }}
            @endif
        </div>
    </div>

    {{-- DOWNLOAD PDF MODAL --}}
<div class="modal fade" id="downloadVotersPdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="GET" action="{{ route('officer.results.voters.pdf', $election) }}" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download Voters PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-light border small mb-3">
                    Tip: Choose what to filter. If you choose “Faculty”, select a faculty. If “Program”, select a program.
                </div>

                <div class="mb-3">
                    <label class="form-label">Export Type</label>
                    <select name="export_scope" class="form-select" id="exportScope" required>
                        <option value="all">All voters</option>
                        <option value="faculty">By Faculty</option>
                        <option value="program">By Program</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Faculty (optional)</label>
                    <select name="faculty_id" class="form-select modal-select2" id="exportFaculty">
                        <option value="">All Faculties</option>
                        @foreach(($faculties ?? collect()) as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Program (optional)</label>
                    <select name="program_id" class="form-select modal-select2" id="exportProgram">
                        <option value="">All Programs</option>
                        @foreach(($programs ?? collect()) as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-0">
                    <label class="form-label">Search (optional)</label>
                    <input type="text" class="form-control" name="q" placeholder="Reg no or student name">
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger">
                    <i class="bi bi-download me-1"></i> Download PDF
                </button>
            </div>
        </form>
    </div>
</div>


</div>
@endsection

@section('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // page filter select2
            $('.filter-select2').select2({
                width: '100%',
                placeholder: 'Select...',
                allowClear: true
            });

            // modal select2 (important: dropdownParent)
            $('#downloadVotersPdfModal').on('shown.bs.modal', function () {
                const $modal = $(this);

                $modal.find('.modal-select2').each(function () {
                    const $select = $(this);

                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }

                    $select.select2({
                        dropdownParent: $modal,
                        width: '100%',
                        placeholder: 'Select...',
                        allowClear: true
                    });
                });
            });

            // export scope behavior (enable/disable)
            function syncExportScope(){
                const scope = $('#exportScope').val();
                const $fac = $('#exportFaculty');
                const $prog = $('#exportProgram');

                if(scope === 'faculty'){
                    $fac.prop('disabled', false);
                    $prog.prop('disabled', true).val('').trigger('change');
                } else if(scope === 'program'){
                    $prog.prop('disabled', false);
                    $fac.prop('disabled', true).val('').trigger('change');
                } else {
                    $fac.prop('disabled', false);
                    $prog.prop('disabled', false);
                }
            }

            $('#exportScope').on('change', syncExportScope);
            syncExportScope();
        });
    </script>
@endsection


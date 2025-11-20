@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0" x-data="studentManager()" x-init="init()">
    <!-- Header -->
    <div class="card-header d-flex justify-content-between align-items-center"
        style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <h5 class="mb-0 text-white">Students Management</h5>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-dark fs-6">{{ $students->total() }} Total</span>

            @if(auth()->user()->hasRole('Admin|Administrator|Dean Of Students'))
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal" title="Add New Student">
                Add
            </button>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#importStudentsModal" title="Import Excel">
                Import
            </button>
            <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#exportModal" title="Export to Excel">
                Excel
            </button>
            <button type="button" class="btn btn-danger btn-sm" 
                onclick="window.open('{{ route('students.export.attendance.pdf') }}', '_blank')">
            Attendance PDF
        </button>
            @endif
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mx-4 mt-3">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3">
        {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white p-3 border-bottom">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <input type="text" x-model="search" @keyup.debounce.500ms="applyFilters()" 
                       class="form-control form-control-sm" placeholder="Search name, reg no, email, phone..." 
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select x-model="faculty_id" @change="applyFilters()" class="form-select form-select-sm">
                    <option value="">All Faculties</option>
                    @foreach($faculties as $f)
                    <option value="{{ $f->id }}" {{ request('faculty_id') == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select x-model="program_id" @change="applyFilters()" class="form-select form-select-sm">
                    <option value="">All Programs</option>
                    @foreach($programs as $p)
                    <option value="{{ $p->id }}" {{ request('program_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select x-model="status" @change="applyFilters()" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="Active" {{ request('status')=='Active'?'selected':'' }}>Active</option>
                    <option value="Inactive" {{ request('status')=='Inactive'?'selected':'' }}>Not Activated</option>
                    <option value="Alumni" {{ request('status')=='Alumni'?'selected':'' }}>Alumni</option>
                </select>
            </div>
            <div class="col-md-3">
                <button @click="applyFilters()" class="btn btn-primary btn-sm me-2">Apply</button>
                <button @click="resetFilters()" class="btn btn-outline-secondary btn-sm">Reset</button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card-body p-0">
        @if($students->isEmpty())
        <div class="p-5 text-center text-muted">
            <p class="mt-3">No students found.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Reg No</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Faculty</th>
                        <th>Program</th>
                        <th>Status</th>
                        <th>Gender</th>
                        @if(auth()->user()->hasRole('Admin|Administrator|Dean Of Students'))
                        <th class="text-center">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                    <tr>
                        <td>{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                        <td><strong>{{ $student->first_name }} {{ $student->last_name }}</strong></td>
                        <td><code>{{ $student->reg_no }}</code></td>
                        <td>
                            @if($student->phone)
                                <a href="tel:{{ $student->phone }}" class="text-decoration-none">{{ $student->phone }}</a>
                            @else
                                <em class="text-muted">—</em>
                            @endif
                        </td>
                        <td class="text-truncate" style="max-width: 180px;">{{ $student->email }}</td>
                        <td><span class="badge bg-info text-white">{{ $student->faculty?->name ?? '—' }}</span></td>
                        <td><span class="badge bg-secondary text-white">{{ $student->program?->name ?? '—' }}</span></td>
                        <td>
                            @php
                                $statusInfo = match($student->status) {
                                    'Active'   => ['label' => 'Active',        'class' => 'bg-success'],
                                    'Inactive' => ['label' => 'Not Activated', 'class' => 'bg-danger'],
                                    'Alumni'   => ['label' => 'Alumni',        'class' => 'bg-secondary'],
                                    default    => ['label' => 'Unknown',       'class' => 'bg-warning']
                                };
                            @endphp
                            <span class="badge {{ $statusInfo['class'] }} text-white">
                                {{ $statusInfo['label'] }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $student->gender === 'male' ? 'bg-primary' : 'bg-pink' }}">
                                {{ ucfirst($student->gender ?? '?') }}
                            </span>
                        </td>

                        @if(auth()->user()->hasRole('Admin|Administrator|Dean Of Students'))
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editStudentModal{{ $student->id }}" title="Edit Student">
                                Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#resetPasswordModal{{ $student->id }}" title="Reset Password">
                                Reset
                            </button>
                        </td>
                        @endif
                    </tr>

                    <!-- EDIT MODAL WITH PHONE -->
                    <div class="modal fade" id="editStudentModal{{ $student->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <form action="{{ route('students.update', $student) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">Edit Student</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label>Reg No *</label>
                                                <input type="text" name="reg_no" class="form-control" value="{{ $student->reg_no }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Phone (optional)</label>
                                                <input type="text" name="phone" class="form-control" value="{{ $student->phone }}" placeholder="+255 xxx xxx xxx">
                                            </div>
                                            <div class="col-md-6">
                                                <label>First Name</label>
                                                <input type="text" name="first_name" class="form-control" value="{{ $student->first_name }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Last Name</label>
                                                <input type="text" name="last_name" class="form-control" value="{{ $student->last_name }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Email</label>
                                                <input type="email" name="email" class="form-control" value="{{ $student->email }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Gender *</label>
                                                <select name="gender" class="form-select" required>
                                                    <option value="male" {{ $student->gender === 'male' ? 'selected' : '' }}>Male</option>
                                                    <option value="female" {{ $student->gender === 'female' ? 'selected' : '' }}>Female</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Program *</label>
                                                <select name="program_id" class="form-select" required @change="loadFaculties($event.target.value, {{ $student->faculty_id }})">
                                                    <option value="">Select Program</option>
                                                    @foreach($programs as $p)
                                                    <option value="{{ $p->id }}" {{ $student->program_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Faculty *</label>
                                                <select name="faculty_id" class="form-select" required>
                                                    <option value="">First select Program</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Status *</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="Active" {{ $student->status === 'Active' ? 'selected' : '' }}>Active</option>
                                                    <option value="Inactive" {{ $student->status === 'Inactive' ? 'selected' : '' }}>Not Activated</option>
                                                    <option value="Alumni" {{ $student->status === 'Alumni' ? 'selected' : '' }}>Alumni</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning mt-3">
                                            Password will be reset to: <strong>sjut123456</strong>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Student</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- RESET PASSWORD MODAL -->
                    <div class="modal fade" id="resetPasswordModal{{ $student->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('students.reset-password', $student) }}" method="POST">
                                @csrf @method('PATCH')
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5>Reset Password</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <p><strong>{{ $student->first_name }} {{ $student->last_name }}</strong><br>
                                           <code>{{ $student->reg_no }}</code></p>
                                        <div class="alert alert-info">
                                            Password will be reset to: <strong>sjut123456</strong>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Reset Password</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="card-footer bg-light d-flex justify-content-between">
            {{ $students->links('vendor.pagination.bootstrap-5') }}
            <small class="text-muted">Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}</small>
        </div>
        @endif
    </div>
</div>

<!-- ADD STUDENT MODAL -->
@if(auth()->user()->hasRole('Admin|Administrator|Dean Of Students'))
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('students.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5>Add New Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Reg No <span class="text-danger">*</span></label>
                            <input type="text" name="reg_no" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="">Choose</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Program <span class="text-danger">*</span></label>
                            <select name="program_id" class="form-select" required @change="loadFaculties($event.target.value)">
                                <option value="">Select Program First</option>
                                @foreach($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Faculty <span class="text-danger">*</span></label>
                            <select name="faculty_id" class="form-select" required>
                                <option value="">Select Program first</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label>Email (optional)</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label>Phone (optional)</label>
                            <input type="text" name="phone" class="form-control" placeholder="+255 xxx xxx xxx">
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        Default password = <strong>Reg No</strong> | First login auto-activates account
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Student</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif


<!-- EXPORT MODAL -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="GET" action="{{ route('students.export.excel') }}" target="_blank">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Export Students to Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Faculty -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Faculty</label>
                            @if(auth()->user()->hasRole('Admin|Administrator|Dean Of Students'))
                            <select name="faculty_ids[]" class="form-select form-select-sm" multiple size="5">
                                <option value="">All Faculties</option>
                                @foreach($faculties->sortBy('name') as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                            @else
                            <!-- Lecturer sees only their faculty -->
                            @php
                                $lecturerFaculty = auth()->user()->courses()->first()?->program?->faculties?->first();
                            @endphp
                            <input type="text" class="form-control" value="{{ $lecturerFaculty?->name ?? 'No Faculty' }}" disabled>
                            <input type="hidden" name="faculty_ids[]" value="{{ $lecturerFaculty?->id }}">
                            @endif
                        </div>

                        <!-- Program -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Program</label>
                            <select name="program_ids[]" class="form-select form-select-sm" multiple size="5">
                                <option value="">All Programs</option>
                                @foreach($programs->sortBy('name') as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                        </div>

                        <!-- Status -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Not Activated</option>
                                <option value="Alumni">Alumni</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        Download Excel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- IMPORT MODAL -->
<div class="modal fade" id="importStudentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5>Import Students from Excel/CSV</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        Required: <code>idnumber</code>, <code>gender</code><br>
                        Optional: <code>firstname</code>, <code>lastname</code>, <code>email</code>
                    </div>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload & Import</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function studentManager() {
    return {
        search: '{{ request('search') }}',
        faculty_id: '{{ request('faculty_id') }}',
        program_id: '{{ request('program_id') }}',
        status: '{{ request('status') }}',
        faculties: @json($facultiesByProgram),

        applyFilters() {
            const params = new URLSearchParams();
            if (this.search) params.append('search', this.search);
            if (this.faculty_id) params.append('faculty_id', this.faculty_id);
            if (this.program_id) params.append('program_id', this.program_id);
            if (this.status) params.append('status', this.status);
            window.location = `?${params.toString()}`;
        },

        resetFilters() {
            this.search = this.faculty_id = this.program_id = this.status = '';
            window.location = window.location.pathname;
        },

        loadFaculties(programId, preselected = null) {
            const selects = document.querySelectorAll('select[name="faculty_id"]');
            selects.forEach(select => {
                select.innerHTML = '<option value="">Loading...</option>';
                if (!programId || !this.faculties[programId]) {
                    select.innerHTML = '<option value="">No faculty available</option>';
                    return;
                }
                let html = '<option value="">Select Faculty</option>';
                Object.entries(this.faculties[programId]).forEach(([id, name]) => {
                    const sel = preselected == id ? 'selected' : '';
                    html += `<option value="${id}" ${sel}>${name}</option>`;
                });
                select.innerHTML = html;
            });
        },

        init() {
            document.addEventListener('shown.bs.modal', e => {
                const program = e.target.querySelector('select[name="program_id"]');
                if (program?.value) {
                    const faculty = e.target.querySelector('select[name="faculty_id"]');
                    this.loadFaculties(program.value, faculty?.value);
                }
            });
        }
    }
}
</script>
@endsection

@section('styles')
<style>
    .bg-pink { background-color: #e91e63 !important; }
    code { font-size: 90%; background: #f1f3f5; padding: 2px 6px; border-radius: 4px; }
    .btn-sm { padding: 0.35rem 0.65rem; }
    [title] { cursor: help; }
    .table td { vertical-align: middle; }
</style>
@endsection
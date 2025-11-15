@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <!-- Header -->
    <div class="card-header" style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 text-white">Students</h5>
            <span class="badge bg-light text-dark">{{ $students->total() }} Students</span>
        </div>

        <!-- Filters -->
        <div class="bg-white p-3 rounded shadow-sm mt-3">
            <form method="GET" class="row g-2 alignuddha-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search name, reg, email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="faculty_id" class="form-select form-select-sm">
                        <option value="">All Faculties</option>
                        @foreach($faculties as $f)
                            <option value="{{ $f->id }}" {{ request('faculty_id') == $f->id ? 'selected' : '' }}>
                                {{ $f->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="program_id" class="form-select form-select-sm">
                        <option value="">All Programs</option>
                        @foreach($programs as $p)
                            <option value="{{ $p->id }}" {{ request('program_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="year" class="form-select form-select-sm">
                        <option value="">All Years</option>
                        @for($y = 1; $y <= 5; $y++)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                Year {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        @if($students->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-people display-1"></i>
                <p class="mt-3">No students found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Reg No</th>
                            <th>Email</th>
                            <th>Faculty</th>
                            <th>Program</th>
                            <th>Year</th>
                            <th>Gender</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr>
                                <td>{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                                <td>
                                    <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>
                                </td>
                                <td><code>{{ $student->reg_no }}</code></td>
                                <td>{{ $student->email }}</td>
                                <td>
                                    <span class="badge bg-info text-white">
                                        {{ $student->faculty?->name ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $student->program?->name ?? '—' }}</td>
                                <td>
                                    @php
                                        $year = substr($student->reg_no, -2);
                                    @endphp
                                    Year {{ $year }}
                                </td>
                                <td>
                                    <span class="badge {{ $student->gender === 'male' ? 'bg-primary' : 'bg-pink' }}">
                                        {{ ucfirst($student->gender) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-light border-top-0">
                {{ $students->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
    .bg-pink { background-color: #e91e63 !important; }
    .table-hover tbody tr:hover { background-color: #f1f3f5; }
</style>
@endsection
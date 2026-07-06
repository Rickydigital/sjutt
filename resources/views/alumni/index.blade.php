@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <h5 class="mb-0 text-white">Alumni Management</h5>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-dark fs-6">{{ $alumni->total() }} Total</span>
            @can('manage alumni')
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addAlumniModal">Add</button>
                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#importAlumniModal">Import</button>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mx-4 mt-3">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3">{!! session('error') !!}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="bg-white p-3 border-bottom">
        <form method="GET" action="{{ route('alumni.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search name, email, phone, NIDA...">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">Activation</option>
                    <option value="1" @selected(request('is_active') === '1')>Activated</option>
                    <option value="0" @selected(request('is_active') === '0')>Not Activated</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="graduation_year_id" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    @foreach($graduationYears as $year)
                        <option value="{{ $year->id }}" @selected(request('graduation_year_id') == $year->id)>{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary btn-sm">Apply</button>
                <a href="{{ route('alumni.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email / Phone</th>
                        <th>Education</th>
                        <th>Employment</th>
                        <th>Settlement</th>
                        <th>Status</th>
                        @can('manage alumni')<th class="text-center">Actions</th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($alumni as $alumnus)
                        @php
                            $education = $alumnus->educations->first();
                            $employment = $alumnus->employments->first();
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($alumni->currentPage() - 1) * $alumni->perPage() }}</td>
                            <td>
                                <strong>{{ $alumnus->full_name }}</strong><br>
                                <small class="text-muted">{{ ucfirst($alumnus->gender ?? '—') }}</small>
                            </td>
                            <td>
                                <a href="mailto:{{ $alumnus->email }}">{{ $alumnus->email }}</a><br>
                                <small>{{ $alumnus->phone ?? '—' }}</small>
                            </td>
                            <td>
                                <small>{{ $education?->faculty?->name ?? '—' }}</small><br>
                                <small>{{ $education?->program?->name ?? '—' }}</small><br>
                                <span class="badge bg-secondary">{{ $education?->graduationYear?->year ?? 'No year' }}</span>
                            </td>
                            <td>
                                <small>{{ $employment?->employmentState?->name ?? '—' }}</small><br>
                                <small>{{ $employment?->employmentSector?->name ?? '—' }}</small><br>
                                <small>{{ $employment?->organization ?? 'NIL' }}</small>
                            </td>
                            <td>
                                <small>{{ $alumnus->country?->name ?? '—' }}</small><br>
                                <small>{{ $alumnus->settlement_region }} {{ $alumnus->settlement_city }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $alumnus->status === 'active' ? 'bg-success' : ($alumnus->status === 'suspended' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ ucfirst($alumnus->status) }}</span><br>
                                <small>{{ $alumnus->is_active ? 'Activated' : 'Not Activated' }}</small>
                            </td>
                            @can('manage alumni')
                            <td class="text-center">
                                <a class="btn btn-sm btn-outline-info" href="{{ route('alumni.show', $alumnus) }}">View</a>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAlumniModal{{ $alumnus->id }}">Edit</button>
                                @if(!$alumnus->is_active)
                                    <form action="{{ route('alumni.activate', $alumnus) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Activate</button></form>
                                @endif
                                <form action="{{ route('alumni.suspend', $alumnus) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger">Suspend</button></form>
                            </td>
                            @endcan
                        </tr>
                        @include('alumni.partials-edit-modal', ['alumnus' => $alumnus])
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">No alumni found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-light d-flex justify-content-between">
            {{ $alumni->links('vendor.pagination.bootstrap-5') }}
            <small class="text-muted">Showing {{ $alumni->firstItem() }}–{{ $alumni->lastItem() }} of {{ $alumni->total() }}</small>
        </div>
    </div>
</div>

@include('alumni.partials-create-modal')
@include('alumni.partials-import-modal')
@endsection

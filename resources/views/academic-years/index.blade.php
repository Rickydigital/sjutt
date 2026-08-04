@extends('components.app-main-layout')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1" style="color:#4B2E83;">
                <i class="fas fa-calendar-alt me-2"></i>Academic Years
            </h1>
            <p class="text-muted mb-0">Manage academic years used by the Almanac, timetable and examinations.</p>
        </div>

        <a href="{{ route('academic-years.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Academic Year
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('academic-years.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="status_filter" class="form-label">Filter by status</label>
                    <select name="status" id="status_filter" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-outline-primary" type="submit">Filter</button>
                    <a href="{{ route('academic-years.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header text-white" style="background:linear-gradient(135deg,#6f42c1,#4B2E83);">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Academic Year List</span>
                <span class="badge bg-light text-dark">
                    Current: {{ $currentAcademicYear?->name ?? 'None' }}
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Academic Year</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Activated At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($academicYears as $academicYear)
                        <tr>
                            <td class="fw-semibold">{{ $academicYear->name }}</td>
                            <td>{{ $academicYear->start_date->format('d M Y') }}</td>
                            <td>{{ $academicYear->end_date->format('d M Y') }}</td>
                            <td>
                                @php
                                    $badge = match($academicYear->status) {
                                        'active' => 'success',
                                        'archived' => 'secondary',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ ucfirst($academicYear->status) }}</span>
                            </td>
                            <td>{{ $academicYear->activated_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('academic-years.edit', $academicYear) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>

                                @if(!$academicYear->isActive())
                                    <form method="POST" action="{{ route('academic-years.activate', $academicYear) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit" onclick="return confirm('Activate this academic year? The currently active year will be archived.')">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                @endif

                                @if($academicYear->status !== 'archived')
                                    <form method="POST" action="{{ route('academic-years.archive', $academicYear) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" type="submit" onclick="return confirm('Archive this academic year?')">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                    </form>
                                @endif

                                @if(!$academicYear->isActive())
                                    <form method="POST" action="{{ route('academic-years.destroy', $academicYear) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Delete this academic year?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No academic years found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($academicYears->hasPages())
            <div class="card-footer">
                {{ $academicYears->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

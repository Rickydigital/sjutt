@extends('components.app-main-layout')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Header --}}
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header d-flex justify-content-between align-items-center"
         style="background: linear-gradient(135deg, #6f42c1, #5a2d91); color: white;">
        <div class="d-flex align-items-center gap-3">
            @if($alumnus->profile_photo)
                <img src="{{ asset('storage/' . $alumnus->profile_photo) }}"
                     class="rounded-circle" width="56" height="56" style="object-fit:cover; border:2px solid rgba(255,255,255,0.5);">
            @else
                <div class="rounded-circle bg-white d-flex align-items-center justify-content-center"
                     style="width:56px;height:56px;font-size:1.4rem;font-weight:700;color:#6f42c1;">
                    {{ strtoupper(substr($alumnus->f_name ?? 'A', 0, 1)) }}{{ strtoupper(substr($alumnus->l_name ?? '', 0, 1)) }}
                </div>
            @endif
            <div>
                <h5 class="mb-0 text-white fw-bold">{{ $alumnus->full_name }}</h5>
                <small class="text-white-50">{{ $alumnus->email }}</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @php
                $statusClass = match($alumnus->status) {
                    'active' => 'success',
                    'suspended' => 'danger',
                    default => 'secondary',
                };
            @endphp
            <span class="badge bg-{{ $statusClass }}">{{ ucfirst($alumnus->status) }}</span>
            <span class="badge {{ $alumnus->is_active ? 'bg-info' : 'bg-warning text-dark' }}">
                {{ $alumnus->is_active ? 'Activated' : 'Not Activated' }}
            </span>
            <a href="{{ route('alumni.index') }}" class="btn btn-light btn-sm ms-2">← Back</a>
        </div>
    </div>

    {{-- Action bar --}}
    <div class="card-body border-bottom py-2 d-flex flex-wrap gap-2">
        @canany(['manage alumni', 'edit alumni'])
            <button class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal" data-bs-target="#editAlumniModal{{ $alumnus->id }}">
                <i class="fas fa-edit me-1"></i> Edit
            </button>
        @endcanany

        @can('manage alumni')
            @if(!$alumnus->is_active && $alumnus->status !== 'suspended')
                <form method="POST" action="{{ route('alumni.activate', $alumnus) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-success">
                        <i class="fas fa-check me-1"></i> Activate
                    </button>
                </form>
            @elseif($alumnus->is_active)
                <form method="POST" action="{{ route('alumni.deactivate', $alumnus) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-warning">
                        <i class="fas fa-pause me-1"></i> Deactivate
                    </button>
                </form>
            @endif

            @if($alumnus->status !== 'suspended')
                <form method="POST" action="{{ route('alumni.suspend', $alumnus) }}" class="d-inline"
                      onsubmit="return confirm('Suspend {{ $alumnus->full_name }}? They will be locked out immediately.')">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-danger">
                        <i class="fas fa-ban me-1"></i> Suspend
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('alumni.activate', $alumnus) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-success">
                        <i class="fas fa-unlock me-1"></i> Unsuspend
                    </button>
                </form>
            @endif

            @if(!$alumnus->is_active)
                <form method="POST" action="{{ route('alumni.resend-temp-password', $alumnus) }}" class="d-inline"
                      onsubmit="return confirm('Generate a new temporary password and email it to {{ $alumnus->email }}?')">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-key me-1"></i> Resend Temp Password
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('alumni.send-reset-link', $alumnus) }}" class="d-inline"
                  onsubmit="return confirm('Send a password reset link to {{ $alumnus->email }}? It will expire in 48 hours.')">
                @csrf
                <button class="btn btn-sm btn-outline-info">
                    <i class="fas fa-link me-1"></i> Send Reset Link
                </button>
            </form>
        @endcan

        @can('manage alumni')
            <form method="POST" action="{{ route('alumni.destroy', $alumnus) }}" class="d-inline ms-auto"
                  onsubmit="return confirm('Permanently delete {{ $alumnus->full_name }}? This cannot be undone.')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">
                    <i class="fas fa-trash me-1"></i> Delete
                </button>
            </form>
        @endcan
    </div>
</div>

<div class="row g-3">
    {{-- Personal details --}}
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-light fw-semibold">Personal Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Phone</dt>
                    <dd class="col-7">{{ $alumnus->phone ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Date of Birth</dt>
                    <dd class="col-7">{{ optional($alumnus->date_of_birth)->format('d M Y') ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Gender</dt>
                    <dd class="col-7">{{ ucfirst($alumnus->gender ?? '—') }}</dd>

                    <dt class="col-5 text-muted">NIDA Number</dt>
                    <dd class="col-7">{{ $alumnus->nida_number ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Country</dt>
                    <dd class="col-7">{{ $alumnus->country?->name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Region</dt>
                    <dd class="col-7">{{ $alumnus->settlement_region ?? '—' }}</dd>

                    <dt class="col-5 text-muted">City</dt>
                    <dd class="col-7">{{ $alumnus->settlement_city ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Meetings</dt>
                    <dd class="col-7">
                        <span class="badge {{ $alumnus->interested_meetings ? 'bg-success' : 'bg-secondary' }}">
                            {{ $alumnus->interested_meetings ? 'Interested' : 'Not interested' }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted">Social Platform</dt>
                    <dd class="col-7">
                        <span class="badge {{ $alumnus->interested_social_platform ? 'bg-success' : 'bg-secondary' }}">
                            {{ $alumnus->interested_social_platform ? 'Interested' : 'Not interested' }}
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Account info --}}
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-light fw-semibold">Account Activity</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6 text-muted">Imported At</dt>
                    <dd class="col-6">{{ optional($alumnus->imported_at)->format('d M Y H:i') ?? '—' }}</dd>

                    <dt class="col-6 text-muted">Temp Password Sent</dt>
                    <dd class="col-6">
                        @if($alumnus->temporary_password_sent_at)
                            <span class="text-success">{{ $alumnus->temporary_password_sent_at->format('d M Y H:i') }}</span>
                        @else
                            <span class="text-danger">Not sent</span>
                        @endif
                    </dd>

                    <dt class="col-6 text-muted">First Login</dt>
                    <dd class="col-6">{{ optional($alumnus->first_login_at)->format('d M Y H:i') ?? '—' }}</dd>

                    <dt class="col-6 text-muted">Last Login</dt>
                    <dd class="col-6">{{ optional($alumnus->last_login_at)->format('d M Y H:i') ?? '—' }}</dd>

                    <dt class="col-6 text-muted">Password Changed</dt>
                    <dd class="col-6">{{ optional($alumnus->password_changed_at)->format('d M Y H:i') ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Education --}}
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light fw-semibold">Education</div>
            @if($alumnus->educations->isEmpty())
                <div class="card-body text-muted">No education records.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Faculty</th><th>Programme</th><th>Major</th><th>Graduation Year</th></tr>
                        </thead>
                        <tbody>
                            @foreach($alumnus->educations as $ed)
                                <tr>
                                    <td>{{ $ed->faculty?->name ?? '—' }}</td>
                                    <td>{{ $ed->program?->name ?? '—' }}</td>
                                    <td>{{ $ed->degree_program_major ?? '—' }}</td>
                                    <td>{{ $ed->graduationYear?->year ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Employment --}}
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light fw-semibold">Employment</div>
            @if($alumnus->employments->isEmpty())
                <div class="card-body text-muted">No employment records.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>State</th><th>Sector</th><th>Organisation</th><th>Year</th><th>Current</th></tr>
                        </thead>
                        <tbody>
                            @foreach($alumnus->employments as $em)
                                <tr>
                                    <td>{{ $em->employmentState?->name ?? '—' }}</td>
                                    <td>{{ $em->employmentSector?->name ?? '—' }}</td>
                                    <td>{{ $em->organization ?? '—' }}</td>
                                    <td>{{ $em->employmentYear?->year ?? '—' }}</td>
                                    <td>
                                        @if($em->is_current)
                                            <span class="badge bg-success">Current</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Social platforms --}}
    @if($alumnus->socialPlatforms->isNotEmpty())
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light fw-semibold">Social Platforms</div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @foreach($alumnus->socialPlatforms as $platform)
                        <span class="badge bg-primary">{{ $platform->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Reuse the existing edit modal --}}
@include('alumni.partials-edit-modal', ['alumnus' => $alumnus])

@endsection

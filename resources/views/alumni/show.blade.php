@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex justify-content-between">
        <h5 class="mb-0 text-white">{{ $alumnus->full_name }}</h5>
        <a href="{{ route('alumni.index') }}" class="btn btn-light btn-sm">Back</a>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-4"><strong>Email:</strong><br>{{ $alumnus->email }}</div>
            <div class="col-md-4"><strong>Phone:</strong><br>{{ $alumnus->phone ?? '—' }}</div>
            <div class="col-md-4"><strong>Status:</strong><br>{{ ucfirst($alumnus->status) }} / {{ $alumnus->is_active ? 'Activated' : 'Not Activated' }}</div>
            <div class="col-md-4"><strong>NIDA:</strong><br>{{ $alumnus->nida_number ?? '—' }}</div>
            <div class="col-md-4"><strong>Gender:</strong><br>{{ ucfirst($alumnus->gender ?? '—') }}</div>
            <div class="col-md-4"><strong>DOB:</strong><br>{{ optional($alumnus->date_of_birth)->format('d/m/Y') ?? '—' }}</div>
        </div>
        <hr>
        <h6>Education</h6>
        <ul>
            @foreach($alumnus->educations as $education)
                <li>{{ $education->program?->name ?? '—' }} | {{ $education->faculty?->name ?? '—' }} | {{ $education->graduationYear?->year ?? '—' }}</li>
            @endforeach
        </ul>
        <h6>Employment</h6>
        <ul>
            @foreach($alumnus->employments as $employment)
                <li>{{ $employment->employmentState?->name ?? '—' }} | {{ $employment->employmentSector?->name ?? '—' }} | {{ $employment->organization ?? 'NIL' }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endsection

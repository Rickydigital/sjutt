@extends('components.app-main-layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header text-white" style="background:linear-gradient(135deg,#6f42c1,#4B2E83);">
            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit {{ $academicYear->name }}</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('academic-years.update', $academicYear) }}">
                @include('academic-years._form', ['academicYear' => $academicYear])
            </form>
        </div>
    </div>
</div>
@endsection

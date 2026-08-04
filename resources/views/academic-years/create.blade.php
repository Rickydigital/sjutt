@extends('components.app-main-layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header text-white" style="background:linear-gradient(135deg,#6f42c1,#4B2E83);">
            <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Create Academic Year</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('academic-years.store') }}">
                @include('academic-years._form')
            </form>
        </div>
    </div>
</div>
@endsection

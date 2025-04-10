@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="card shadow-sm mt-4">
        <div class="card-header" style="background-color: #4B2E83; color: white;">
            <h4 class="mb-0"><i class="fa fa-info-circle mr-2"></i>Year Details</h4>
        </div>
        <div class="card-body">
            <p><strong>Year:</strong> {{ $year->year }}</p>
            <a href="{{ route('years.index') }}" class="btn btn-secondary mt-3">
                <i class="fa fa-arrow-left mr-1"></i> Back to List
            </a>
        </div>
    </div>
</div>
@endsection

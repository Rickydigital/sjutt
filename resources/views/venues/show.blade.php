@extends('components.app-main-layout')

@section('content')
    <div class="col-md-8 offset-md-2">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong class="card-title">Venue Details</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> {{ $venue->longform }}</p>
                        <p><strong>Code:</strong> {{ $venue->name }}</p>
                        <p><strong>Building:</strong> {{ $venue->building ? $venue->building->name : 'N/A' }}</p>
                        <p><strong>Capacity:</strong> {{ $venue->capacity }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $venue->type)) }}</p>
                        <p><strong>Latitude:</strong> {{ $venue->lat ?? 'N/A' }}</p>
                        <p><strong>Longitude:</strong> {{ $venue->lng ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="my-3 d-flex flex-row justify-content-end">
                    <a href="{{ route('venues.index') }}" class="btn btn-outline-primary mx-3">Back</a>
                    <a href="{{ route('venues.edit', $venue) }}" class="btn btn-primary">
                        <i class="fa fa-edit mr-1"></i> Edit </a>
                </div>
            </div>
        </div>
    </div>
@endsection

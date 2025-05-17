@extends('components.app-main-layout')

@section('content')
    <div class=" d-flex flex-column align-items-center">
        <div class="card col-md-8">
            <div class="card-header">
                <strong class="card-title">Building Details</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> {{ $building->name }}</p>
                        <p><strong>Description:</strong> {{ $building->description ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Venues:</strong> {{ $building->venues->count() }}</p>
                    </div>
                </div>
                @if ($building->venues->isNotEmpty())
                    <h5 class="mt-3">Associated Venues</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Capacity</th>
                                <th>Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($building->venues as $venue)
                                <tr>
                                    <td>{{ $venue->name }}</td>
                                    <td>{{ $venue->capacity }}</td>
                                    <td>{{ $venue->type }}</td>
                                    <td>
                                        <a href="{{ route('venues.show', $venue) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="mt-3">No venues associated with this building.</p>
                @endif
                <div class=" d-flex flex-row justify-content-end mt-3">
                    <a href="{{ route('buildings.index') }}" class="btn btn-outline-primary">Back</a>
                    <a href="{{ route('buildings.edit', $building) }}" class="btn btn-primary mx-3">
                        <i class="bi bi-pencil-square text-white"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

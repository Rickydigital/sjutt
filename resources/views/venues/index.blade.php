@extends('components.app-main-layout')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class=" d-flex flex-row justify-content-between">
                <div class="col-md-4">
                    <strong class="card-title">Venues</strong>
                </div>

                <a href="{{ route('venues.create') }}" class="btn btn-primary"> New Venue </a>

            </div>
        </div>
        <div class="card-body">
            @if ($venues->isEmpty())
                <p class="text-center">No venues found.</p>
            @else
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Building</th>
                            <th>Capacity</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($venues as $venue)
                            <tr>
                                <td>{{ $venue->longform }}</td>
                                <td>{{ $venue->name }}</td>
                                <td>{{ $venue->building ? $venue->building->name : 'N/A' }}</td>
                                <td>{{ $venue->capacity }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $venue->type)) }}</td>
                                <td>
                                    <a href="{{ route('venues.show', $venue) }}" class="action-icon text-primary"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="{{ route('venues.edit', $venue) }}" class="action-icon text-primary"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('venues.destroy', $venue) }}" method="POST"
                                        style="display: inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this venue?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-icon text-danger" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $venues->links() }}
            @endif
        </div>
    </div>
@endsection

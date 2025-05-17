@extends('components.app-main-layout')

@section('content')
    <div class="card">
        <div class="card-header row justify-content-between">
            <strong class="card-title col-md-6">Buildings</strong>
            <div class="col-md-4 d-flex flex-column align-items-end ">
                <a href="{{ route('buildings.create') }}" class="btn btn-primary">
                    New Building
                </a>
            </div>
        </div>
        <div class="card-body">
            @if ($buildings->isEmpty())
                <p class="text-center">No buildings found.</p>
            @else
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Venues</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($buildings as $building)
                            <tr>
                                <td>{{ $building->name }}</td>
                                <td>{{ $building->description ?? 'N/A' }}</td>
                                <td>{{ $building->venues_count }}</td>
                                <td>
                                    <a href="{{ route('buildings.show', $building) }}" class="action-icon text-primary"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="{{ route('buildings.edit', $building) }}" class="action-icon text-primary"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('buildings.destroy', $building) }}" method="POST"
                                        style="display: inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this building?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-icon" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Delete">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $buildings->links() }}
            @endif


        </div>
    </div>
@endsection

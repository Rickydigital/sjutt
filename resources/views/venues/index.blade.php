@extends('components.app-main-layout')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-row justify-content-between">
                <div class="col-md-4">
                    <strong class="card-title">Venues</strong>
                </div>
                <div>
                    <a href="{{ route('venues.create') }}" class="btn btn-primary">New Venue</a>
                    <a href="{{ route('venues.export') }}" class="btn btn-success">Export Venues Now</a>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importModal">Import Buildings & Venues</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if ($venues->isEmpty())
                <p class="text-center">No venues found.</p>
            @else
                <table class="table table-striped">
                    <thead class="bg-primary text-white">
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
                 <div class=" my-2">
                    {{ $venues->links('vendor.pagination.bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Buildings & Venues</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('venues.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="file" class="form-label">Select Excel File</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #4B2E83; color: white;">
                            <strong class="card-title">Buildings</strong>
                            <a href="{{ route('buildings.create') }}" class="btn btn-sm float-right" style="background-color: white; color: #4B2E83;">
                                <i class="fa fa-plus mr-1"></i> Add Building
                            </a>
                        </div>
                        <div class="card-body">
                            @if ($buildings->isEmpty())
                                <p class="text-center">No buildings found.</p>
                            @else
                                <table class="table table-striped table-bordered">
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
                                                    <a href="{{ route('buildings.show', $building) }}" class="btn btn-sm btn-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('buildings.edit', $building) }}" class="btn btn-sm btn-warning">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('buildings.destroy', $building) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this building?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fa fa-trash"></i>
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
                </div>
            </div>
        </div>
    </div>
@endsection
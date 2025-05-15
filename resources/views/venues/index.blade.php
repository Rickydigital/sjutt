@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #4B2E83; color: white;">
                            <strong class="card-title">Venues</strong>
                            <a href="{{ route('venues.create') }}" class="btn btn-sm float-right" style="background-color: white; color: #4B2E83;">
                                <i class="fa fa-plus mr-1"></i> Add Venue
                            </a>
                        </div>
                        <div class="card-body">
                            @if ($venues->isEmpty())
                                <p class="text-center">No venues found.</p>
                            @else
                                <table class="table table-striped table-bordered">
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
                                                    <a href="{{ route('venues.show', $venue) }}" class="btn btn-sm btn-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('venues.edit', $venue) }}" class="btn btn-sm btn-warning">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('venues.destroy', $venue) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this venue?');">
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
                                {{ $venues->links() }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@extends('components.app-main-layout')

@section('content')
    <div class="col-md-8 offset-md-2">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong class="card-title">Create Venue</strong>
            </div>
            <div class="card-body">
                @if ($buildings->isEmpty())
                    <div class="alert alert-warning">No buildings available. Please <a
                            href="{{ route('buildings.create') }}">create a building</a> first.</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                @endif
                <form action="{{ route('venues.store') }}" method="POST" id="venueForm">
                    @csrf
                    <div class="form-group">
                        <label for="longform">Name <span class="text-danger">*</span></label>
                        <input type="text" name="longform" id="longform" class="form-control"
                            value="{{ old('longform') }}" required placeholder="e.g., Main Lecture Hall">
                        @error('longform')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="name">Code <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}"
                            required placeholder="e.g., LH101">
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="building_id">Building</label>
                        <select name="building_id" id="building_id" class="form-control select2">
                            <option value="">Select Building</option>
                            @foreach ($buildings as $building)
                                <option value="{{ $building->id }}"
                                    {{ old('building_id') == $building->id ? 'selected' : '' }}>
                                    {{ $building->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('building_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="capacity">Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="capacity" id="capacity" class="form-control"
                            value="{{ old('capacity') }}" min="1" required>
                        @error('capacity')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="type">Type <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-control select2" required>
                            <option value="">Select Type</option>
                            @foreach ($venueTypes as $type)
                                <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="lat">Latitude</label>
                        <input type="number" name="lat" id="lat" class="form-control"
                            value="{{ old('lat') }}" step="any" placeholder="e.g., 40.7128">
                        @error('lat')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="lng">Longitude</label>
                        <input type="number" name="lng" id="lng" class="form-control"
                            value="{{ old('lng') }}" step="any" placeholder="e.g., -74.0060">
                        @error('lng')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="d-flex flex-row justify-content-end my-2">
                        <a href="{{ route('venues.index') }}" class="btn btn-outline-danger mx-3">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
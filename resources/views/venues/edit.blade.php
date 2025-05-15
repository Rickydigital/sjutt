@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #4B2E83; color: white;">
                            <strong class="card-title">Edit Venue</strong>
                        </div>
                        <div class="card-body">
                            @if ($buildings->isEmpty())
                                <div class="alert alert-warning">No buildings available. Please <a href="{{ route('buildings.create') }}">create a building</a> first.</div>
                            @endif
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                </div>
                            @endif
                            <form action="{{ route('venues.update', $venue) }}" method="POST" id="venueForm">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label for="longform">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="longform" id="longform" class="form-control" value="{{ old('longform', $venue->longform) }}" required placeholder="e.g., Main Lecture Hall">
                                    @error('longform')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="name">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $venue->name) }}" required placeholder="e.g., LH101">
                                    @error('name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="building_id">Building <span class="text-danger">*</span></label>
                                    <select name="building_id" id="building_id" class="form-control select2" required>
                                        <option value="">Select Building</option>
                                        @foreach ($buildings as $building)
                                            <option value="{{ $building->id }}" {{ old('building_id', $venue->building_id) == $building->id ? 'selected' : '' }}>
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
                                    <input type="number" name="capacity" id="capacity" class="form-control" value="{{ old('capacity', $venue->capacity) }}" min="1" required>
                                    @error('capacity')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="type">Type <span class="text-danger">*</span></label>
                                    <select name="type" id="type" class="form-control select2" required>
                                        <option value="">Select Type</option>
                                        @foreach ($venueTypes as $type)
                                            <option value="{{ $type }}" {{ old('type', $venue->type) == $type ? 'selected' : '' }}>
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
                                    <input type="number" name="lat" id="lat" class="form-control" value="{{ old('lat', $venue->lat) }}" step="any" placeholder="e.g., 40.7128">
                                    @error('lat')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="lng">Longitude</label>
                                    <input type="number" name="lng" id="lng" class="form-control" value="{{ old('lng', $venue->lng) }}" step="any" placeholder="e.g., -74.0060">
                                    @error('lng')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group text-right">
                                    <a href="{{ route('venues.index') }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn" style="background-color: #4B2E83; color: white;">Update Venue</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select an option';
                },
                allowClear: false  
            });

            
            $('#building_id').on('change', function() {
                console.log('Building ID changed to: ', $(this).val());
            });

            
            $('#venueForm').on('submit', function(e) {
                const buildingId = $('#building_id').val();
                console.log('Form submitted with building_id: ', buildingId);
                if (!buildingId) {
                    console.warn('Building ID is empty!');
                }
            });
        });
    </script>
@endsection
@extends('components.app-main-layout')

@section('content')
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <strong class="card-title">Create Program</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('programs.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}"
                            required placeholder="e.g., Bachelor of Science in Information Technology">
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="short_name">Short Name <span class="text-danger">*</span></label>
                        <input type="text" name="short_name" id="short_name" class="form-control"
                            value="{{ old('short_name') }}" required placeholder="e.g., BScIT">
                        @error('short_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="total_years">Total Years of Study <span class="text-danger">*</span></label>
                        <input type="number" name="total_years" id="total_years" class="form-control"
                            value="{{ old('total_years') }}" required min="1" max="10" placeholder="e.g., 3">
                        @error('total_years')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4" placeholder="Program description">{{ old('description') }}</textarea>
                        @error('description')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="administrator_id">Administrator <span class="text-danger">*</span></label>
                        <select name="administrator_id" id="administrator_id" class="form-control select2" required>
                            <option value="">Select Administrator</option>
                            @foreach ($administrators as $admin)
                                <option value="{{ $admin->id }}"
                                    {{ old('administrator_id') == $admin->id ? 'selected' : '' }}>
                                    {{ $admin->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('administrator_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class=" d-flex flex-row justify-content-end">
                        <a href="{{ route('programs.index') }}" class="btn btn-outline-danger ">Cancel</a>
                        <button type="submit" class="btn btn-primary mx-2"> Save </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

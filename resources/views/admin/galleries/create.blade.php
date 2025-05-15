
@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <h1 class="font-weight-bold" style="color: #4B2E83;">
                <i class="fa fa-plus mr-2"></i> Create Gallery Item
            </h1>
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('gallery.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="media" class="form-label">Media (Select 1-10 images)</label>
                            <input type="file" class="form-control" id="media" name="media[]" multiple accept="image/*" required>
                            <small class="form-text text-muted">Upload between 1 and 10 images (JPEG, PNG, JPG, GIF; max 2MB each).</small>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('gallery.index') }}" class="btn btn-secondary" style="border-radius: 25px;">Cancel</a>
                            <button type="submit" class="btn btn-primary" style="background-color: #4B2E83; border-color: #4B2E83; border-radius: 25px;">
                                <i class="fa fa-save mr-1"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

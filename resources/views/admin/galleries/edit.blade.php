
@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <h1 class="font-weight-bold" style="color: #4B2E83;">
                <i class="fa fa-edit mr-2"></i> Edit Gallery Item
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
                    <form action="{{ route('gallery.update', $gallery->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description', $gallery->description) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="media" class="form-label">Media (Select 1-10 images to replace existing)</label>
                            <input type="file" class="form-control" id="media" name="media[]" multiple accept="image/*">
                            <small class="form-text text-muted">Leave empty to keep existing images. Upload new images to replace (JPEG, PNG, JPG, GIF; max 2MB each).</small>
                            <div class="mt-2">
                                <strong>Current Images:</strong>
                                <div class="d-flex flex-wrap">
                                    @foreach ($gallery->media ?? [] as $media)
                                        <img src="{{ $media }}" alt="Current Media" class="rounded m-1" style="width: 80px; height: 80px; object-fit: cover;">
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('gallery.index') }}" class="btn btn-secondary" style="border-radius: 25px;">Cancel</a>
                            <button type="submit" class="btn btn-primary" style="background-color: #4B2E83; border-color: #4B2E83; border-radius: 25px;">
                                <i class="fa fa-save mr-1"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

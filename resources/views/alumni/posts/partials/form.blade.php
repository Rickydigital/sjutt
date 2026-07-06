<div class="modal-header"><h5>{{ $post ? 'Edit Post' : 'Add Post' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body row g-3">
    <div class="col-md-8"><label>Title</label><input name="title" class="form-control" value="{{ old('title', $post->title ?? '') }}" required></div>
    <div class="col-md-4"><label>Category</label><select name="category" class="form-select">@foreach(['announcement','news','opportunity','story','general'] as $c)<option value="{{ $c }}" @selected(old('category', $post->category ?? 'general')===$c)>{{ ucfirst($c) }}</option>@endforeach</select></div>
    <div class="col-md-4"><label>Status</label><select name="status" class="form-select">@foreach($post ? ['draft','pending','published','rejected','archived'] : ['draft','published'] as $s)<option value="{{ $s }}" @selected(old('status', $post->status ?? 'draft')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <div class="col-md-8"><label>Image</label><input type="file" name="image" class="form-control"></div>
    <div class="col-12"><label>Body</label><textarea name="body" rows="7" class="form-control" required>{{ old('body', $post->body ?? '') }}</textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-dark">Save</button></div>

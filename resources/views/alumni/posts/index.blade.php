@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
        <h5 class="mb-0 text-white">Alumni Posts</h5>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addPostModal">Add Post</button>
    </div>
    @if(session('success')) <div class="alert alert-success m-3">{{ session('success') }}</div> @endif
    <div class="card-body">
        <form class="row g-2 mb-3">
            <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search post"></div>
            <div class="col-md-3"><select name="status" class="form-select form-select-sm"><option value="">All Status</option>@foreach(['draft','pending','published','rejected','archived'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="category" class="form-select form-select-sm"><option value="">All Categories</option>@foreach(['announcement','news','opportunity','story','general'] as $c)<option value="{{ $c }}" @selected(request('category')===$c)>{{ ucfirst($c) }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary">Filter</button></div>
        </form>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead><tbody>
            @foreach($posts as $post)
            <tr><td><strong>{{ $post->title }}</strong></td><td>{{ $post->category }}</td><td><span class="badge bg-secondary">{{ $post->status }}</span></td><td>{{ $post->published_at?->format('d M Y') ?? '—' }}</td><td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPost{{ $post->id }}">Edit</button>@if($post->status !== 'published')<form method="POST" action="{{ route('alumni.posts.approve', $post) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Approve</button></form>@endif<form method="POST" action="{{ route('alumni.posts.destroy', $post) }}" class="d-inline" onsubmit="return confirm('Delete post?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
            <div class="modal fade" id="editPost{{ $post->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" enctype="multipart/form-data" action="{{ route('alumni.posts.update', $post) }}" class="modal-content">@csrf @method('PUT') @include('alumni.posts.partials.form', ['post' => $post])</form></div></div>
            @endforeach
        </tbody></table></div>
        {{ $posts->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>
<div class="modal fade" id="addPostModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" enctype="multipart/form-data" action="{{ route('alumni.posts.store') }}" class="modal-content">@csrf @include('alumni.posts.partials.form', ['post' => null])</form></div></div>
@endsection

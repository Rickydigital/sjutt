@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <h1 class="font-weight-bold" style="color: #4B2E83;">
                            <i class="fa fa-newspaper mr-2"></i> News List
                        </h1>
                        <a href="{{ route('news.create') }}" class="btn btn-lg" style="background-color: #4B2E83; color: white; border-radius: 25px;">
                            <i class="fa fa-plus mr-1"></i> Add News
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <form method="GET" action="{{ route('news.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search news..." 
                                value="{{ request('search') }}" style="border-radius: 20px 0 0 20px; border-color: #4B2E83;">
                            <div class="input-group-append">
                                <button type="submit" class="btn" style="background-color: #4B2E83; color: white; border-radius: 0 20px 20px 0;">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- News Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">List of News</strong>
                        </div>
                        <div class="card-body p-0">
                            @if ($news->isEmpty())
                                <div class="alert alert-info text-center m-3">
                                    <i class="fa fa-info-circle mr-2"></i> No news found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead style="background-color: #4B2E83; color: white;">
                                            <tr>
                                                <th scope="col"><i class="fa fa-heading mr-2"></i> Title</th>
                                                <th scope="col"><i class="fa fa-comment mr-2"></i> Description</th>
                                                <th scope="col"><i class="fa fa-image mr-2"></i> Image</th>
                                                <th scope="col"><i class="fa fa-cogs mr-2"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($news as $item)
                                                <tr>
                                                    <td class="align-middle">{{ $item->title }}</td>
                                                    <td class="align-middle">{{ Str::limit($item->description, 50) }}</td>
                                                    <td class="align-middle">
                                                        @if ($item->image)
                                                            <img src="{{ asset('storage/' . $item->image) }}" width="50">
                                                        @endif
                                                    </td>
                                                    <td class="align-middle">
                                                        <a href="#" class="btn btn-sm btn-info action-btn" data-toggle="modal" 
                                                            data-target="#viewModal-{{ $item->id }}" title="View">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('news.edit', $item->id) }}" 
                                                            class="btn btn-sm btn-warning action-btn" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('news.destroy', $item->id) }}" method="POST" 
                                                            style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger action-btn" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            @foreach ($news as $item)
                <div class="modal fade" id="viewModal-{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header" style="background-color: #4B2E83; color: white;">
                                <h5 class="modal-title">News Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-4"><strong>Title:</strong></div>
                                    <div class="col-md-8">{{ $item->title }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Description:</strong></div>
                                    <div class="col-md-8">{{ $item->description }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><strong>Image:</strong></div>
                                    <div class="col-md-8">
                                        @if ($item->image)
                                            <img src="{{ asset('storage/' . $item->image) }}" class="img-fluid" style="max-width: 300px;">
                                        @else
                                            No Image
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination -->
            @if ($news->hasPages())
                <div class="row mt-4">
                    <div class="col-md-12">
                        {{ $news->appends(['search' => request('search')])->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('[title]').tooltip({ placement: 'top', trigger: 'hover' });
        });
    </script>
@endsection

<style>
    .table-bordered th, .table-bordered td { border: 2px solid #4B2E83 !important; }
    .table-hover tbody tr:hover { background-color: #f1eef9; transition: background-color 0.3s ease; }
    .btn:hover { opacity: 0.85; transform: translateY(-1px); transition: all 0.2s ease; }
    .card { border: none; border-radius: 10px; overflow: hidden; }
    .action-btn { min-width: 36px; padding: 6px; margin: 0 4px; }
    .table th, .table td { vertical-align: middle; }
</style>
@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <!-- Page Header -->
            <div class="row mb-4 align-items-center">
                <div class="col-md-6">
                    <h1 class="font-weight-bold" style="color: #4B2E83;">
                        <i class="fa fa-image mr-2"></i> Gallery Management
                    </h1>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="{{ route('gallery.create') }}" class="btn btn-primary" style="background-color: #4B2E83; border-color: #4B2E83; border-radius: 25px;">
                        <i class="fa fa-plus mr-1"></i> Create Gallery Item
                    </a>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <form method="GET" action="{{ route('gallery.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search gallery..." 
                                value="{{ request('search') }}" style="border-radius: 20px 0 0 20px; border-color: #4B2E83;">
                            <button type="submit" class="btn btn-primary" style="background-color: #4B2E83; border-color: #4B2E83; border-radius: 0 20px 20px 0;">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 col-lg-8 text-md-end">
                    <button class="btn btn-primary toggle-view" data-view="grid" style="border-radius: 25px;">
                        <i class="fa fa-th-large mr-1"></i> Grid View
                    </button>
                    <button class="btn btn-outline-secondary toggle-view" data-view="table" style="border-radius: 25px;">
                        <i class="fa fa-table mr-1"></i> Table View
                    </button>
                </div>
            </div>

            <!-- Gallery Grid -->
            <div id="grid-view" class="row mb-4">
                @if ($galleries->isEmpty())
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle mr-2"></i> No gallery items found.
                        </div>
                    </div>
                @else
                    @foreach ($galleries as $gallery)
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card shadow-sm border-0 rounded-3 h-100 gallery-card">
                                <div class="card-body p-3">
                                    <div class="gallery-images d-flex flex-wrap mb-3">
                                        @foreach ($gallery->media ?? [] as $media)
                                            <img src="{{ $media }}" alt="Gallery Media" class="rounded m-1" style="width: 80px; height: 80px; object-fit: cover;">
                                        @endforeach
                                    </div>
                                    <h5 class="card-title text-truncate" style="color: #4B2E83;">{{ Str::limit($gallery->description, 50) }}</h5>
                                </div>
                                <div class="card-footer bg-transparent border-0 d-flex justify-content-center gap-2">
                                    <a href="#" class="btn btn-sm btn-info view-modal" data-bs-toggle="modal" data-bs-target="#viewModal-{{ $gallery->id }}" title="View" data-bs-toggle="tooltip">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="{{ route('gallery.edit', $gallery->id) }}" class="btn btn-sm btn-warning" title="Edit" data-bs-toggle="tooltip">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <form action="{{ route('gallery.destroy', $gallery->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this gallery item?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" data-bs-toggle="tooltip" style="background-color: #dc3545; border-color: #dc3545;">
                                            <i class="fa fa-trash" style="color: white;"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Gallery Table -->
            <div id="table-view" class="row mb-4" style="display: none;">
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">List of Gallery Items</strong>
                        </div>
                        <div class="card-body p-0">
                            @if ($galleries->isEmpty())
                                <div class="alert alert-info text-center m-3">
                                    <i class="fa fa-info-circle mr-2"></i> No gallery items found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead style="background-color: #4B2E83; color: white;">
                                            <tr>
                                                <th scope="col"><i class="fa fa-comment mr-2"></i> Description</th>
                                                <th scope="col"><i class="fa fa-image mr-2"></i> Media</th>
                                                <th scope="col"><i class="fa fa-cogs mr-2"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($galleries as $gallery)
                                                <tr>
                                                    <td class="align-middle">{{ Str::limit($gallery->description, 100) }}</td>
                                                    <td class="align-middle">
                                                        <div class="d-flex flex-wrap">
                                                            @foreach ($gallery->media ?? [] as $media)
                                                                <img src="{{ $media }}" alt="Gallery Media" class="rounded m-1" style="width: 50px; height: 50px; object-fit: cover;">
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <a href="#" class="btn btn-sm btn-info view-modal" data-bs-toggle="modal" data-bs-target="#viewModal-{{ $gallery->id }}" title="View" data-bs-toggle="tooltip">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('gallery.edit', $gallery->id) }}" class="btn btn-sm btn-warning" title="Edit" data-bs-toggle="tooltip">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('gallery.destroy', $gallery->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this gallery item?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete" data-bs-toggle="tooltip" style="background-color: #dc3545; border-color: #dc3545;">
                                                                <i class="fa fa-trash" style="color: white;"></i>
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
            @foreach ($galleries as $gallery)
                <div class="modal fade" id="viewModal-{{ $gallery->id }}" tabindex="-1" aria-labelledby="viewModalLabel-{{ $gallery->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content rounded-3">
                            <div class="modal-header" style="background-color: #4B2E83; color: white;">
                                <h5 class="modal-title" id="viewModalLabel-{{ $gallery->id }}">Gallery Item Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-3"><strong>Description:</strong></div>
                                    <div class="col-md-9">{{ $gallery->description }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <p>Media count: {{ count($gallery->media ?? []) }}</p> <!-- Debug line -->
                                        <div id="carousel-{{ $gallery->id }}" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                                            <div class="carousel-inner">
                                                @foreach ($gallery->media ?? [] as $index => $media)
                                                    <div class="carousel-item {{ $index == 0 ? 'active' : '' }}">
                                                        <img src="{{ $media }}" class="d-block w-100" alt="Gallery Media" style="max-height: 400px; object-fit: contain;">
                                                    </div>
                                                @endforeach
                                            </div>
                                            @if (count($gallery->media ?? []) > 1)
                                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel-{{ $gallery->id }}" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Previous</span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#carousel-{{ $gallery->id }}" data-bs-slide="next">
                                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Next</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 25px;">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination -->
            @if ($galleries->hasPages())
                <div class="row mt-4">
                    <div class="col-md-12">
                        {{ $galleries->appends(['search' => request('search')])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    @section('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Debug modal clicks
                document.querySelectorAll('.view-modal').forEach(button => {
                    button.addEventListener('click', function() {
                        console.log('View modal button clicked for modal ID: ' + this.getAttribute('data-bs-target'));
                    });
                });

                // Initialize tooltips
                try {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                        new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                } catch (e) {
                    console.error('Error initializing tooltips:', e);
                }

                // Toggle between grid and table view
                const gridView = document.getElementById('grid-view');
                const tableView = document.getElementById('table-view');
                const toggleButtons = document.querySelectorAll('.toggle-view');

                toggleButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const view = this.getAttribute('data-view');
                        if (view === 'grid') {
                            gridView.style.display = 'flex';
                            tableView.style.display = 'none';
                            toggleButtons[0].classList.add('btn-primary');
                            toggleButtons[0].classList.remove('btn-outline-secondary');
                            toggleButtons[1].classList.add('btn-outline-secondary');
                            toggleButtons[1].classList.remove('btn-primary');
                        } else {
                            gridView.style.display = 'none';
                            tableView.style.display = 'block';
                            toggleButtons[1].classList.add('btn-primary');
                            toggleButtons[1].classList.remove('btn-outline-secondary');
                            toggleButtons[0].classList.add('btn-outline-secondary');
                            toggleButtons[0].classList.remove('btn-primary');
                        }
                    });
                });

                // Initialize carousels when modals are shown
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.addEventListener('shown.bs.modal', function() {
                        const carousel = this.querySelector('.carousel');
                        if (carousel) {
                            try {
                                new bootstrap.Carousel(carousel, {
                                    interval: 3000,
                                    ride: 'carousel'
                                });
                                console.log('Carousel initialized for modal ID: ' + modal.id);
                            } catch (e) {
                                console.error('Error initializing carousel:', e);
                            }
                        }
                    });
                });
            });
        </script>
    @endsection

    @section('style')
        <style>
            .gallery-card {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .gallery-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2) !important;
            }
            .gallery-images img {
                transition: opacity 0.3s ease;
            }
            .gallery-images img:hover {
                opacity: 0.9;
            }
            .table th, .table td {
                vertical-align: middle;
            }
            .table-hover tbody tr:hover {
                background-color: #f1eef9;
            }
            .btn:hover {
                opacity: 0.85;
                transform: translateY(-1px);
                transition: all 0.2s ease;
            }
            .card {
                border-radius: 10px;
                overflow: hidden;
            }
            .btn-danger {
                background-color: #dc3545 !important;
                border-color: #dc3545 !important;
            }
            .btn-danger i {
                color: white !important;
            }
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            .gallery-images {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            .table .d-flex {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            .carousel-control-prev,
            .carousel-control-next {
                display: block !important;
                opacity: 0.9;
                width: 5%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1;
            }
            .carousel-control-prev:hover,
            .carousel-control-next:hover {
                opacity: 1;
            }
            .carousel-inner {
                background: #f8f9fa; /* Light background for better visibility */
            }
        </style>
    @endsection
@endsection
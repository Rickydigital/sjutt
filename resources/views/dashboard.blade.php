@extends('components.app-main-layout')
@section('content')
    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        <div>
            <h3 class="fw-bold mb-3">Dashboard</h3>
        </div>
        <div class="ms-md-auto py-2 py-md-0">
            <a href="#" class="btn btn-label-info btn-round me-2">Manage</a>
            <a href="#" class="btn btn-primary btn-round">Add Customer</a>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Buildings</p>
                                <h4 class="card-title">16</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Venues</p>
                                <h4 class="card-title">37</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                <i class="fas fa-luggage-cart"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Programs</p>
                                <h4 class="card-title">33</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card card-stats card-round">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-icon">
                            <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                <i class="far fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="col col-stats ms-3 ms-sm-0">
                            <div class="numbers">
                                <p class="card-category">Courses</p>
                                <h4 class="card-title">47</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card card-round">
                <div class="card-body">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">Lecturers</div>
                        <div class="card-tools">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-clean me-0" type="button" id="dropdownMenuButton"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-list py-4">
                        <div class="item-list">
                            <div class="avatar">
                                <img src="{{ asset('app-assets/img/jm_denis.jpg') }}" alt="..."
                                    class="avatar-img rounded-circle" />
                            </div>
                            <div class="info-user ms-3">
                                <div class="username">Jimmy Denis</div>
                                {{-- <div class="status"></div> --}}
                            </div>
                            <button class="btn btn-icon btn-link op-8 me-1">
                                <i class="far fa-envelope"></i>
                            </button>
                            <button class="btn btn-icon btn-link btn-danger op-8">
                                <i class="fas fa-ban"></i>
                            </button>
                        </div>
                        <div class="item-list">
                            <div class="avatar">
                                <span class="avatar-title rounded-circle border border-white">CF</span>
                            </div>
                            <div class="info-user ms-3">
                                <div class="username">Chandra Felix</div>
                                {{-- <div class="status">Sales Promotion</div> --}}
                            </div>
                            <button class="btn btn-icon btn-link op-8 me-1">
                                <i class="far fa-envelope"></i>
                            </button>
                            <button class="btn btn-icon btn-link btn-danger op-8">
                                <i class="fas fa-ban"></i>
                            </button>
                        </div>
                        <div class="item-list">
                            <div class="avatar">
                                <img src="{{ asset('app-assets/img/talha.jpg') }}" alt="..."
                                    class="avatar-img rounded-circle" />
                            </div>
                            <div class="info-user ms-3">
                                <div class="username">Talha</div>
                                {{-- <div class="status">Front End Designer</div> --}}
                            </div>
                            <button class="btn btn-icon btn-link op-8 me-1">
                                <i class="far fa-envelope"></i>
                            </button>
                            <button class="btn btn-icon btn-link btn-danger op-8">
                                <i class="fas fa-ban"></i>
                            </button>
                        </div>
                        <div class="item-list">
                            <div class="avatar">
                                <img src="{{ asset('app-assets/img/chadengle.jpg') }}" alt="..."
                                    class="avatar-img rounded-circle" />
                            </div>
                            <div class="info-user ms-3">
                                <div class="username">Chad</div>
                                {{-- <div class="status">CEO Zeleaf</div> --}}
                            </div>
                            <button class="btn btn-icon btn-link op-8 me-1">
                                <i class="far fa-envelope"></i>
                            </button>
                            <button class="btn btn-icon btn-link btn-danger op-8">
                                <i class="fas fa-ban"></i>
                            </button>
                        </div>
                        <div class="item-list">
                            <div class="avatar">
                                <span class="avatar-title rounded-circle border border-white bg-primary">H</span>
                            </div>
                            <div class="info-user ms-3">
                                <div class="username">Hizrian</div>
                                {{-- <div class="status">Web Designer</div> --}}
                            </div>
                            <button class="btn btn-icon btn-link op-8 me-1">
                                <i class="far fa-envelope"></i>
                            </button>
                            <button class="btn btn-icon btn-link btn-danger op-8">
                                <i class="fas fa-ban"></i>
                            </button>
                        </div>
                        <div class="item-list">
                            <div class="avatar">
                                <span class="avatar-title rounded-circle border border-white bg-secondary">F</span>
                            </div>
                            <div class="info-user ms-3">
                                <div class="username">Farrah</div>
                                {{-- <div class="status">Marketing</div> --}}
                            </div>
                            <button class="btn btn-icon btn-link op-8 me-1">
                                <i class="far fa-envelope"></i>
                            </button>
                            <button class="btn btn-icon btn-link btn-danger op-8">
                                <i class="fas fa-ban"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">Upcoming Calendar events</div>
                        <div class="card-tools">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-clean me-0" type="button" id="dropdownMenuButton"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        {{-- <!-- Projects table --> --}}
                        <table class="table align-items-center mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th scope="col">Event name</th>
                                    <th scope="col" class="text-end">Date & Time</th>
                                    <th scope="col" class="text-end">Members</th>
                                    <th scope="col" class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        Start UE exams
                                    </th>
                                    <td class="text-end">Mar 19, 2020, 2.45pm</td>
                                    <td class="text-end">Undergraduate students</td>
                                    <td class="text-end">
                                        <span class="badge badge-danger">Pending</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        Start UE exams
                                    </th>
                                    <td class="text-end">Mar 19, 2020, 2.45pm</td>
                                    <td class="text-end">Undergraduate students</td>
                                    <td class="text-end">
                                        <span class="badge badge-danger">Pending</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        Start UE exams
                                    </th>
                                    <td class="text-end">Mar 19, 2020, 2.45pm</td>
                                    <td class="text-end">Undergraduate students</td>
                                    <td class="text-end">
                                        <span class="badge badge-danger">Pending</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        Start UE exams
                                    </th>
                                    <td class="text-end">Mar 19, 2020, 2.45pm</td>
                                    <td class="text-end">Undergraduate students</td>
                                    <td class="text-end">
                                        <span class="badge badge-danger">Pending</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        Start UE exams
                                    </th>
                                    <td class="text-end">Mar 19, 2020, 2.45pm</td>
                                    <td class="text-end">Undergraduate students</td>
                                    <td class="text-end">
                                        <span class="badge badge-danger">Pending</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        Start UE exams
                                    </th>
                                    <td class="text-end">Mar 19, 2020, 2.45pm</td>
                                    <td class="text-end">Undergraduate students</td>
                                    <td class="text-end">
                                        <span class="badge badge-danger">Pending</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        Start UE exams
                                    </th>
                                    <td class="text-end">Mar 19, 2020, 2.45pm</td>
                                    <td class="text-end">Undergraduate students</td>
                                    <td class="text-end">
                                        <span class="badge badge-danger">Pending</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

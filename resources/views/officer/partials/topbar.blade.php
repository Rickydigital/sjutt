@php
    $officer = auth('stuofficer')->user();
@endphp

<div class="main-header">
    <div class="main-header-logo">
        {{-- Logo Header --}}
        <div class="logo-header" data-background-color="dark">
            <a href="{{ route('officer.dashboard') }}" class="logo">
                <img src="{{ asset('app-assets/img/kaiadmin/logo_light.svg') }}"
                     alt="SJUT Officer Portal"
                     class="navbar-brand"
                     height="20" />
            </a>

            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>

            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
        {{-- End Logo Header --}}
    </div>

    {{-- Navbar Header --}}
    <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
        <div class="container-fluid">

            {{-- LEFT: Context Info (Officer Role) --}}
            <div class="navbar-header-left d-none d-lg-flex align-items-center">
                <span class="badge bg-primary">
                    <i class="bi bi-person-badge me-1"></i>
                    Election Officer
                </span>
            </div>

            {{-- RIGHT --}}
            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">

                {{-- Officer Profile Dropdown --}}
                <li class="nav-item topbar-user dropdown hidden-caret">
                    <a class="dropdown-toggle profile-pic"
                       data-bs-toggle="dropdown"
                       href="#"
                       aria-expanded="false">
                        <div class="avatar-sm">
                            <img src="{{ asset('app-assets/img/user.png') }}"
                                 alt="Officer"
                                 class="avatar-img rounded-circle" />
                        </div>
                        <span class="profile-username d-none d-md-block">
                            <span class="op-7">Hi,</span>
                            <span class="fw-bold">
                                {{ $officer->first_name }}
                            </span>
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-user animated fadeIn">
                        <div class="dropdown-user-scroll scrollbar-outer">

                            {{-- Officer Info --}}
                            <li>
                                <div class="user-box">
                                    <div class="avatar-md">
                                        <img src="{{ asset('app-assets/img/user.png') }}"
                                             alt="Officer"
                                             class="avatar-img rounded-circle" />
                                    </div>
                                    <div class="u-text ms-3">
                                        <h5>{{ $officer->first_name }} {{ $officer->last_name }}</h5>
                                        <p class="text-muted mb-0">{{ $officer->reg_no }}</p>
                                        <span class="badge bg-info mt-1">Election Officer</span>
                                    </div>
                                </div>
                            </li>

                            <li><div class="dropdown-divider"></div></li>

                            {{-- Quick Links --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('officer.elections.index') }}">
                                    <i class="bi bi-box-seam me-2"></i>
                                    My Elections
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="{{ route('officer.results.index') }}">
                                    <i class="bi bi-bar-chart-line-fill me-2"></i>
                                    Results
                                </a>
                            </li>

                            <li><div class="dropdown-divider"></div></li>

                            {{-- Logout --}}
                            <li>
                                <a class="dropdown-item text-danger"
                                   href="#"
                                   onclick="event.preventDefault(); document.getElementById('officer-logout-form').submit();">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Logout
                                </a>

                                <form id="officer-logout-form"
                                      action="{{ route('stu.logout') }}"
                                      method="POST"
                                      class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </div>
                    </ul>
                </li>

            </ul>
        </div>
    </nav>
    {{-- End Navbar --}}
</div>

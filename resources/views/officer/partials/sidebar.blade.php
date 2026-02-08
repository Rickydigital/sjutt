@php
    // officer is logged in with guard stuofficer
    $student = auth('stuofficer')->user();
    $currentRoute = request()->route()?->getName();
@endphp

<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
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
    </div>

    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">

                {{-- Officer Dashboard --}}
                <li class="nav-item {{ request()->routeIs('officer.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('officer.dashboard') }}">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                {{-- Elections Section --}}
                <li class="nav-item {{ request()->routeIs('officer.elections.*') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#officerElections"
                       aria-expanded="{{ request()->routeIs('officer.elections.*') ? 'true' : 'false' }}">
                        <i class="bi bi-box-seam"></i>
                        <p>Elections</p>
                        <span class="caret"></span>
                    </a>

                    <div class="collapse {{ request()->routeIs('officer.elections.*') ? 'show' : '' }}"
                         id="officerElections">
                        <ul class="nav nav-collapse">

                            {{-- Assigned Elections List --}}
                            <li class="{{ request()->routeIs('officer.elections.index') ? 'active' : '' }}">
                                <a href="{{ route('officer.elections.index') }}">
                                    <span class="sub-item">My Elections</span>
                                </a>
                            </li>

                            {{-- OPTIONAL: if you have a current election in session, you can show quick links --}}
                            {{-- Example: session('active_election_id') --}}
                            {{--
                            @if(session('active_election_id'))
                                <li class="{{ request()->routeIs('officer.elections.positions.*') ? 'active' : '' }}">
                                    <a href="{{ route('officer.elections.positions.index', session('active_election_id')) }}">
                                        <span class="sub-item">Positions</span>
                                    </a>
                                </li>

                                <li class="{{ request()->routeIs('officer.elections.candidates.*') ? 'active' : '' }}">
                                    <a href="{{ route('officer.elections.candidates.index', session('active_election_id')) }}">
                                        <span class="sub-item">Candidates</span>
                                    </a>
                                </li>
                            @endif
                            --}}

                        </ul>
                    </div>
                </li>

                {{-- Results --}}
                <li class="nav-item {{ request()->routeIs('officer.results.*') ? 'active' : '' }}">
                    <a href="{{ route('officer.results.index') }}">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <p>Results</p>
                    </a>
                </li>

                {{-- Vote (Officer as a student) --}}
            <li class="nav-item {{ request()->routeIs('stu.vote.*') ? 'active' : '' }}">
                <a href="{{ route('student.vote.index') }}">
                    <i class="bi bi-check2-square"></i>
                    <p>Vote</p>
                </a>
            </li>


                {{-- Divider --}}
                <li class="nav-item mt-3">
                    <hr class="text-white opacity-25">
                </li>

                {{-- Officer Account (minimal) --}}
                <li class="nav-item">
                    <a href="#" class="d-flex align-items-center">
                        <i class="bi bi-person-badge"></i>
                        <p class="mb-0">
                            {{ $student?->first_name }} {{ $student?->last_name }}
                            <span class="d-block small text-muted">{{ $student?->reg_no }}</span>
                        </p>
                    </a>
                </li>

                {{-- Logout --}}
                <li class="nav-item">
                    <a href="#"
                       onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
                        <i class="bi bi-box-arrow-right"></i>
                        <p>Logout</p>
                    </a>

                    <form id="logoutForm" action="{{ route('stu.logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>

            </ul>
        </div>
    </div>
</div>

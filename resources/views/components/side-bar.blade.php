{{-- <!-- Sidebar Start --> --}}
<style>
    i {
        font-size: 20px;
    }
</style>

<aside class="left-sidebar">
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="{{ route('dashboard') }}" class="text-nowrap logo-img">
                <img src="{{ asset('assets/images/logos/logo.png') }}" width="180" alt="Logo">
            </a>

            <div
                class="close-btn d-xl-none d-block sidebartoggler cursor-pointer"
                id="sidebarCollapse"
            >
                <i class="ti ti-x fs-8"></i>
            </div>
        </div>

        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
            <ul id="sidebarnav">

                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Navigation</span>
                </li>

                {{-- Dashboard --}}
                <li class="sidebar-item">
                    <a
                        class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}"
                        aria-expanded="false"
                    >
                        <span>
                            <i class="ti ti-layout-dashboard"></i>
                        </span>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>

                {{-- Academic Year --}}
                <li class="sidebar-item">
                    <a
                        class="sidebar-link {{ request()->routeIs('academic-years.*') ? 'active' : '' }}"
                        href="{{ route('academic-years.index') }}"
                        aria-expanded="false"
                    >
                        <span>
                            <i class="ti ti-calendar-stats"></i>
                        </span>
                        <span class="hide-menu">Academic Years</span>
                    </a>
                </li>

                {{-- Academic Almanac --}}
                <li class="sidebar-item">
                    <a
                        class="sidebar-link {{ request()->routeIs('almanac.*') ? 'active' : '' }}"
                        href="{{ route('almanac.index') }}"
                        aria-expanded="false"
                    >
                        <span>
                            <i class="ti ti-calendar-event"></i>
                        </span>
                        <span class="hide-menu">Academic Almanac</span>
                    </a>
                </li>

                {{-- Map --}}
                <li class="sidebar-item">
                    <a
                        class="sidebar-link {{ request()->routeIs('buildings.*') ? 'active' : '' }}"
                        href="{{ route('buildings.index') }}"
                        aria-expanded="false"
                    >
                        <span>
                            <i class="bi bi-map"></i>
                        </span>
                        <span class="hide-menu">Map Management</span>
                    </a>
                </li>

                {{-- Timetable --}}
                <li class="sidebar-item">
                    <a
                        class="sidebar-link {{ request()->routeIs('timetables.*') ? 'active' : '' }}"
                        href="{{ route('timetables.index') }}"
                        aria-expanded="false"
                    >
                        <span>
                            <i class="bi bi-table"></i>
                        </span>
                        <span class="hide-menu">Timetable Management</span>
                    </a>
                </li>

                {{-- Venue --}}
                <li class="sidebar-item">
                    <a
                        class="sidebar-link {{ request()->routeIs('venues.*') ? 'active' : '' }}"
                        href="{{ route('venues.index') }}"
                        aria-expanded="false"
                    >
                        <span>
                            <i class="bi bi-building"></i>
                        </span>
                        <span class="hide-menu">Venue Management</span>
                    </a>
                </li>

                {{-- Programs --}}
                <li class="sidebar-item">
                    <a
                        class="sidebar-link {{ request()->routeIs('programs.*') ? 'active' : '' }}"
                        href="{{ route('programs.index') }}"
                        aria-expanded="false"
                    >
                        <span>
                            <i class="ti ti-file-description"></i>
                        </span>
                        <span class="hide-menu">Programs Management</span>
                    </a>
                </li>

                {{-- Courses --}}
                <li class="sidebar-item">
                    <a
                        class="sidebar-link {{ request()->routeIs('courses.*') ? 'active' : '' }}"
                        href="{{ route('courses.index') }}"
                        aria-expanded="false"
                    >
                        <span>
                            <i class="ti ti-book"></i>
                        </span>
                        <span class="hide-menu">Course Management</span>
                    </a>
                </li>

                {{-- Users --}}
                <li class="sidebar-item">
                    <a
                        class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                        href="{{ route('users.index') }}"
                        aria-expanded="false"
                    >
                        <span>
                            <i class="bi bi-people"></i>
                        </span>
                        <span class="hide-menu">User Management</span>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>
{{-- <!-- Sidebar End --> --}}
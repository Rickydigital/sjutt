{{-- <!-- Sidebar Start --> --}}
<style>
    i {
        font-size: 20px;
    }
</style>
<aside class="left-sidebar">
    {{-- <!-- Sidebar scroll--> --}}
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="{{ route('dashboard') }}" class="text-nowrap logo-img">
                <img src="{{asset('assets/images/logos/logo.png')}}" width="180" alt="" />
            </a>
            <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
                <i class="ti ti-x fs-8"></i>
            </div>
        </div>
        {{-- <!-- Sidebar navigation--> --}}
        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
            <ul id="sidebarnav">
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Navigation</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('dashboard') }}" aria-expanded="false">
                        <span>
                            <i class="ti ti-layout-dashboard"></i>
                        </span>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('buildings.index') }}" aria-expanded="false">
                        <span>
                            <i class="bi bi-map"></i>
                        </span>
                        <span class="hide-menu">Map Management</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('timetables.index') }}" aria-expanded="false">
                        <span>
                            <i class="bi bi-table"></i>
                        </span>
                        <span class="hide-menu">Timetable Management</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('venues.index') }}" aria-expanded="false">
                        <span>
                            <i class="bi bi-building"></i>
                        </span>
                        <span class="hide-menu">Venue Management</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('programs.index') }}" aria-expanded="false">
                        <span>
                            <i class="ti ti-file-description"></i>
                        </span>
                        <span class="hide-menu">Programs Management</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('courses.index') }}" aria-expanded="false">
                        <span>
                            <i class="ti ti-file-description"></i>
                        </span>
                        <span class="hide-menu">Course Management</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('users.index') }}" aria-expanded="false">
                        <span>
                            <i class="bi bi-people "></i>
                        </span>
                        <span class="hide-menu">User Management </span>
                    </a>
                </li>
            </ul>

        </nav>
        {{-- <!-- End Sidebar navigation --> --}}
    </div>
    {{-- <!-- End Sidebar scroll--> --}}
</aside>
{{-- <!--  Sidebar End --> --}}

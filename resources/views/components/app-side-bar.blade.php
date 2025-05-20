{{-- <!-- Sidebar --> --}}
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        {{-- <!-- Logo Header --> --}}
        <div class="logo-header" data-background-color="dark">
            <a href="{{ route('dashboard') }}" class="logo">
                <img src="{{ asset('app-assets/img/kaiadmin/logo_light.svg') }}" alt="navbar brand" class="navbar-brand"
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
        {{-- <!-- End Logo Header --> --}}
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">

                {{-- Dashboard --}}
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                {{-- users --}}
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#users">
                        <i class="bi bi-people-fill "></i>
                        <p>Users Management</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="users">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('users.index') }}">
                                    <span class="sub-item">Staff</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('users.index') }}">
                                    <span class="sub-item">Students</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Structures --}}
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#structures">
                        <i class="bi bi-buildings-fill"></i>
                        <p>Structures</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="structures">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('buildings.index') }}">
                                    <span class="sub-item">Buildings</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('venues.index') }}">
                                    <span class="sub-item">Venues</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Academics --}}
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#academics">
                        <i class="bi bi-stack"></i>
                        <p>Academics</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="academics">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('programs.index') }}">
                                    <span class="sub-item">Programs</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('faculties.index') }}">
                                    <span class="sub-item">Faculties</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('courses.index') }}">
                                    <span class="sub-item">Courses</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Community --}}
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#community">
                        <i class="bi bi-person-arms-up"></i>
                        <p>Community</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="community">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="#">
                                    <span class="sub-item">Talents</span>
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <span class="sub-item">Top talents</span>
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <span class="sub-item">Flagged talents</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- News and Events --}}
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#news_events">
                        <i class="bi bi-newspaper"></i>
                        <p>News & Events</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="news_events">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('news.index') }}">
                                    <span class="sub-item">News</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('events.index') }}">
                                    <span class="sub-item">Events</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('gallery.index') }}">
                                    <span class="sub-item">Gallery</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Timetables --}}
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#timetables">
                        <i class="bi bi-table"></i>
                        <p>Timetables & Calendar</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="timetables">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('timetable.index') }}">
                                    <span class="sub-item">Lecture Timetable</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('timetables.index') }}">
                                    <span class="sub-item">Examination Timetable</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('calendar.index') }}">
                                    <span class="sub-item">University Calendar</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- others --}}
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#sjut">
                        <i class="far fa-chart-bar"></i>
                        <p>SJUT</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="sjut">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('fee_structures.index') }}">
                                    <span class="sub-item">Fee Structure</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.suggestions.index') }}">
                                    <span class="sub-item">Suggestion Box</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('years.index') }}">
                                    <span class="sub-item">Year of Study</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>
{{-- <!-- End Sidebar --> --}}

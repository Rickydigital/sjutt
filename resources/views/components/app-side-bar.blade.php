{{-- Sidebar --}}
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        {{-- Logo Header --}}
        <div class="logo-header" data-background-color="dark">
            <a href="{{ route('dashboard') }}" class="logo">
                <img src="{{ asset('app-assets/img/kaiadmin/logo_light.svg') }}" alt="navbar brand" class="navbar-brand" height="20" />
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

                {{-- User Management --}}
                @canany(['view users', 'view students'])
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#users">
                        <i class="bi bi-people-fill"></i>
                        <p>Users Management</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="users">
                        <ul class="nav nav-collapse">
                            @can('view users')
                            <li>
                                <a href="{{ route('users.index') }}">
                                    <span class="sub-item">Staff</span>
                                </a>
                            </li>
                            @endcan
                            @can('view students')
                            <li>
                                <a href="{{ route('students.index') }}">
                                    <span class="sub-item">Students</span>
                                </a>
                            </li>
                            @endcan

                            <li>
                                <a  href="{{ route('user.sessions.index') }}">
                                    <span class="sub-item">Lecturer Sessions</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Structures--}}
                @canany(['view buildings', 'view venues'])
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#structures">
                        <i class="bi bi-buildings-fill"></i>
                        <p>Structures</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="structures">
                        <ul class="nav nav-collapse">
                            @can('view buildings')
                            <li>
                                <a href="{{ route('buildings.index') }}">
                                    <span class="sub-item">Buildings</span>
                                </a>
                            </li>
                            @endcan
                            @can('view venues')
                            <li>
                                <a href="{{ route('venues.index') }}">
                                    <span class="sub-item">Venues</span>
                                </a>
                            </li>

                            <li>
                                <a  href="{{ route('venue.sessions.index') }}">
                                    <span class="sub-item">Venue Sessions</span>
                                </a>
                            </li>

                            {{--  <li>
                                <a  href="{{ route('venues.summary') }}">
                                    <span class="sub-item">Venue Summary</span>
                                </a>
                            </li>  --}}

                            <li>
                                <a  href="{{ route('venues.timetable') }}">
                                    <span class="sub-item">Venue Summary</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Academics  --}}
                @canany(['view programs', 'view faculties', 'view courses'])
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#academics">
                        <i class="bi bi-stack"></i>
                        <p>Academics</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="academics">
                        <ul class="nav nav-collapse">
                            @can('view programs')
                            <li>
                                <a href="{{ route('programs.index') }}">
                                    <span class="sub-item">Programs</span>
                                </a>
                            </li>
                            @endcan
                            @can('view faculties')
                            <li>
                                <a href="{{ route('faculties.index') }}">
                                    <span class="sub-item">Classes</span>
                                </a>
                            </li>
                            @endcan
                            @can('view courses')
                            <li>
                                <a href="{{ route('courses.index') }}">
                                    <span class="sub-item">Courses</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Community --}}
                {{-- @can('view talents')
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#community">
                        <i class="bi bi-person-arms-up"></i>
                        <p>Community</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="community">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="">
                                    <span class="sub-item">Talents</span>
                                </a>
                            </li>
                            <li>
                                <a href="">
                                    <span class="sub-item">Top Talents</span>
                                </a>
                            </li>
                            <li>
                                <a href="">
                                    <span class="sub-item">Flagged Talents</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcan --}}

                {{-- News and Events --}}
                @canany(['view news', 'view events', 'view gallery'])
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#news_events">
                        <i class="bi bi-newspaper"></i>
                        <p>News & Events</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="news_events">
                        <ul class="nav nav-collapse">
                            @can('view news')
                            <li>
                                <a href="{{ route('news.index') }}">
                                    <span class="sub-item">News</span>
                                </a>
                            </li>
                            @endcan
                            @can('view events')
                            <li>
                                <a href="{{ route('events.index') }}">
                                    <span class="sub-item">Events</span>
                                </a>
                            </li>
                            @endcan
                            @can('view gallery')
                            <li>
                                <a href="{{ route('gallery.index') }}">
                                    <span class="sub-item">Gallery</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Timetables & Calendar--}}
                @canany(['view timetables', 'view examination timetables', 'view calendar'])
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#timetables">
                        <i class="bi bi-table"></i>
                        <p>Timetables & Calendar</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="timetables">
                        <ul class="nav nav-collapse">
                            @can('view timetables')
                            <li>
                                <a href="{{ route('timetable.index') }}">
                                    <span class="sub-item">Lecture Timetable</span>
                                </a>
                            </li>
                            @endcan
                            @can('view examination timetables')
                            <li>
                                <a href="{{ route('timetables.index') }}">
                                    <span class="sub-item">Examination Timetable</span>
                                </a>
                            </li>
                            @endcan
                            @can('view calendar')
                            <li>
                                <a href="{{ route('calendar.index') }}">
                                    <span class="sub-item">University Calendar</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Attendance --}}
                @can('view attendance')
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#attendance">
                        <i class="bi bi-check-square"></i>
                        <p>Attendance</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="attendance">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="">
                                    <span class="sub-item">Students Attendance</span>
                                </a>
                            </li>
                            <li>
                                <a href="">
                                    <span class="sub-item">Lecturers Attendance</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcan

                {{-- Suggestion Box--}}
                @can('view suggestions')
                <li class="nav-item">
                    <a href="{{ route('admin.suggestions.index') }}">
                        <i class="bi bi-envelope"></i>
                        <p>Suggestion Box</p>
                    </a>
                </li>
                @endcan

                {{-- Fee Structure --}}
                @can('view fee structures')
                <li class="nav-item">
                    <a href="{{ route('fee_structures.index') }}">
                        <i class="bi bi-currency-dollar"></i>
                        <p>Fee Structure</p>
                    </a>
                </li>
                @endcan

                 {{-- Roles & Permissions (Admin Only) --}}
                @hasrole('Admin')
                <li class="nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <a href="{{ route('roles.index') }}">
                        <i class="bi bi-shield-lock"></i>
                        <p>Roles & Permissions</p>
                    </a>
                </li>
                @endhasrole
            </ul>

           
        </div>
    </div>
</div>
{{-- End Sidebar --}}
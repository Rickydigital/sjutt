<aside id="left-panel" class="left-panel">
    <nav class="navbar navbar-expand-sm navbar-default">
        <div id="main-menu" class="main-menu collapse navbar-collapse">
            <ul class="nav navbar-nav">
                <li class="active">
                    <a href="{{ route('dashboard') }}"><i class="menu-icon fas fa-laptop"></i>Dashboard </a>
                </li>
                <li class="menu-item-has-children dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="menu-icon fas fa-users"></i>Users</a>
                    <ul class="sub-menu children dropdown-menu">
                        <li><i class="fas fa-table"></i><a href="{{ route('users.index') }}">Staff</a></li>
                        <li><i class="fas fa-table"></i><a href="{{ route('users.index') }}">Students</a></li>
                    </ul>
                </li>
                <li class="menu-item-has-children dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="menu-icon fas fa-th"></i>Structures</a>
                    <ul class="sub-menu children dropdown-menu">
                        <li><i class="menu-icon fas fa-th"></i><a href="{{ route('programs.index') }}">Programs</a></li>
                        <li><i class="menu-icon fas fa-th"></i><a href="{{ route('faculties.index') }}">Faculties</a></li>
                        <li><i class="menu-icon fas fa-th"></i><a href="{{ route('buildings.index') }}">Buildings</a></li>
                        <li><i class="menu-icon fas fa-th"></i><a href="{{ route('venues.index') }}">Venues</a></li>
                    </ul>
                </li>
                <li class="menu-item-has-children dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="menu-icon fas fa-tasks"></i>Community</a>
                    <ul class="sub-menu children dropdown-menu">
                        <li><i class="menu-icon fab fa-fort-awesome"></i><a href="font-fontawesome.html">Talents</a></li>
                        <li><i class="menu-icon ti-themify-logo"></i><a href="font-themify.html">Top talents</a></li>
                        <li><i class="menu-icon ti-themify-logo"></i><a href="font-themify.html">Flagged talents</a></li>
                    </ul>
                </li>
                <li class="menu-item-has-children dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="menu-icon fas fa-bullhorn"></i>News & Events</a>
                    <ul class="sub-menu children dropdown-menu">
                        <li><i class="menu-icon fas fa-newspaper"></i><a href="{{ route('news.index') }}">News</a></li>
                        <li><i class="menu-icon fas fa-calendar-alt"></i><a href="{{ route('events.index') }}">Events</a></li>
                        <li><i class="menu-icon fas fa-images"></i><a href="{{ route('gallery.index') }}">Gallery</a></li>
                    </ul>
                </li>
                <li class="menu-item-has-children dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="menu-icon fas fa-calendar"></i>Timetables</a>
                    <ul class="sub-menu children dropdown-menu">
                        <li><i class="menu-icon fas fa-calendar"></i><a href="{{ route('admin.calendars.index') }}">Calendar</a></li>
                        <li><i class="menu-icon fas fa-clock"></i><a href="{{ route('timetable.index') }}">Lecture Timetable</a></li>
                        <li><i class="menu-icon fas fa-file-alt"></i><a href="{{ route('timetables.index') }}">Examination Timetable</a></li>
                    </ul>
                </li>
                <li class="menu-item-has-children dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="menu-icon fas fa-university"></i>Sjut Commun</a>
                    <ul class="sub-menu children dropdown-menu">
                        <li><i class="menu-icon fas fa-book"></i><a href="{{ route('courses.index') }}">Courses</a></li>
                        <li><i class="menu-icon fas fa-money-bill"></i><a href="{{ route('fee_structures.index') }}">Fee Structure</a></li>
                        <li><i class="menu-icon fas fa-comment"></i><a href="{{ route('admin.suggestions.index') }}">Suggestion Box</a></li>
                        <li><i class="menu-icon fas fa-graduation-cap"></i><a href="{{ route('years.index') }}">Years Of Study</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</aside>
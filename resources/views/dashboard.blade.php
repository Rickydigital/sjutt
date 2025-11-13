@extends('components.app-main-layout')
@section('content')
    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        <div>
            <h3 class="fw-bold mb-3">Dashboard</h3>
        </div>
        <div class="ms-md-auto py-2 py-md-0">
            @if (auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Timetable Officer'))
                <a href="{{ route('timetable.index') }}" class="btn btn-label-info btn-round me-2">Manage Timetable</a>
            @endif
            @if (auth()->user()->hasRole('Admin'))
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-round">Add User</a>
            @endif
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
                                <h4 class="card-title">{{ $buildingCount }}</h4>
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
                                <h4 class="card-title">{{ $venueCount }}</h4>
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
                                <h4 class="card-title">{{ $programCount }}</h4>
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
                                <h4 class="card-title">{{ $courseCount }}</h4>
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
                                    <a class="dropdown-item" href="#">View All</a>
                                    @if (auth()->user()->hasRole('Admin'))
                                        <a class="dropdown-item" href="{{ route('users.create') }}">Add Lecturer</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-list py-4">
                        @foreach ($lecturers as $lecturer)
                            <div class="item-list">
                                <div class="avatar">
                                    <span class="avatar-title rounded-circle border border-white bg-{{ ['primary', 'secondary', 'success', 'info'][rand(0, 3)] }}">
                                        {{ strtoupper(substr($lecturer->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="info-user ms-3">
                                    <div class="username">{{ $lecturer->name }}</div>
                                </div>
                                <button class="btn btn-icon btn-link op-8 me-1">
                                    <i class="far fa-envelope"></i>
                                </button>
                                @if (auth()->user()->hasRole('Admin'))
                                    <button class="btn btn-icon btn-link btn-danger op-8">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">
                            @if (auth()->user()->hasRole('Lecturer'))
                                Today's Sessions & Weekly Timetable
                            @elseif (auth()->user()->hasRole('Administrator'))
                                Program Lecturer Timetable
                            @else
                                Timetable Overview
                            @endif
                        </div>
                        <div class="card-tools">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-clean me-0" type="button" id="dropdownMenuButton"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <a class="dropdown-item" href="{{ route('timetable.index') }}">View Full Timetable</a>
                                    @if (auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Timetable Officer'))
                                        <a class="dropdown-item" href="{{ route('timetable.generate') }}">Generate Timetable</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead class="thead-light">
                                <tr>
                                    @if (auth()->user()->hasRole('Lecturer'))
                                        <th scope="col">Course</th>
                                        <th scope="col" class="text-end">Day</th>
                                        <th scope="col" class="text-end">Time</th>
                                        <th scope="col" class="text-end">Venue</th>
                                        <th scope="col" class="text-end">Faculty</th>
                                        <th scope="col" class="text-end">Group</th>
                                        <th scope="col" class="text-end">Activity</th>
                                    @elseif (auth()->user()->hasRole('Administrator'))
                                        <th scope="col">Lecturer</th>
                                        <th scope="col" class="text-end">Course</th>
                                        <th scope="col" class="text-end">Day</th>
                                        <th scope="col" class="text-end">Time</th>
                                        <th scope="col" class="text-end">Venue</th>
                                        <th scope="col" class="text-end">Faculty</th>
                                        <th scope="col" class="text-end">Group</th>
                                    @else
                                        <th scope="col">Course</th>
                                        <th scope="col" class="text-end">Lecturer</th>
                                        <th scope="col" class="text-end">Day</th>
                                        <th scope="col" class="text-end">Time</th>
                                        <th scope="col" class="text-end">Venue</th>
                                        <th scope="col" class="text-end">Faculty</th>
                                        <th scope="col" class="text-end">Group</th>

                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if (auth()->user()->hasRole('Lecturer'))
                                    <!-- Daily Sessions for Lecturer -->
                                    @if ($todaySessions->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center">No sessions scheduled for today.</td>
                                        </tr>
                                    @else
                                        @foreach ($todaySessions as $session)
                                            <tr>
                                                <th scope="row">{{ $session->course_code }} - {{ $session->course_name }}</th>
                                                <td class="text-end">{{ $session->day }}</td>
                                                <td class="text-end">{{ $session->time_start }} - {{ $session->time_end }}</td>
                                                <td class="text-end">{{ $session->venue->name }}</td>
                                                <td class="text-end">{{ $session->faculty->name }}</td>
                                                <td class="text-end">{{ $session->group_selection }}</td>
                                                <td class="text-end">{{ $session->activity }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    <!-- Weekly Timetable for Lecturer -->
                                    @if ($weeklySessions->isNotEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center"><strong>Weekly Timetable</strong></td>
                                        </tr>
                                        @foreach ($weeklySessions as $session)
                                            <tr>
                                                <th scope="row">{{ $session->course_code }} - {{ $session->course_name }}</th>
                                                <td class="text-end">{{ $session->day }}</td>
                                                <td class="text-end">{{ $session->time_start }} - {{ $session->time_end }}</td>
                                                <td class="text-end">{{ $session->venue->name }}</td>
                                                <td class="text-end">{{ $session->faculty->name }}</td>
                                                <td class="text-end">{{ $session->group_selection }}</td>
                                                <td class="text-end">{{ $session->activity }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @elseif (auth()->user()->hasRole('Administrator'))
                                    @if ($programSessions->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center">No sessions scheduled for your program.</td>
                                        </tr>
                                    @else
                                        @foreach ($programSessions as $session)
                                            <tr>
                                                <th scope="row">{{ $session->lecturer->name }}</th>
                                                <td class="text-end">{{ $session->course_code }} - {{ $session->course_name }}</td>
                                                <td class="text-end">{{ $session->day }}</td>
                                                <td class="text-end">{{ $session->time_start }} - {{ $session->time_end }}</td>
                                                <td class="text-end">{{ $session->venue->name }}</td>
                                                <td class="text-end">{{ $session->faculty->name }}</td>
                                                <td class="text-end">{{ $session->group_selection }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @else
                                    @if ($allSessions->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center">No sessions scheduled.</td>
                                        </tr>
                                    @else
                                        @foreach ($allSessions as $session)
                                            <tr>
                                                <th scope="row">{{ $session->course_code }} - {{ $session->course_name }}</th>
                                                <td class="text-end">{{ $session->lecturer->name }}</td>
                                                <td class="text-end">{{ $session->day }}</td>
                                                <td class="text-end">{{ $session->time_start }} - {{ $session->time_end }}</td>
                                                <td class="text-end">{{ $session->venue->name }}</td>
                                                <td class="text-end">{{ $session->faculty->name }}</td>
                                                <td class="text-end">{{ $session->group_selection }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
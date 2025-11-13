@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            Sessions in <strong>{{ $venue->longform }}</strong> ({{ $venue->name }})
        </h5>
        <div>
            <a href="{{ route('venue.sessions.pdf', $venue) }}" class="btn btn-sm btn-success">
                Download PDF
            </a>
            <a href="{{ route('venue.sessions.index') }}" class="btn btn-sm btn-outline-secondary">
                Back
            </a>
        </div>
    </div>

    <div class="card-body">
        @if($slots->isEmpty())
            <div class="alert alert-info text-center">
                No sessions booked for this venue.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-secondary">
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Courses</th>
                            <th>Faculty</th>
                            <th>Lecturer(s)</th>
                            <th>Groups</th>
                            <th>Activity</th>
                            <th>Sessions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slots as $slot)
                        <tr>
                            <td>{{ $slot['day'] }}</td>
                            <td>{{ $slot['start'] }} – {{ $slot['end'] }}</td>
                            <td>
                                @foreach($slot['courses'] as $code)
                                    <span class="badge bg-primary me-1">{{ $code }}</span>
                                @endforeach
                            </td>
                            <td>{{ $slot['faculty'] ?: '—' }}</td>
                            <td>
                                @php
                                    $names = \App\Models\User::whereIn('id', $slot['lecturers'])
                                        ->pluck('name')
                                        ->implode(', ');
                                @endphp
                                {{ $names ?: '—' }}
                            </td>
                            <td>{{ $slot['groups'] }}</td>
                            <td>{{ $slot['activity'] }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $slot['count'] > 1 ? 'warning' : 'success' }}">
                                    {{ $slot['count'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
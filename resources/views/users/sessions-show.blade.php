{{-- resources/views/users/sessions-show.blade.php --}}
@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">
            Schedule for <strong>{{ $user->name }}</strong>
            @if($user->roles->count())
                <small class="text-muted">({{ $user->roles->pluck('name')->implode(', ') }})</small>
            @endif
        </h5>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('user.sessions.pdf', ['user' => $user->id, 'setup_id' => $selectedSetupId]) }}" class="btn btn-sm btn-success">
                Download PDF
            </a>
            <a href="{{ route('user.sessions.index', ['setup_id' => $selectedSetupId]) }}" class="btn btn-sm btn-outline-secondary">
                Back
            </a>
        </div>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('user.sessions.show', $user->id) }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <label for="setup_id" class="form-label">Setup</label>
                    <select name="setup_id" id="setup_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Select setup</option>
                        @foreach($timetableSemesters as $setup)
                            <option value="{{ $setup->id }}" {{ (string) $selectedSetupId === (string) $setup->id ? 'selected' : '' }}>
                                {{ $setup->semester?->name ?? 'N/A' }} - {{ $setup->academic_year }}
                                @if(!empty($setup->is_current)) (Current) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <div class="mb-3">
            <span class="badge bg-info">
                Setup:
                {{ $selectedSetup?->semester?->name ?? 'N/A' }}
                ({{ $selectedSetup?->academic_year ?? 'N/A' }})
            </span>
        </div>

        @if($slots->isEmpty())
            <div class="alert alert-info text-center">
                No sessions scheduled for this lecturer in the selected setup.
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
                            <th>Venue</th>
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
                                    <strong>{{ $slot['venue_code'] }}</strong><br>
                                    <small>{{ $slot['venue'] }}</small>
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
{{-- resources/views/timetable/venues.blade.php --}}
@extends('components.app-main-layout')

@section('content')
<style>
    :root {
        --purple: #6f42c1;
        --purple-dark: #563d7c;
        --purple-light: #e2d9f3;
    }

    .card-header {
        background: linear-gradient(135deg, var(--purple), var(--purple-dark)) !important;
        color: #fff !important;
    }

    .btn-purple {
        background: var(--purple);
        border: none;
        color: #fff;
    }

    .btn-purple:hover {
        background: var(--purple-dark);
        color: #fff;
    }

    .btn-outline-purple {
        color: var(--purple);
        border: 1px solid var(--purple);
        background: #fff;
    }

    .btn-outline-purple:hover {
        background: var(--purple);
        color: #fff;
    }

    .text-purple {
        color: var(--purple) !important;
    }

    .timetable-table th {
        background: linear-gradient(135deg, var(--purple), var(--purple-dark));
        color: #fff;
        font-weight: 600;
        vertical-align: middle;
        text-align: center;
    }

    .timetable-table td {
        border: 2px solid #dee2e6 !important;
        vertical-align: top;
    }

    .course-cell {
        background: var(--purple-light);
        border-left: 4px solid var(--purple);
        padding: 10px;
        border-radius: 6px;
        font-size: 0.9rem;
        margin: 4px 0;
        box-shadow: 0 2px 5px rgba(111, 66, 193, 0.10);
    }

    .empty-slot {
        color: #888;
        font-style: italic;
        padding-top: 8px;
    }

    .venue-title {
        font-size: 2rem;
        font-weight: 800;
        color: var(--purple);
        text-shadow: 2px 2px 4px rgba(0,0,0,0.08);
    }

    .filter-card-label {
        font-weight: 700;
        color: var(--purple);
        margin-bottom: 6px;
    }

    .select2-container--classic .select2-selection--single {
        height: 44px !important;
        padding-top: 7px;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
    }

    .select2-container--classic .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="venue-title mb-0">
                <i class="fas fa-building me-3"></i> Venue Timetables
            </h1>

            <a href="{{ route('timetable.index', ['setup_id' => $selectedSetupId]) }}" class="btn btn-outline-purple">
                <i class="fas fa-users me-1"></i> Faculty View
            </a>
        </div>
    </div>

    @if($error)
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ $error }}
        </div>
    @endif

    <div class="card shadow-lg border-0 mb-5">
        <div class="card-header">
            <h4 class="mb-0">
                <i class="fas fa-filter me-1"></i> Select Setup and Venue
            </h4>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('venues.timetable') }}" id="venueForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="setup_id" class="form-label filter-card-label">Choose Setup</label>
                        <select name="setup_id" id="setup_id" class="form-control select2" required>
                            <option value="">-- Select Setup --</option>
                            @foreach($timetableSemesters as $setup)
                                <option value="{{ $setup->id }}" {{ (string) $selectedSetupId === (string) $setup->id ? 'selected' : '' }}>
                                    {{ $setup->semester?->name ?? 'N/A' }} - {{ $setup->academic_year }}
                                    {{ !empty($setup->is_current) ? '(Current)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label for="venue" class="form-label filter-card-label">Choose Venue</label>
                        <select name="venue" id="venue" class="form-control select2" required>
                            <option value="">-- Select a Venue --</option>
                            @foreach($venues as $v)
                                <option value="{{ $v->id }}" {{ (string) $venueId === (string) $v->id ? 'selected' : '' }}>
                                    {{ $v->name }} (Capacity: {{ $v->capacity }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-purple btn-lg w-100">
                            <i class="fas fa-eye me-1"></i> View Timetable
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($selectedVenue && !$error && $timetableSemester)
        <div class="card shadow-lg border-0">
            <div class="card-header text-center">
                <h2 class="mb-0 text-white">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    {{ $selectedVenue->name }}
                </h2>
                <p class="mb-0 mt-2">
                    Capacity: <strong>{{ $selectedVenue->capacity }}</strong> |
                    Setup: <strong>{{ $timetableSemester->semester?->name ?? 'N/A' }} ({{ $timetableSemester->academic_year }})</strong>
                </p>
            </div>

            <div class="card-body p-0">
                <div class="p-3 bg-light border-bottom d-flex justify-content-end">
                    <a href="{{ route('venues.timetable.export', ['venue' => $venueId, 'setup_id' => $selectedSetupId]) }}"
                       class="btn btn-success btn-sm">
                        <i class="fas fa-file-pdf me-1"></i> Export PDF
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table timetable-table mb-0">
                        <thead>
                            <tr>
                                <th width="130">Time</th>
                                @foreach($days as $day)
                                    <th>{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $activitiesByDay = $timetables->groupBy('day')->map->sortBy('time_start');
                                $occupiedUntil = array_fill_keys($days, -1);
                            @endphp

                            @foreach($timeSlots as $i => $slot)
                                <tr>
                                    <td class="fw-bold text-purple">
                                        {{ $slot }} - {{ date('H:i', strtotime($slot) + 3600) }}
                                    </td>

                                    @foreach($days as $day)
                                        @if($i > $occupiedUntil[$day])
                                            @php
                                                $activities = $activitiesByDay->get($day, collect())
                                                    ->filter(fn($t) => substr($t->time_start, 0, 5) === $slot);

                                                $maxHours = $activities->max(fn($t) =>
                                                    (strtotime($t->time_end) - strtotime($t->time_start)) / 3600
                                                ) ?? 1;

                                                $rowspan = max(1, (int) ceil($maxHours));
                                                $occupiedUntil[$day] = $i + $rowspan - 1;
                                            @endphp

                                            <td rowspan="{{ $rowspan }}" class="p-3 align-top">
                                                @if($activities->count() > 0)
                                                    @foreach($activities as $act)
                                                        <div class="course-cell">
                                                            <strong>{{ $act->course_code }}</strong><br>
                                                            <small>
                                                                <strong>{{ $act->faculty->name ?? 'N/A' }}</strong><br>
                                                                Groups: {{ $act->group_selection }}<br>
                                                                Lecturer: {{ $act->lecturer->name ?? 'TBA' }}<br>
                                                                <em class="text-purple">{{ $act->activity }}</em>
                                                            </small>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="empty-slot">Available</div>
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({
            theme: 'classic',
            width: '100%',
            placeholder: 'Select an option'
        });

        $('#setup_id, #venue').on('change', function () {
            const setupId = $('#setup_id').val();
            const venueId = $('#venue').val();

            if (setupId && venueId) {
                $('#venueForm').submit();
            }
        });
    });
</script>
@endsection
@php use Carbon\Carbon; @endphp

@extends('components.app-main-layout')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center text-white"
         style="background:linear-gradient(135deg,#6f42c1,#4B2E83);">
        <div>
            <h5 class="mb-1">Examination Venue Usage</h5>
            <small>{{ $setup->semester->name ?? 'Semester' }} — {{ $setup->academic_year }}</small>
        </div>
        <a href="{{ route('timetables.index', ['setup_id' => $setup->id]) }}"
           class="btn btn-sm btn-outline-light">Back to Examination Timetable</a>
    </div>

    <div class="card-body">
        @if($venues->isEmpty())
            <div class="alert alert-info mb-0">No examination venue bookings exist for this setup.</div>
        @else
            @foreach($dateChunks as $chunkDays)
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle venue-usage-table mb-0">
                        <thead>
                            <tr>
                                <th rowspan="2" class="time-column">Session</th>
                                @foreach($chunkDays as $day)
                                    <th colspan="{{ $venues->count() }}">
                                        {{ Carbon::parse($day)->format('D, d M Y') }}
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($chunkDays as $day)
                                    @foreach($venues as $venue)
                                        <th class="venue-column">
                                            {{ $venue->name }}
                                            <small>Capacity: {{ $venue->capacity }}</small>
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timeSlots as $slot)
                                <tr>
                                    <td class="time-column">
                                        <strong>{{ $slot['name'] }}</strong><br>
                                        <small>{{ $slot['start_time'] }}–{{ $slot['end_time'] }}</small>
                                    </td>
                                    @foreach($chunkDays as $day)
                                        @foreach($venues as $venue)
                                            @php($items = $grid[$day][$slot['start_time']][$venue->id] ?? [])
                                            <td class="{{ empty($items) ? 'free-cell' : 'booked-cell' }}">
                                                @forelse($items as $item)
                                                    <div class="booking {{ !$loop->last ? 'border-bottom pb-1 mb-1' : '' }}">
                                                        <strong>{{ $item['course_code'] }}</strong>
                                                        @if($item['faculty'])<div>{{ $item['faculty'] }}</div>@endif
                                                        <small>
                                                            {{ $item['program'] }}
                                                            @if($item['allocated_capacity'] > 0)
                                                                · {{ $item['allocated_capacity'] }} students
                                                            @endif
                                                        </small>
                                                    </div>
                                                @empty
                                                    <span>FREE</span>
                                                @endforelse
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endif
    </div>
</div>

<style>
    .venue-usage-table { min-width: 1100px; font-size: .78rem; }
    .venue-usage-table th { background:#5b3aa6; color:#fff; text-align:center; vertical-align:middle; }
    .venue-usage-table td { min-width:125px; text-align:center; vertical-align:middle; }
    .venue-usage-table .time-column { min-width:130px; position:sticky; left:0; z-index:2; background:#4B2E83; color:#fff; }
    .venue-column small { display:block; font-weight:normal; }
    .booked-cell { background:#eee7fb; color:#33215c; }
    .free-cell { background:#f2fbf5; color:#258044; font-style:italic; }
    .booking small { display:block; color:#665b75; }
</style>
@endsection

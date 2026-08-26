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
            <div class="alert alert-info mb-0">No venues are available.</div>
        @else
            <form method="GET" action="{{ route('timetables.venueUsage', $setup) }}" class="row align-items-end mb-4">
                <div class="col-md-7 col-lg-5">
                    <label for="venue_id" class="form-label fw-semibold">Select Venue</label>
                    <select name="venue_id" id="venue_id" class="form-control venue-select" required>
                        @foreach($venues as $venue)
                            <option value="{{ $venue->id }}" @selected($selectedVenue?->id === $venue->id)>
                                {{ $venue->name }}{{ $venue->longform ? ' — '.$venue->longform : '' }}
                                (Capacity: {{ $venue->capacity }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto mt-2 mt-md-0">
                    <button type="submit" class="btn text-white" style="background:#5b3aa6;">
                        <i class="fas fa-search me-1"></i> Show Usage
                    </button>
                </div>
            </form>

            <div class="selected-venue-heading mb-3">
                <strong>{{ $selectedVenue->name }}</strong>
                @if($selectedVenue->longform)
                    <span>— {{ $selectedVenue->longform }}</span>
                @endif
                <span class="badge bg-light text-dark ms-2">Capacity: {{ $selectedVenue->capacity }}</span>
            </div>

            @foreach($dateChunks as $chunkDays)
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle venue-usage-table mb-0">
                        <thead>
                            <tr>
                                <th class="time-column">Session</th>
                                @foreach($chunkDays as $day)
                                    <th>{{ Carbon::parse($day)->format('D, d M Y') }}</th>
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
                                        @php
                                            $items = $grid[$day][$slot['start_time']][$selectedVenue->id] ?? [];
                                        @endphp
                                        <td class="{{ empty($items) ? 'free-cell' : 'booked-cell' }}">
                                            @forelse($items as $item)
                                                <div class="booking {{ !$loop->last ? 'border-bottom pb-1 mb-1' : '' }}">
                                                    <span class="badge used-badge mb-1">USED</span>
                                                    <div><strong>{{ $item['course_code'] }}</strong></div>
                                                    @if($item['faculty'])<div>{{ $item['faculty'] }}</div>@endif
                                                    <small>
                                                        {{ $item['program'] }}
                                                        @if($item['allocated_capacity'] > 0)
                                                            · {{ $item['allocated_capacity'] }} students
                                                        @endif
                                                    </small>
                                                </div>
                                            @empty
                                                <span class="badge free-badge">FREE</span>
                                            @endforelse
                                        </td>
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
    .select2-container { width:100% !important; }
    .selected-venue-heading { padding:12px 15px; background:#f1ebfb; border-left:4px solid #5b3aa6; color:#33215c; }
    .venue-usage-table { width:100%; table-layout:fixed; font-size:.82rem; }
    .venue-usage-table th { background:#5b3aa6; color:#fff; text-align:center; vertical-align:middle; }
    .venue-usage-table td { text-align:center; vertical-align:middle; height:82px; }
    .venue-usage-table .time-column { width:145px; background:#4B2E83; color:#fff; }
    .booked-cell { background:#eee7fb; color:#33215c; }
    .free-cell { background:#f2fbf5; color:#258044; }
    .booking small { display:block; color:#665b75; }
    .used-badge { background:#5b3aa6; color:#fff; }
    .free-badge { background:#d9f4e1; color:#20733a; }
</style>
@endsection

@section('scripts')
<script>
    $(function () {
        $('.venue-select').select2({
            placeholder: 'Search and select a venue',
            width: '100%'
        }).on('change', function () {
            this.form.submit();
        });
    });
</script>
@endsection

@extends('components.app-main-layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center bg-purple text-white">
        <h5 class="mb-0">
            Venue Usage Summary
            @if($semester)
                <small class="ms-2">
                    — {{ $semester->name }} ({{ $academicYear ?? 'N/A' }})
                </small>
            @endif
        </h5>
        <div class="d-flex gap-2">
            {{--  <a href="{{ route('venues.summary.pdf') }}" class="btn btn-sm btn-danger">
                    Print
                </a>  --}}
            <a href="{{ route('venue.sessions.index') }}" class="btn btn-sm btn-outline-light">
                Back
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead style="background: #6f42c1; color: white;">
                    <tr>
                        <th rowspan="2" class="align-middle text-center" style="position: sticky; left:0; background:#6f42c1; color:white; z-index:10;">
                            <strong>Time</strong>
                        </th>
                        @foreach($days as $day)
                            <th colspan="{{ count($venues) }}" class="text-center">{{ $day }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($days as $day)
                            @foreach($venues as $venue)
                                <th class="text-center p-2" style="writing-mode: vertical-rl; text-orientation: mixed; min-width: 110px;">
                                    <div>
                                        <strong>{{ $venue->name }}</strong><br>
                                        <small>{{ $venue->longform }}</small><br>
                                        <small class="badge bg-light text-dark">{{ $venue->capacity }}</small>
                                    </div>
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($hours as $hour)
                        <tr>
                            <td class="fw-bold text-center bg-light" style="position: sticky; left:0; z-index:5;">
                                {{ substr($hour, 0, 5) }}
                            </td>
                            @foreach($days as $day)
                                @foreach($venues as $venue)
                                    @php
                                        $cell = $grid[$day][$hour][$venue->id] ?? null;
                                    @endphp
                                    @if($cell && $cell['isFirst'])
                                        <td class="text-center p-2 align-middle" rowspan="{{ $cell['rowspan'] }}" 
                                            style="background: #e7d4ff; font-size: 11px; min-height: 70px;">
                                            <div class="fw-bold text-purple">{{ $cell['content'] }}</div>
                                            <span class="badge bg-purple text-white">BOOKED</span>
                                        </td>
                                    @elseif(!$cell || !$cell['isFirst'])
                                        {{-- Skip if part of rowspan or empty --}}
                                        @continue
                                    @else
                                        <td class="text-center p-2" style="background: #f8f9fa; height: 70px;">
                                            <i class="text-success">FREE</i>
                                        </td>
                                    @endif
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-purple { background: #6f42c1 !important; }
    .text-purple { color: #6f42c1 !important; }
    @media print {
        body { font-size: 9pt; }
        .badge { font-size: 8pt; }
        .btn { display: none; }
    }
</style>
@endsection
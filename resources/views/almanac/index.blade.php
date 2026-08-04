@extends('components.app-main-layout')

@section('styles')
<style>
    .almanac-table { min-width: 1250px; font-size: .78rem; border-collapse: collapse; }
    .almanac-table th, .almanac-table td { border: 1px solid #555 !important; padding: 4px; vertical-align: middle; }
    .almanac-table th { background: #d9d9d9; text-align: center; }
    .almanac-table .month-cell { writing-mode: vertical-rl; transform: rotate(180deg); font-weight: 700; text-align: center; min-width: 34px; }
    .almanac-table .day-cell { white-space: nowrap; width: 72px; }
    .almanac-table .event-cell { min-width: 310px; }
    .almanac-table .week-end td { border-bottom-width: 2px !important; }
    .small-action { font-size: .75rem; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="fw-bold mb-1" style="color:#4B2E83">Academic Almanac</h1>
            <div class="text-muted">Setup → programme tracks → week blocks → events → PDF</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#setupModal">New Setup</button>
            @if($setup)
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#groupModal">Add Programme Group</button>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#weekBlockModal">Add Week Block</button>
                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#generateWeeksModal">Generate Weeks</button>
                <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#eventModal">Add Event</button>
                <a href="{{ route('almanac.pdf', $setup) }}" class="btn btn-success">Export PDF</a>
            @endif
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('almanac.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Select Almanac Setup</label>
                    <select name="setup_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Select...</option>
                        @foreach($setups as $item)
                            <option value="{{ $item->id }}" @selected($setup?->id === $item->id)>
                                {{ $item->title }} — {{ strtoupper($item->status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($setup)
                    <div class="col-md-6 d-flex flex-wrap gap-2">
                        @if($setup->status !== 'active')
                            <form method="POST" action="{{ route('almanac.setups.activate', $setup) }}">@csrf<button class="btn btn-success">Activate</button></form>
                        @endif
                        @if($setup->status !== 'archived')
                            <form method="POST" action="{{ route('almanac.setups.archive', $setup) }}">@csrf<button class="btn btn-secondary">Archive</button></form>
                        @endif
                        <span class="badge bg-{{ $setup->status === 'active' ? 'success' : ($setup->status === 'draft' ? 'warning text-dark' : 'secondary') }} align-self-center px-3 py-2">
                            {{ strtoupper($setup->status) }}
                        </span>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if(!$setup)
        <div class="alert alert-warning text-center">Create an Almanac setup to begin.</div>
    @else
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header fw-bold">Setup Details</div>
                    <div class="card-body small">
                        <div><strong>Title:</strong> {{ $setup->title }}</div>
                        <div><strong>Academic year:</strong> {{ $setup->academicYear?->year }}</div>
                        <div><strong>Range:</strong> {{ $setup->start_date->format('d M Y') }} – {{ $setup->end_date->format('d M Y') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card h-100 shadow-sm">
                    <div class="card-header fw-bold">Programme Groups</div>
                    <div class="card-body">
                        @forelse($setup->programGroups as $group)
                            <span class="badge me-1 mb-1" style="background:{{ $group->background_color ?: '#6c757d' }};color:{{ $group->text_color ?: '#fff' }}">
                                {{ $group->display_order }}. {{ $group->name }}
                            </span>
                        @empty
                            <span class="text-muted">No programme groups yet.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @if($calendar && $calendar['groups']->isNotEmpty())
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ $setup->title }}</strong>
                    <span class="small text-muted">Generated from week blocks and events</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table almanac-table mb-0">
                            <thead>
                                <tr>
                                    <th rowspan="2">Months</th>
                                    <th colspan="{{ $calendar['groups']->count() }}">Week Number</th>
                                    <th rowspan="2">Dates</th>
                                    <th rowspan="2">Academic Calendar</th>
                                    <th rowspan="2">Meeting/Activities Calendar</th>
                                </tr>
                                <tr>
                                    @foreach($calendar['groups'] as $group)
                                        <th>{{ $group->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($calendar['months'] as $month)
                                    @foreach($month['days'] as $dayIndex => $day)
                                        <tr class="{{ $day['is_week_end'] ? 'week-end' : '' }}">
                                            @if($dayIndex === 0)
                                                <td rowspan="{{ count($month['days']) }}" class="month-cell">{{ $month['label'] }}</td>
                                            @endif
                                            @foreach($calendar['groups'] as $group)
                                                @php($block = $day['week_values'][$group->id] ?? null)
                                                <td class="text-center fw-bold" style="background:{{ $block['background_color'] ?? '#fff' }};color:{{ $block['text_color'] ?? '#000' }}">
                                                    {{ $block['display_value'] ?? '' }}
                                                </td>
                                            @endforeach
                                            <td class="day-cell">{{ $day['day_label'] }}</td>
                                            <td class="event-cell">
                                                @foreach($day['academic_events'] as $event)
                                                    <div style="color:{{ $event['text_color'] ?: ($event['is_no_classes'] ? '#dc3545' : '#000') }}">{{ $event['text'] }}</div>
                                                @endforeach
                                            </td>
                                            <td class="event-cell">
                                                @foreach($day['meeting_events'] as $event)
                                                    <div style="color:{{ $event['text_color'] ?: '#000' }}">{{ $event['text'] }}</div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info">Add programme groups first, then add or generate week blocks.</div>
        @endif
    @endif
</div>

@include('almanac.partials.modals')
@endsection

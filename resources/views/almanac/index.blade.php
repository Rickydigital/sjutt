@extends('components.app-main-layout')

@section('styles')
<style>
    .almanac-table { min-width: 1350px; font-size: .78rem; border-collapse: collapse; }
    .almanac-table th, .almanac-table td { border: 1px solid #555 !important; padding: 4px; vertical-align: middle; }
    .almanac-table th { background: #d9d9d9; text-align: center; }
    .almanac-table .month-cell { writing-mode: vertical-rl; transform: rotate(180deg); font-weight: 700; text-align: center; min-width: 34px; }
    .almanac-table .day-cell { white-space: nowrap; width: 78px; }
    .almanac-table .event-cell { min-width: 330px; position: relative; }
    .almanac-table .week-cell { min-width: 84px; cursor: pointer; }
    .almanac-table .week-end td { border-bottom-width: 2px !important; }
    .cell-add-btn { border: 0; background: transparent; color: #198754; font-weight: 800; font-size: 1rem; line-height: 1; padding: 1px 5px; }
    .event-item { display:flex; gap:5px; align-items:flex-start; justify-content:space-between; padding:2px 0; }
    .event-edit-btn { border:0; background:transparent; color:#4B2E83; padding:0 2px; }
    .group-card { border-left: 5px solid var(--group-color); }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="fw-bold mb-1" style="color:#4B2E83">Academic Almanac</h1>
            <div class="text-muted">Click + inside a date cell to add an event for that exact date.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#setupModal">New Setup</button>
            @if($setup)
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#groupModal">Add Programme Group</button>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#weekBlockModal">Add Week Block</button>
                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#generateWeeksModal">Generate Weeks</button>
                <button class="btn btn-outline-dark js-add-event" data-date="{{ $setup->start_date->format('Y-m-d') }}" data-column="academic">Add Event</button>
                <a href="{{ route('almanac.pdf', $setup) }}" class="btn btn-success">Export PDF</a>
            @endif
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card shadow-sm mb-3"><div class="card-body">
        <form method="GET" action="{{ route('almanac.index') }}" class="row g-2 align-items-end">
            <div class="col-md-6"><label class="form-label">Select Almanac Setup</label>
                <select name="setup_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Select...</option>
                    @foreach($setups as $item)
                        <option value="{{ $item->id }}" @selected($setup?->id === $item->id)>{{ $item->title }} — {{ strtoupper($item->status) }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div></div>

    @if(!$setup)
        <div class="alert alert-warning text-center">Create an Almanac setup to begin.</div>
    @else
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center"><strong>Programme Groups</strong><span class="small text-muted">Click Edit to update name, order, colour, status or assigned programmes.</span></div>
            <div class="card-body"><div class="row g-2">
                @forelse($setup->programGroups as $group)
                    <div class="col-lg-4 col-md-6">
                        <div class="border rounded p-2 h-100 group-card" style="--group-color:{{ $group->background_color ?: '#6c757d' }}">
                            <div class="fw-bold">{{ $group->display_order }}. {{ $group->name }}</div>
                            <div class="small text-muted">{{ $group->level ?: 'No level' }} · {{ $group->is_active ? 'Active' : 'Inactive' }}</div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 js-edit-group" data-id="{{ $group->id }}">Edit</button>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No programme groups yet.</div>
                @endforelse
            </div></div>
        </div>

        @if($calendar && $calendar['groups']->isNotEmpty())
            <div class="card shadow-sm"><div class="card-header"><strong>{{ $setup->title }}</strong></div>
                <div class="card-body p-0"><div class="table-responsive">
                    <table class="table almanac-table mb-0">
                        <thead><tr><th rowspan="2">Months</th><th colspan="{{ $calendar['groups']->count() }}">Week Number</th><th rowspan="2">Dates</th><th rowspan="2">Academic Calendar</th><th rowspan="2">Meeting/Activities Calendar</th></tr>
                        <tr>@foreach($calendar['groups'] as $group)<th>{{ $group->name }}</th>@endforeach</tr></thead>
                        <tbody>
                        @foreach($calendar['months'] as $month)
                            @foreach($month['days'] as $dayIndex => $day)
                                <tr class="{{ $day['is_week_end'] ? 'week-end' : '' }}">
                                    @if($dayIndex === 0)<td rowspan="{{ count($month['days']) }}" class="month-cell">{{ $month['label'] }}</td>@endif
                                    @foreach($calendar['groups'] as $group)
                                        @php($block = $day['week_values'][$group->id] ?? null)
                                        <td class="text-center fw-bold week-cell {{ $block ? 'js-edit-week-block' : '' }}" data-id="{{ $block['id'] ?? '' }}" style="background:{{ $block['background_color'] ?? '#fff' }};color:{{ $block['text_color'] ?? '#000' }}" title="{{ $block ? 'Click to edit week block' : '' }}">
                                            {{ $block['full_label'] ?? '' }}
                                        </td>
                                    @endforeach
                                    <td class="day-cell">{{ $day['day_label'] }}</td>
                                    @foreach(['academic' => 'academic_events', 'meeting' => 'meeting_events'] as $column => $key)
                                        <td class="event-cell">
                                            <div class="d-flex justify-content-end"><button type="button" class="cell-add-btn js-add-event" title="Add {{ $column }} event" data-date="{{ $day['date_value'] }}" data-column="{{ $column }}">+</button></div>
                                            @foreach($day[$key] as $event)
                                                <div class="event-item" style="color:{{ $event['text_color'] ?: ($event['is_no_classes'] ? '#dc3545' : '#000') }}">
                                                    <span>{{ $event['text'] }}</span>
                                                    <button type="button" class="event-edit-btn js-edit-event" data-id="{{ $event['id'] }}" title="Edit event"><i class="fas fa-edit"></i></button>
                                                </div>
                                            @endforeach
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div></div>
            </div>
        @else
            <div class="alert alert-info">Add programme groups first, then add or generate week blocks.</div>
        @endif
    @endif
</div>

@include('almanac.partials.modals')
@endsection

@section('scripts')
@if($setup)
<script>
(() => {
    const setupId = {{ $setup->id }};
    const base = @json(url('/almanac/setups/'.$setup->id));
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const modal = id => bootstrap.Modal.getOrCreateInstance(document.getElementById(id));

    document.querySelectorAll('.js-add-event').forEach(btn => btn.addEventListener('click', () => {
        const form = document.getElementById('eventForm');
        form.reset();
        form.action = `${base}/events`;
        form.querySelector('input[name="_method"]')?.remove();
        document.getElementById('eventModalTitle').textContent = 'Add Almanac Event';
        form.start_date.value = btn.dataset.date;
        form.end_date.value = '';
        form.event_column.value = btn.dataset.column || 'academic';
        form.applies_to_all.checked = true;
        modal('eventModal').show();
    }));

    document.querySelectorAll('.js-edit-event').forEach(btn => btn.addEventListener('click', async () => {
        const data = await fetch(`${base}/events/${btn.dataset.id}`, {headers:{Accept:'application/json'}}).then(r => r.json());
        const form = document.getElementById('eventForm');
        form.action = `${base}/events/${data.id}`;
        ensureMethod(form, 'PUT');
        document.getElementById('eventModalTitle').textContent = 'Edit Almanac Event';
        fill(form, data);
        setMulti(form.querySelector('[name="program_group_ids[]"]'), data.program_group_ids || []);
        modal('eventModal').show();
    }));

    document.querySelectorAll('.js-edit-group').forEach(btn => btn.addEventListener('click', async () => {
        const data = await fetch(`${base}/groups/${btn.dataset.id}`, {headers:{Accept:'application/json'}}).then(r => r.json());
        const form = document.getElementById('editGroupForm');
        form.action = `${base}/groups/${data.id}`;
        fill(form, data);
        setMulti(form.querySelector('[name="program_ids[]"]'), data.program_ids || []);
        modal('editGroupModal').show();
    }));

    document.querySelectorAll('.js-edit-week-block').forEach(cell => cell.addEventListener('click', async () => {
        if (!cell.dataset.id) return;
        const data = await fetch(`${base}/week-blocks/${cell.dataset.id}`, {headers:{Accept:'application/json'}}).then(r => r.json());
        const form = document.getElementById('editWeekBlockForm');
        form.action = `${base}/week-blocks/${data.id}`;
        fill(form, data);
        modal('editWeekBlockModal').show();
    }));

    function ensureMethod(form, method) {
        let input = form.querySelector('input[name="_method"]');
        if (!input) { input = document.createElement('input'); input.type='hidden'; input.name='_method'; form.appendChild(input); }
        input.value = method;
    }
    function fill(form, data) {
        Object.entries(data).forEach(([key, value]) => {
            const el = form.elements[key]; if (!el || key.endsWith('_ids')) return;
            if (el.type === 'checkbox') el.checked = Boolean(value); else el.value = value ?? '';
        });
    }
    function setMulti(select, values) {
        if (!select) return; const wanted = values.map(String);
        [...select.options].forEach(o => o.selected = wanted.includes(String(o.value)));
    }
})();
</script>
@endif
@endsection

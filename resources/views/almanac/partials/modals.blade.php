<div class="modal fade" id="setupModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('almanac.setups.store') }}">@csrf
<div class="modal-header"><h5 class="modal-title">Create Almanac Setup</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><label class="form-label">Academic Year</label><select name="academic_year_id" class="form-select mb-2" required>@foreach($academicYears as $year)<option value="{{ $year->id }}">{{ $year->year ?? $year->name }}</option>@endforeach</select>
<label class="form-label">Title</label><input name="title" class="form-control mb-2" required><div class="row"><div class="col"><label>Start</label><input type="date" name="start_date" class="form-control" required></div><div class="col"><label>End</label><input type="date" name="end_date" class="form-control" required></div></div></div>
<div class="modal-footer"><button class="btn btn-primary">Create Setup</button></div></form></div></div></div>

@if($setup)
<div class="modal fade" id="groupModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('almanac.groups.store', $setup) }}">@csrf
<div class="modal-header"><h5 class="modal-title">Add Programme Group</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('almanac.partials.program-group-fields', ['prefix' => 'add'])</div><div class="modal-footer"><button class="btn btn-primary">Save Group</button></div></form></div></div></div>

<div class="modal fade" id="editGroupModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form id="editGroupForm" method="POST">@csrf @method('PUT')
<div class="modal-header"><h5 class="modal-title">Edit Programme Group</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('almanac.partials.program-group-fields', ['prefix' => 'edit'])</div><div class="modal-footer"><button class="btn btn-primary">Update Group</button></div></form></div></div></div>

<div class="modal fade" id="eventModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="eventForm" method="POST" action="{{ route('almanac.events.store', $setup) }}">@csrf
<div class="modal-header"><h5 class="modal-title" id="eventModalTitle">Add Almanac Event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
<label>Title</label><input name="title" class="form-control mb-2" required><label>Description</label><textarea name="description" class="form-control mb-2"></textarea>
<div class="row"><div class="col"><label>Start Date</label><input type="date" name="start_date" class="form-control" required></div><div class="col"><label>End Date (optional)</label><input type="date" name="end_date" class="form-control"></div></div>
<div class="small text-muted">When an end date is supplied, the Almanac automatically prints “End of [event title]” on that end date. A one-day event is printed once only.</div>
<div class="row mt-2"><div class="col"><label>Event Type</label><select name="event_column" class="form-select"><option value="academic">Academic Calendar</option><option value="meeting">Meeting/Activities Calendar</option></select></div><div class="col"><label>Category</label><input name="category" class="form-control"></div></div>
<div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="applies_to_all" value="1" checked><label class="form-check-label">Applies to all groups</label></div>
<label class="mt-2">Selected Groups</label><select name="program_group_ids[]" class="form-select" multiple size="5">@foreach($setup->programGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select>
<div class="row mt-2"><div class="col"><label><input type="checkbox" name="is_no_classes" value="1"> No classes</label></div><div class="col"><label><input type="checkbox" name="is_tentative" value="1"> Tentative</label></div></div>
<div class="row mt-2"><div class="col"><label>Background</label><input type="color" name="background_color" class="form-control form-control-color" value="#ffffff"></div><div class="col"><label>Text</label><input type="color" name="text_color" class="form-control form-control-color" value="#000000"></div></div>
</div><div class="modal-footer"><button class="btn btn-dark">Save Event</button></div></form></div></div></div>
@endif

<div class="modal fade" id="setupModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('almanac.setups.store') }}">@csrf
<div class="modal-header"><h5 class="modal-title">Create Almanac Setup</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <label class="form-label">Academic Year</label><select name="academic_year_id" class="form-select mb-2" required>@foreach($academicYears as $year)<option value="{{ $year->id }}">{{ $year->year }}</option>@endforeach</select>
    <label class="form-label">Title</label><input name="title" class="form-control mb-2" placeholder="Academic Almanac for 2025/2026" required>
    <div class="row"><div class="col"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" required></div><div class="col"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" required></div></div>
</div><div class="modal-footer"><button class="btn btn-primary">Create Setup</button></div></form></div></div></div>

@if($setup)
<div class="modal fade" id="groupModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('almanac.groups.store', $setup) }}">@csrf
<div class="modal-header"><h5 class="modal-title">Add Programme Group</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <label class="form-label">Group Name</label><input name="name" class="form-control mb-2" required>
    <label class="form-label">Level</label><input name="level" class="form-control mb-2" placeholder="Degree / Masters / Non-Degree">
    <label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control mb-2" min="1" value="{{ $setup->programGroups->count()+1 }}" required>
    <div class="row"><div class="col"><label class="form-label">Background</label><input type="color" name="background_color" class="form-control form-control-color" value="#dbeafe"></div><div class="col"><label class="form-label">Text</label><input type="color" name="text_color" class="form-control form-control-color" value="#000000"></div></div>
    <label class="form-label mt-2">Programs</label><select name="program_ids[]" class="form-select" multiple size="6">@foreach($programs as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select>
    <input type="hidden" name="is_active" value="1">
</div><div class="modal-footer"><button class="btn btn-primary">Save Group</button></div></form></div></div></div>

<div class="modal fade" id="weekBlockModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('almanac.week-blocks.store', $setup) }}">@csrf
<div class="modal-header"><h5 class="modal-title">Add Week Block</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <label class="form-label">Programme Group</label><select name="almanac_program_group_id" class="form-select mb-2" required>@foreach($setup->programGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select>
    <div class="row"><div class="col"><label class="form-label">Start</label><input type="date" name="start_date" class="form-control" required></div><div class="col"><label class="form-label">End</label><input type="date" name="end_date" class="form-control" required></div></div>
    <label class="form-label mt-2">Display Value</label><input name="display_value" class="form-control mb-2" placeholder="1, 2, Exam1, Exam2">
    <label class="form-label">Type</label><select name="block_type" class="form-select mb-2">@foreach(['teaching','examination','registration','orientation','fieldwork','clinical','holiday','break','other'] as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select>
    <div class="row"><div class="col"><label>Background</label><input type="color" name="background_color" class="form-control form-control-color" value="#fde68a"></div><div class="col"><label>Text</label><input type="color" name="text_color" class="form-control form-control-color" value="#000000"></div></div>
    <label class="form-label mt-2">Notes</label><textarea name="notes" class="form-control"></textarea>
</div><div class="modal-footer"><button class="btn btn-success">Save Block</button></div></form></div></div></div>

<div class="modal fade" id="generateWeeksModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('almanac.week-blocks.generate', $setup) }}">@csrf
<div class="modal-header"><h5 class="modal-title">Generate Teaching Weeks</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <label class="form-label">Programme Group</label><select name="almanac_program_group_id" class="form-select mb-2" required>@foreach($setup->programGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select>
    <label class="form-label">First Week Start Date</label><input type="date" name="start_date" class="form-control mb-2" required>
    <div class="row"><div class="col"><label class="form-label">Number of Weeks</label><input type="number" name="number_of_weeks" class="form-control" min="1" max="60" value="15" required></div><div class="col"><label class="form-label">Starting Number</label><input type="number" name="starting_number" class="form-control" min="1" value="1" required></div></div>
    <div class="row mt-2"><div class="col"><label>Background</label><input type="color" name="background_color" class="form-control form-control-color" value="#bfdbfe"></div><div class="col"><label>Text</label><input type="color" name="text_color" class="form-control form-control-color" value="#000000"></div></div>
</div><div class="modal-footer"><button class="btn btn-warning">Generate</button></div></form></div></div></div>

<div class="modal fade" id="eventModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="{{ route('almanac.events.store', $setup) }}">@csrf
<div class="modal-header"><h5 class="modal-title">Add Almanac Event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <label class="form-label">Title</label><input name="title" class="form-control mb-2" required>
    <label class="form-label">Description</label><textarea name="description" class="form-control mb-2"></textarea>
    <div class="row"><div class="col"><label>Start Date</label><input type="date" name="start_date" class="form-control" required></div><div class="col"><label>End Date</label><input type="date" name="end_date" class="form-control"></div></div>
    <div class="row mt-2"><div class="col"><label>Column</label><select name="event_column" class="form-select"><option value="academic">Academic Calendar</option><option value="meeting">Meeting/Activities Calendar</option></select></div><div class="col"><label>Category</label><input name="category" class="form-control" placeholder="Examination, Meeting, Holiday..."></div></div>
    <div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="applies_to_all" value="1" checked id="allGroups"><label class="form-check-label" for="allGroups">Applies to all groups</label></div>
    <label class="form-label mt-2">Selected Groups</label><select name="program_group_ids[]" class="form-select" multiple size="5">@foreach($setup->programGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select>
    <div class="row mt-2"><div class="col"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_no_classes" value="1" id="noClasses"><label class="form-check-label" for="noClasses">No classes</label></div></div><div class="col"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_tentative" value="1" id="tentative"><label class="form-check-label" for="tentative">Tentative</label></div></div></div>
</div><div class="modal-footer"><button class="btn btn-dark">Save Event</button></div></form></div></div></div>
@endif

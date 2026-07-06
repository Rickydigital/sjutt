<div class="modal-header"><h5>{{ $item ? 'Edit Calendar Item' : 'Add Calendar Item' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body row g-3">
    <div class="col-md-8"><label>Title</label><input name="title" class="form-control" value="{{ old('title', $item->title ?? '') }}" required></div>
    <div class="col-md-4"><label>Type</label><select name="type" class="form-select">@foreach(['meeting','assembly','convocation','deadline','event','other'] as $t)<option value="{{ $t }}" @selected(old('type', $item->type ?? 'event')===$t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
    <div class="col-12"><label>Description</label><textarea name="description" rows="3" class="form-control">{{ old('description', $item->description ?? '') }}</textarea></div>
    <div class="col-md-4"><label>Date</label><input type="date" name="calendar_date" class="form-control" value="{{ old('calendar_date', isset($item?->calendar_date) ? $item->calendar_date->format('Y-m-d') : '') }}" required></div>
    <div class="col-md-4"><label>Start Time</label><input type="time" name="start_time" class="form-control" value="{{ old('start_time', $item->start_time ?? '') }}"></div>
    <div class="col-md-4"><label>End Time</label><input type="time" name="end_time" class="form-control" value="{{ old('end_time', $item->end_time ?? '') }}"></div>
    <div class="col-md-6"><label>Venue</label><input name="venue" class="form-control" value="{{ old('venue', $item->venue ?? '') }}"></div>
    <div class="col-md-3"><label>Status</label><select name="status" class="form-select">@foreach(['draft','published','cancelled'] as $s)<option value="{{ $s }}" @selected(old('status', $item->status ?? 'published')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <div class="col-md-3 form-check mt-4"><input type="checkbox" name="is_public" value="1" class="form-check-input" @checked(old('is_public', $item->is_public ?? true))><label class="form-check-label">Public</label></div>
    <div class="col-md-12"><label>Link Event Optional</label><select name="alumni_event_id" class="form-select"><option value="">None</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected(old('alumni_event_id', $item->alumni_event_id ?? '')==$event->id)>{{ $event->title }}</option>@endforeach</select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Save</button></div>

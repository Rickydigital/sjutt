<div class="modal-header"><h5>{{ $event ? 'Edit Event' : 'Add Event' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body row g-3">
    <div class="col-md-8"><label>Title</label><input name="title" class="form-control" value="{{ old('title', $event->title ?? '') }}" required></div>
    <div class="col-md-4"><label>Status</label><select name="status" class="form-select" required>@foreach(['draft','published','cancelled','completed'] as $s)<option value="{{ $s }}" @selected(old('status', $event->status ?? 'draft')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <div class="col-12"><label>Description</label><textarea name="description" rows="4" class="form-control">{{ old('description', $event->description ?? '') }}</textarea></div>
    <div class="col-md-6"><label>Venue</label><input name="venue" class="form-control" value="{{ old('venue', $event->venue ?? '') }}"></div>
    <div class="col-md-6"><label>Location</label><input name="location" class="form-control" value="{{ old('location', $event->location ?? '') }}"></div>
    <div class="col-md-6"><label>Starts At</label><input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', isset($event?->starts_at) ? $event->starts_at->format('Y-m-d\TH:i') : '') }}"></div>
    <div class="col-md-6"><label>Ends At</label><input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', isset($event?->ends_at) ? $event->ends_at->format('Y-m-d\TH:i') : '') }}"></div>
    <div class="col-md-6"><label>Banner</label><input type="file" name="banner" class="form-control"></div>
    <div class="col-md-3"><label>Capacity</label><input type="number" name="capacity" class="form-control" value="{{ old('capacity', $event->capacity ?? '') }}"></div>
    <div class="col-md-3 form-check mt-4"><input type="checkbox" name="requires_registration" value="1" class="form-check-input" @checked(old('requires_registration', $event->requires_registration ?? false))><label class="form-check-label">Requires registration</label></div>
    @if(!$event)<div class="col-md-3 form-check"><input type="checkbox" name="add_to_calendar" value="1" class="form-check-input"><label class="form-check-label">Add to calendar</label></div>@endif
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>

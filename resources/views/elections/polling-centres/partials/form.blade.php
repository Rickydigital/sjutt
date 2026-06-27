<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Centre Name</label>
        <input type="text" name="name" class="form-control" required
               value="{{ old('name', $centre->name ?? '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Location</label>
        <input type="text" name="location" class="form-control"
               value="{{ old('location', $centre->location ?? '') }}">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Manager Name</label>
        <input type="text" name="manager_name" class="form-control"
               value="{{ old('manager_name', $centre->manager_name ?? '') }}">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Manager Phone</label>
        <input type="text" name="manager_phone" class="form-control"
               value="{{ old('manager_phone', $centre->manager_phone ?? '') }}">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Manager Email</label>
        <input type="email" name="manager_email" class="form-control"
               value="{{ old('manager_email', $centre->manager_email ?? '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Active From</label>
        <input type="datetime-local" name="active_from" class="form-control"
               value="{{ old('active_from', isset($centre) && $centre?->active_from ? $centre->active_from->format('Y-m-d\TH:i') : '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Active Until</label>
        <input type="datetime-local" name="active_until" class="form-control"
               value="{{ old('active_until', isset($centre) && $centre?->active_until ? $centre->active_until->format('Y-m-d\TH:i') : '') }}">
    </div>

    <div class="col-md-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   id="is_active_{{ $centre->id ?? 'create' }}"
                   {{ old('is_active', $centre->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active_{{ $centre->id ?? 'create' }}">
                Centre Active
            </label>
        </div>
    </div>
</div>
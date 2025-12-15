<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Platform <span class="text-danger">*</span></label>
        <select name="platform" class="form-select" required>
            <option value="android" {{ old('platform', $version->platform ?? '') == 'android' ? 'selected' : '' }}>Android</option>
            <option value="ios" {{ old('platform', $version->platform ?? '') == 'ios' ? 'selected' : '' }}>iOS</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Version Name <span class="text-danger">*</span></label>
        <input type="text" name="version_name" class="form-control" value="{{ old('version_name', $version->version_name ?? '') }}" required>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Version Code <span class="text-danger">*</span></label>
        <input type="number" name="version_code" class="form-control" value="{{ old('version_code', $version->version_code ?? '') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Force Update <span class="text-danger">*</span></label>
        <select name="is_force_update" class="form-select" required>
            <option value="1" {{ old('is_force_update', $version->is_force_update ?? 0) ? 'selected' : '' }}>Yes</option>
            <option value="0" {{ old('is_force_update', $version->is_force_update ?? 0) ? '' : 'selected' }}>No</option>
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Whats New</label>
    <textarea name="whats_new" class="form-control" rows="4">{{ old('whats_new', $version->whats_new ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">APK/IPA File {{ isset($isEdit) ? '(Leave empty to keep current)' : '' }} <span class="text-danger">*</span></label>
    <input type="file" name="apk_file" class="form-control" accept=".apk,.ipa" {{ !isset($isEdit) ? 'required' : '' }}>
    @if(isset($version) && $version->download_url)
        <small class="text-muted">Current: <a href="{{ route('app.download', basename($version->download_url)) }}" 
                                    target="_blank" 
                                    class="text-primary small">
                                        Download
                                    </a></small>
    @endif
</div>
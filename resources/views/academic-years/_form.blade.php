@csrf

@if(isset($academicYear))
    @method('PUT')
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Academic Year <span class="text-danger">*</span></label>
        <input
            type="text"
            class="form-control @error('name') is-invalid @enderror"
            id="name"
            name="name"
            value="{{ old('name', $academicYear->name ?? '') }}"
            placeholder="2025/2026"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select
            class="form-select @error('status') is-invalid @enderror"
            id="status"
            name="status"
            required
        >
            @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $academicYear->status ?? 'draft') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
        <input
            type="date"
            class="form-control @error('start_date') is-invalid @enderror"
            id="start_date"
            name="start_date"
            value="{{ old('start_date', isset($academicYear) ? $academicYear->start_date?->format('Y-m-d') : '') }}"
            required
        >
        @error('start_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
        <input
            type="date"
            class="form-control @error('end_date') is-invalid @enderror"
            id="end_date"
            name="end_date"
            value="{{ old('end_date', isset($academicYear) ? $academicYear->end_date?->format('Y-m-d') : '') }}"
            required
        >
        @error('end_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i>
        {{ isset($academicYear) ? 'Update Academic Year' : 'Create Academic Year' }}
    </button>

    <a href="{{ route('academic-years.index') }}" class="btn btn-outline-secondary">
        Cancel
    </a>
</div>

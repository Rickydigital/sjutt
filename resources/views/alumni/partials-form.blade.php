@php
    $education = $alumnus?->educations?->first();
    $employment = $alumnus?->employments?->first();
@endphp
<input type="hidden" name="education_id" value="{{ $education?->id }}">
<input type="hidden" name="employment_id" value="{{ $employment?->id }}">

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">First Name *</label>
        <input type="text" name="f_name" class="form-control" value="{{ old('f_name', $alumnus?->f_name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Middle Name</label>
        <input type="text" name="m_name" class="form-control" value="{{ old('m_name', $alumnus?->m_name) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Last Name *</label>
        <input type="text" name="l_name" class="form-control" value="{{ old('l_name', $alumnus?->l_name) }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $alumnus?->email) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $alumnus?->phone) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', optional($alumnus?->date_of_birth)->format('Y-m-d')) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
            <option value="">Choose</option>
            <option value="male" @selected(old('gender', $alumnus?->gender) === 'male')>Male</option>
            <option value="female" @selected(old('gender', $alumnus?->gender) === 'female')>Female</option>
            <option value="other" @selected(old('gender', $alumnus?->gender) === 'other')>Other</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">NIDA Number</label>
        <input type="text" name="nida_number" class="form-control" value="{{ old('nida_number', $alumnus?->nida_number) }}" placeholder="11111111-11111-11111-11">
    </div>
    <div class="col-md-4">
        <label class="form-label">Profile Photo</label>
        <input type="file" name="profile_photo" class="form-control" accept="image/*">
    </div>

    <div class="col-12"><hr><h6>Education</h6></div>
    <div class="col-md-4">
        <label class="form-label">Faculty / School / College</label>
        <select name="faculty_id" class="form-select">
            <option value="">Choose</option>
            @foreach($faculties as $faculty)
                <option value="{{ $faculty->id }}" @selected(old('faculty_id', $education?->faculty_id) == $faculty->id)>{{ $faculty->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select">
            <option value="">Choose</option>
            @foreach($programs as $program)
                <option value="{{ $program->id }}" @selected(old('program_id', $education?->program_id) == $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Graduation Year</label>
        <select name="graduation_year_id" class="form-select">
            <option value="">Choose</option>
            @foreach($graduationYears as $year)
                <option value="{{ $year->id }}" @selected(old('graduation_year_id', $education?->graduation_year_id) == $year->id)>{{ $year->year }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Degree Program and Major Conferred</label>
        <input type="text" name="degree_program_major" class="form-control" value="{{ old('degree_program_major', $education?->degree_program_major) }}">
    </div>

    <div class="col-12"><hr><h6>Employment</h6></div>
    <div class="col-md-3">
        <label class="form-label">Employment State</label>
        <select name="employment_state_id" class="form-select">
            <option value="">Choose</option>
            @foreach($employmentStates as $state)
                <option value="{{ $state->id }}" @selected(old('employment_state_id', $employment?->employment_state_id) == $state->id)>{{ $state->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Employment Sector</label>
        <select name="employment_sector_id" class="form-select">
            <option value="">Choose</option>
            @foreach($employmentSectors as $sector)
                <option value="{{ $sector->id }}" @selected(old('employment_sector_id', $employment?->employment_sector_id) == $sector->id)>{{ $sector->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Employment Year</label>
        <select name="employment_year_id" class="form-select">
            <option value="">Choose</option>
            @foreach($employmentYears as $year)
                <option value="{{ $year->id }}" @selected(old('employment_year_id', $employment?->employment_year_id) == $year->id)>{{ $year->year }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Organization</label>
        <input type="text" name="organization" class="form-control" value="{{ old('organization', $employment?->organization) }}" placeholder="NIL if none">
    </div>

    <div class="col-12"><hr><h6>Settlement and Engagement</h6></div>
    <div class="col-md-4">
        <label class="form-label">Country</label>
        <select name="settlement_country_id" class="form-select">
            <option value="">Choose</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected(old('settlement_country_id', $alumnus?->settlement_country_id) == $country->id)>{{ $country->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Region / Province</label>
        <input type="text" name="settlement_region" class="form-control" value="{{ old('settlement_region', $alumnus?->settlement_region) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">City / Town / Village</label>
        <input type="text" name="settlement_city" class="form-control" value="{{ old('settlement_city', $alumnus?->settlement_city) }}">
    </div>

    <div class="col-md-4 form-check ms-2">
        <input class="form-check-input" type="checkbox" name="interested_meetings" value="1" @checked(old('interested_meetings', $alumnus?->interested_meetings))>
        <label class="form-check-label">Interested in Alumni meetings</label>
    </div>
    <div class="col-md-4 form-check">
        <input class="form-check-input" type="checkbox" name="interested_social_platform" value="1" @checked(old('interested_social_platform', $alumnus?->interested_social_platform))>
        <label class="form-check-label">Interested in WhatsApp/Telegram</label>
    </div>
    <div class="col-md-3 form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $alumnus?->is_active))>
        <label class="form-check-label">Activated</label>
    </div>

    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="pending" @selected(old('status', $alumnus?->status ?? 'pending') === 'pending')>Pending</option>
            <option value="active" @selected(old('status', $alumnus?->status) === 'active')>Active</option>
            <option value="suspended" @selected(old('status', $alumnus?->status) === 'suspended')>Suspended</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Password {{ $alumnus ? '(leave blank to keep)' : '' }}</label>
        <input type="password" name="password" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control">
    </div>
</div>

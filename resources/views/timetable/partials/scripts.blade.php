<script>
    window.exportTimetable = function(draft) {
        const setupId = $('#setup_id').val() || $('#setup_id_filter').val() || $('#generate_setup_id').val() || '';
        const query = new URLSearchParams({
            draft: draft,
            setup_id: setupId
        });
        window.location.href = `{{ route('timetable.export') }}?${query.toString()}`;
    };

    $(function () {
        const faculties = @json($faculties);
        const timeSlots = @json($allTimeSlots);
        let availableCourses = [];

        function showAlert(type, title, msg) {
            Swal.fire({
                icon: type,
                title: title,
                html: `<div class="text-start">${msg}</div>`,
                confirmButtonText: 'OK',
                confirmButtonColor: type === 'error' ? '#dc3545' : '#4b2e83'
            });
        }

        function initSelect2(selector, modalSelector = null) {
            const $el = $(selector);

            $el.select2({
                theme: 'classic',
                dropdownParent: modalSelector ? $(modalSelector) : $('body'),
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true,
                closeOnSelect: false
            });
        }

        initSelect2('#setup_id');
        initSelect2('#setup_id_filter');
        initSelect2('#faculty');
        initSelect2('#generate_setup_id', '#generateTimetableModal');
        initSelect2('#generate_faculty_id', '#generateTimetableModal');
        initSelect2('#generate_venues', '#generateTimetableModal');

        initSelect2('#modal_course_code', '#addTimetableModal');
        initSelect2('#modal_lecturer_id', '#addTimetableModal');
        initSelect2('#modal_activity', '#addTimetableModal');
        initSelect2('#modal_venue_id', '#addTimetableModal');
        initSelect2('#modal_group_selection', '#addTimetableModal');
        initSelect2('#modal_time_end', '#addTimetableModal');

        initSelect2('#edit_modal_course_code', '#editTimetableModal');
        initSelect2('#edit_modal_lecturer_id', '#editTimetableModal');
        initSelect2('#edit_modal_activity', '#editTimetableModal');
        initSelect2('#edit_modal_venue_id', '#editTimetableModal');
        initSelect2('#edit_modal_group_selection', '#editTimetableModal');
        initSelect2('#edit_modal_time_end', '#editTimetableModal');

        initSelect2('#add_semester_id', '#addTimetableSemesterModal');
        initSelect2('#edit_semester_id', '#editTimetableSemesterModal');

        $(document).on('focus', '.course-code, .lecturer-select, .activity-select, .group-selection', function () {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                initSelect2(this, '#generateTimetableModal');
            }
        });

        function setupAllGroupsExclusive(selector) {
            $(document).on('select2:select', selector, function (e) {
                const selected = e.params.data.id;
                const $select = $(this);
                let values = $select.val() || [];

                if (selected === 'All Groups') {
                    $select.val(['All Groups']).trigger('change');
                } else if (values.includes('All Groups')) {
                    values = values.filter(v => v !== 'All Groups');
                    $select.val(values).trigger('change');
                }
            });
        }

        setupAllGroupsExclusive('#modal_group_selection');
        setupAllGroupsExclusive('#edit_modal_group_selection');
        setupAllGroupsExclusive('.group-selection');

        $('#setup_id').on('change', function () {
            $('#setupFilterForm').submit();
        });

        $('#faculty, #setup_id_filter').on('change', function () {
            $('#facultyFilterForm').submit();
        });

        $(document).on('click', '#activateSelectedSetupBtn', function () {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'question',
                title: 'Activate this setup?',
                text: 'The current active setup will be archived.',
                showCancelButton: true,
                confirmButtonText: 'Activate',
                confirmButtonColor: '#198754'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.post(`{{ url('/timetable-setups') }}/${id}/activate`, {
                    _token: '{{ csrf_token() }}'
                }).done(function (response) {
                    Swal.fire('Success', response.message, 'success').then(() => location.reload());
                }).fail(function (xhr) {
                    const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>') || 'Activation failed.';
                    Swal.fire('Error', msg, 'error');
                });
            });
        });

        $(document).on('click', '#deleteSelectedSetupBtn', function () {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'warning',
                title: 'Delete this setup?',
                text: 'Delete only if it has no timetable entries.',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#dc3545'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: `{{ url('/timetable-setups') }}/${id}`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    }
                }).done(function (response) {
                    Swal.fire('Deleted', response.message, 'success').then(() => location.reload());
                }).fail(function (xhr) {
                    const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>') || 'Delete failed.';
                    Swal.fire('Error', msg, 'error');
                });
            });
        });

        function reindexCourses() {
            $('.course-selection').each(function(index) {
                $(this).find('.course-code').attr('name', `courses[${index}]`);
                $(this).find('.lecturer-select').attr('name', `lecturers[${index}]`);
                $(this).find('.activity-select').attr('name', `activities[${index}]`);
                $(this).find('.group-selection').attr('name', `groups[${index}][]`);
            });
        }

        function loadGenerateGroups(facultyId, $target, modalSelector = null, selectedValues = []) {
            $.ajax({
                url: '{{ route('timetables.getGroups') }}',
                method: 'GET',
                data: { faculty_id: facultyId },
                success: function (response) {
                    const wasSelect2 = $target.hasClass('select2-hidden-accessible');

                    if (wasSelect2) {
                        $target.select2('destroy');
                    }

                    $target.empty().append('<option value="All Groups">All Groups</option>');

                    (response.groups || []).forEach(group => {
                        $target.append(new Option(group.group_name, group.group_name));
                    });

                    if (selectedValues && selectedValues.length) {
                        $target.val(selectedValues);
                    } else {
                        $target.val(null);
                    }

                    initSelect2($target, modalSelector);
                    $target.trigger('change');
                },
                error: function (xhr) {
                    const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>')
                        || xhr.responseJSON?.message
                        || 'Failed to load groups.';
                    showAlert('error', 'Group Loading Failed', msg);
                }
            });
        }

        $('#add-course').on('click', function () {
            const index = $('.course-selection').length;

            const html = `
                <div class="tt-course-selection course-selection">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <label class="form-label">Course</label>
                            <select name="courses[${index}]" class="form-control course-code" required>
                                <option value="">Select Course</option>
                                ${availableCourses.map(c => `<option value="${c.course_code}" data-cross="${c.cross_catering ? 1 : 0}">${c.course_code}</option>`).join('')}
                            </select>
                            <div class="tt-cross-note">Cross-catering course detected. Generation will apply shared session logic.</div>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Assigned User</label>
                            <select name="lecturers[${index}]" class="form-control lecturer-select" required>
                                <option value="">Select User</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label">Activity</label>
                            <select name="activities[${index}]" class="form-control activity-select" required>
                                <option value="">Select Activity</option>
                                <option value="Lecture">Lecture</option>
                                <option value="Practical">Practical</option>
                                <option value="Workshop">Workshop</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Groups</label>
                            <select name="groups[${index}][]" class="form-control group-selection" multiple required>
                                <option value="All Groups">All Groups</option>
                            </select>
                        </div>
                        <div class="col-lg-1 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger w-100 remove-course">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>`;

            $('#course-selections').append(html);

            initSelect2('.course-selection:last .course-code', '#generateTimetableModal');
            initSelect2('.course-selection:last .lecturer-select', '#generateTimetableModal');
            initSelect2('.course-selection:last .activity-select', '#generateTimetableModal');
            initSelect2('.course-selection:last .group-selection', '#generateTimetableModal');

            const facultyId = $('#generate_faculty_id').val();
            if (facultyId) {
                loadGenerateGroups(
                    facultyId,
                    $('.course-selection:last .group-selection'),
                    '#generateTimetableModal'
                );
            }
        });

        $(document).on('click', '.remove-course', function () {
            if ($('.course-selection').length > 1) {
                $(this).closest('.course-selection').remove();
                reindexCourses();
            }
        });

        function maybeLoadSetupDecision() {
            const facultyId = $('#generate_faculty_id').val();
            const setupId = $('#generate_setup_id').val();

            if (!facultyId || !setupId) return;

            $.get(`{{ route('timetables.setupDecision') }}`, {
                faculty_id: facultyId,
                setup_id: setupId
            }).done(function (response) {
                if (response.requires_decision) {
                    $('#setupDecisionMessage').html(response.message);
                    $('#currentCoursesBox').html((response.current_courses || []).join('<br>') || 'No current courses');
                    $('#targetCoursesBox').html((response.target_courses || []).join('<br>') || 'No target courses');
                    $('#generation_mode').val('');
                    $('#generation_mode_hidden').val('');
                    $('#setupDecisionModal').modal('show');
                } else {
                    $('#generation_mode_hidden').val('');
                }
            });
        }

        function loadGenerateCourses() {
            const facultyId = $('#generate_faculty_id').val();
            const setupId = $('#generate_setup_id').val();

            if (!facultyId || !setupId) return;

            $.ajax({
                url: '{{ route('timetables.getCourses') }}',
                method: 'GET',
                data: {
                    faculty_id: facultyId,
                    setup_id: setupId
                },
                success: function (response) {
                    availableCourses = response.course_codes || [];

                    $('.course-code').each(function () {
                        const current = $(this).val();
                        $(this).empty().append('<option value="">Select Course</option>');
                        availableCourses.forEach(course => {
                            const option = new Option(course.course_code, course.course_code, false, course.course_code === current);
                            $(option).attr('data-cross', course.cross_catering ? 1 : 0);
                            $(this).append(option);
                        });
                        $(this).trigger('change');
                    });
                }
            });
        }

        $('#generate_faculty_id, #generate_setup_id').on('change', function () {
            const facultyId = $('#generate_faculty_id').val();
            const setupId = $('#generate_setup_id').val();

            if (!facultyId || !setupId) return;

            loadGenerateCourses();

           $('.group-selection').each(function () {
                let val = $(this).val();

                if (!Array.isArray(val)) {
                    $(this).val(['All Groups']);
                }
            });

            maybeLoadSetupDecision();
        });

        $('#confirmSetupDecisionBtn').on('click', function () {
            const mode = $('#generation_mode').val();
            if (!mode) {
                Swal.fire('Required', 'Please choose a handling mode first.', 'warning');
                return;
            }

            $('#generation_mode_hidden').val(mode);
            $('#setupDecisionModal').modal('hide');
        });

        $(document).on('change', '.course-code', function () {
            const courseCode = $(this).val();
            const $courseSelection = $(this).closest('.course-selection');
            const $userSelect = $courseSelection.find('.lecturer-select');

            const isCross = String($(this).find(':selected').data('cross')) === '1';
            $courseSelection.find('.tt-cross-note').first().toggle(isCross);

            $userSelect.empty().append('<option value="">Select User</option>').trigger('change');

            if (!courseCode) return;

            $.ajax({
                url: '{{ route('timetables.getLecturers') }}',
                method: 'GET',
                data: { course_code: courseCode },
                success: function (response) {
                    (response.lecturers || []).forEach(user => {
                        $userSelect.append(new Option(user.name, user.id));
                    });
                    $userSelect.trigger('change');
                }
            });
        });

        $(document).on('click', '.add-timetable', function () {
            const facultyId = $(this).data('faculty');
            const day = $(this).data('day');
            const time = $(this).data('time');
            const setupId = $(this).data('setup');

            if (!facultyId) {
                showAlert('error', 'Error', 'Select a faculty first.');
                return;
            }

            $('#modal_faculty_id').val(facultyId);
            $('#modal_setup_id').val(setupId || $('#setup_id').val() || $('#setup_id_filter').val() || '');
            $('#modal_day').val(day);
            $('#modal_time_start').val(time);

            $('#modal_time_end').empty().append('<option value="">Select End Time</option>');

            const startIndex = timeSlots.indexOf(time);
            const endOptions = timeSlots.slice(startIndex + 1);

            endOptions.forEach(slot => {
                $('#modal_time_end').append(new Option(slot, slot));
            });

            if (endOptions.length > 0) {
                $('#modal_time_end').val(endOptions[0]).trigger('change');
            } else {
                $('#modal_time_end').val('').trigger('change');
            }

            $.ajax({
                url: '{{ route('timetables.getCourses') }}',
                method: 'GET',
                data: {
                    faculty_id: facultyId,
                    setup_id: $('#modal_setup_id').val()
                },
                success: function (response) {
                    $('#modal_course_code').empty().append('<option value="">Select Course Code</option>');
                    (response.course_codes || []).forEach(course => {
                        $('#modal_course_code').append(
                            $('<option>', {
                                value: course.course_code,
                                text: course.course_code
                            }).attr('data-cross', course.cross_catering ? 1 : 0)
                        );
                    });
                    $('#modal_course_code').trigger('change');
                }
            });

            loadGenerateGroups(
                facultyId,
                $('#modal_group_selection'),
                '#addTimetableModal'
            );

            $('#modal_lecturer_id').empty().append('<option value="">Select User</option>').trigger('change');
            $('#modal_activity').val('').trigger('change');
            $('#modal_venue_id').empty().append('<option value="">Loading available venues...</option>').trigger('change');
            $('#modal_cross_note').hide();

            $('#addTimetableModal').modal('show');
        });

        function populateTimeEnd($target, startValue, selectedValue = '') {
            $target.empty().append('<option value="">Select End Time</option>');
            const startIndex = timeSlots.indexOf(startValue);
            timeSlots.slice(startIndex + 1).forEach(slot => {
                $target.append(new Option(slot, slot, false, slot === selectedValue));
            });
            $target.trigger('change');
        }

        $(document).on('change', '#modal_course_code', function () {
            const courseCode = $(this).val();
            const facultyId = $('#modal_faculty_id').val();
            const setupId = $('#modal_setup_id').val();

            $('#modal_cross_note').hide();
            $('#modal_lecturer_id').empty().append('<option value="">Select User</option>').trigger('change');

            if (!courseCode) return;

            $.ajax({
                url: '{{ route('timetables.getCourses') }}',
                method: 'GET',
                data: { faculty_id: facultyId, setup_id: setupId },
                success: function (response) {
                    const selectedCourse = (response.course_codes || []).find(c => c.course_code === courseCode);
                    if (selectedCourse && selectedCourse.cross_catering) {
                        $('#modal_cross_note').show();
                    }
                }
            });

            $.ajax({
                url: '{{ route('timetables.getLecturers') }}',
                method: 'GET',
                data: { course_code: courseCode },
                success: function (response) {
                    (response.lecturers || []).forEach(user => {
                        $('#modal_lecturer_id').append(new Option(user.name, user.id));
                    });
                    $('#modal_lecturer_id').trigger('change');
                }
            });
        });

        $(document).on('click', '.edit-timetable', function () {
            const id = $(this).data('id');


            $.ajax({
                url: '{{ route('timetable.show', ':id') }}'.replace(':id', id),
                method: 'GET',
                success: function (data) {
                    $('#editTimetableForm').attr('action', '{{ route('timetable.update', ':id') }}'.replace(':id', id));
                    $('#edit_modal_id').val(data.id);
                    $('#edit_modal_faculty_id').val(data.faculty_id);
                    $('#edit_modal_setup_id').val($('#setup_id').val() || $('#setup_id_filter').val() || '{{ $timetableSemester?->id }}');
                    $('#edit_modal_day').val(data.day);
                    $('#edit_modal_time_start').val(data.time_start);

                    populateTimeEnd($('#edit_modal_time_end'), data.time_start, data.time_end);

                    $.ajax({
                        url: '{{ route('timetables.getCourses') }}',
                        method: 'GET',
                        data: {
                            faculty_id: data.faculty_id,
                            setup_id: $('#edit_modal_setup_id').val()
                        },
                        success: function (response) {
                            $('#edit_modal_course_code').empty().append('<option value="">Select Course Code</option>');
                            (response.course_codes || []).forEach(course => {
                                $('#edit_modal_course_code').append(
                                    $('<option>', {
                                        value: course.course_code,
                                        text: course.course_code,
                                        selected: course.course_code === data.course_code
                                    }).attr('data-cross', course.cross_catering ? 1 : 0)
                                );
                            });
                            $('#edit_modal_course_code').trigger('change');
                        }
                    });

                    loadGenerateGroups(
                        data.faculty_id,
                        $('#edit_modal_group_selection'),
                        '#editTimetableModal',
                        data.group_selection_array || []
                    );

                    $.ajax({
                        url: '{{ route('timetables.getLecturers') }}',
                        method: 'GET',
                        data: { course_code: data.course_code },
                        success: function (response) {
                            $('#edit_modal_lecturer_id').empty().append('<option value="">Select User</option>');
                            (response.lecturers || []).forEach(user => {
                                $('#edit_modal_lecturer_id').append(new Option(user.name, user.id, false, String(user.id) === String(data.lecturer_id)));
                            });
                            $('#edit_modal_lecturer_id').trigger('change');
                        }
                    });

                    $('#edit_modal_activity').val(data.activity || '').trigger('change');
                    $('#edit_modal_cross_note').toggle(!!data.is_cross_catering);
                    $('#edit_modal_venue_id').data('selected-venues', data.venue_ids || []);

                    $('#editTimetableModal').modal('show');
                }
            });
        });

        $(document).on('change', '#edit_modal_course_code', function () {
            const courseCode = $(this).val();

            $('#edit_modal_cross_note').hide();
            $('#edit_modal_lecturer_id').empty().append('<option value="">Select User</option>').trigger('change');

            if (!courseCode) return;

            const selected = $(this).find(':selected');
            if (String(selected.data('cross')) === '1') {
                $('#edit_modal_cross_note').show();
            }

            $.ajax({
                url: '{{ route('timetables.getLecturers') }}',
                method: 'GET',
                data: { course_code: courseCode },
                success: function (response) {
                    (response.lecturers || []).forEach(user => {
                        $('#edit_modal_lecturer_id').append(new Option(user.name, user.id));
                    });
                    $('#edit_modal_lecturer_id').trigger('change');
                }
            });
        });

        function loadAvailableVenues(prefix, excludeId = null, selectedVenueIds = []) {
    const day = $(`#${prefix}_day`).val();
    const start = $(`#${prefix}_time_start`).val();
    const end = $(`#${prefix}_time_end`).val();
    const facultyId = $(`#${prefix}_faculty_id`).val();
    const setupId = $(`#${prefix}_setup_id`).val()
        || $('#setup_id').val()
        || $('#setup_id_filter').val()
        || $('#generate_setup_id').val()
        || '';

    const $venueSelect = $(`#${prefix}_venue_id`);
    const isMultiple = $venueSelect.prop('multiple');

    if (!day || !start || !end || !facultyId) {
        $venueSelect.empty();
        if (!isMultiple) {
            $venueSelect.append('<option value="">Select day, start and end time first</option>');
        }
        $venueSelect.trigger('change');
        return;
    }

    $.ajax({
        url: '{{ route('timetables.available-venues') }}',
        method: 'GET',
        data: {
            day: day,
            time_start: start,
            time_end: end,
            faculty_id: facultyId,
            setup_id: setupId,
            exclude_id: excludeId
        },
        success: function (response) {
            const selectedIds = (selectedVenueIds || []).map(v => String(v));

            $venueSelect.empty();

            if (!isMultiple) {
                $venueSelect.append('<option value="">Select Venue</option>');
            }

            (response.venues || []).forEach(venue => {
                const option = new Option(
                    venue.text,
                    venue.id,
                    false,
                    selectedIds.includes(String(venue.id))
                );
                $venueSelect.append(option);
            });

            if (selectedIds.length) {
                $venueSelect.val(selectedIds);
            }

            $venueSelect.trigger('change');
        },
        error: function (xhr) {
            $venueSelect.empty();

            if (!isMultiple) {
                $venueSelect.append('<option value="">No available venues</option>');
            }

            $venueSelect.trigger('change');

            const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>')
                || xhr.responseJSON?.message
                || 'Could not load available venues.';

            showAlert('error', 'Venue Loading Failed', msg);
        }
    });
}
        $(document).on('change', '#modal_time_end', function () {
            loadAvailableVenues('modal');
        });

        $(document).on('change', '#edit_modal_time_end', function () {
            loadAvailableVenues('edit_modal', $('#edit_modal_id').val());
        });

        $('#addTimetableModal').on('shown.bs.modal', function () {
            if ($('#modal_time_end').val()) {
                loadAvailableVenues('modal');
            }
        });

        $('#editTimetableModal').on('shown.bs.modal', function () {
            const selectedVenueIds = $('#edit_modal_venue_id').data('selected-venues') || [];
            loadAvailableVenues('edit_modal', $('#edit_modal_id').val(), selectedVenueIds);
        });

        $('#generateTimetableForm').on('submit', function (e) {
    e.preventDefault();

    // ✅ ADD THIS HERE (VERY IMPORTANT)
    reindexCourses();

    const $btn = $('#generateSubmitBtn');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generating...');

    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function (response) {
            if (response.proceed && response.warnings) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Warnings Found',
                    html: `<div class="text-start">${response.warnings.join('<br>')}</div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Proceed Anyway',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#4b2e83'
                }).then(result => {
                    if (result.isConfirmed) {
                        const forcedData = $('#generateTimetableForm').serialize() + '&force_proceed=1';

                        $.post($('#generateTimetableForm').attr('action'), forcedData)
                            .done(function (finalResponse) {
                                showAlert('success', 'Success', finalResponse.message || 'Timetable generated successfully.');
                                location.reload();
                            })
                            .fail(function (xhr) {
                                const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>') || 'Generation failed.';
                                showAlert('error', 'Error', msg);
                            });
                    }
                });
                return;
            }

            showAlert('success', 'Success', response.message || 'Timetable generated successfully.');
            location.reload();
        },
        error: function (xhr) {
            const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>')
                || xhr.responseJSON?.message
                || 'An error occurred while generating the timetable.';
            showAlert('error', 'Generation Failed', msg);
        },
        complete: function () {
            $btn.prop('disabled', false).html('Generate');
        }
    });
});
        $('#addTimetableForm, #editTimetableForm, #addTimetableSemesterForm, #editTimetableSemesterForm').on('submit', function (e) {
    e.preventDefault();

    const $form = $(this);
    let payload = $form.serializeArray();

    if ($form.attr('id') === 'editTimetableForm') {
        const venueValues = $('#edit_modal_venue_id').val() || [];
        payload = payload.filter(item => item.name !== 'venue_id[]' && item.name !== 'venue_id');
        payload.push({
            name: 'venue_id',
            value: venueValues.join(',')
        });
    }

    if ($form.attr('id') === 'addTimetableForm') {
        const venueValues = $('#modal_venue_id').val() || [];
        payload = payload.filter(item => item.name !== 'venue_id[]' && item.name !== 'venue_id');
        payload.push({
            name: 'venue_id',
            value: venueValues.join(',')
        });
    }

    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $.param(payload),
        success: function (response) {
            showAlert('success', 'Success', response.message || 'Operation completed successfully.');
            $('.modal').modal('hide');
            location.reload();
        },
        error: function (xhr) {
            const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>')
                || xhr.responseJSON?.message
                || 'Operation failed.';
            showAlert('error', 'Error', msg);
        }
    });
});
        $(document).on('click', '.show-timetable', function () {
            const id = $(this).data('id');

            $.ajax({
                url: '{{ route('timetable.show', ':id') }}'.replace(':id', id),
                method: 'GET',
                success: function (data) {
                    $('#show_course_code').text(data.course_code || 'N/A');
                    $('#show_course_name').text(data.course_name || 'N/A');
                    $('#show_activity').text(data.activity || 'N/A');
                    $('#show_day').text(data.day || 'N/A');
                    $('#show_time_start').text(data.time_start || 'N/A');
                    $('#show_time_end').text(data.time_end || 'N/A');
                    $('#show_venue').text((data.venue_names || []).join(', ') || 'N/A');
                    $('#show_groups').text(data.group_selection || 'N/A');
                    $('#show_group_details').text(data.group_details || 'N/A');
                    $('#show_lecturer').text(data.lecturer_name || 'N/A');
                    $('#show_faculty').text(data.faculty_name || faculties[data.faculty_id] || 'N/A');
                    $('#show_timetable_semester').text(data.semester_name || 'N/A');
                    $('#show_cross_mode').text(data.is_cross_catering ? 'Yes' : 'No');
                    $('#show_cross_related_count').text(data.cross_related_count ?? 1);

                    const crossFaculties = data.cross_faculties || [];
                    const collisions = data.collisions || {};

                    let hasCross = data.is_cross_catering && crossFaculties.length > 0;
                    let hasCollisions =
                        (collisions.lecturer || []).length > 0 ||
                        (collisions.faculty || []).length > 0 ||
                        (collisions.group || []).length > 0 ||
                        (collisions.venue || []).length > 0;

                    $('#cross_faculties_card').hide();
                    $('#collision_card').hide();
                    $('#no_extra_info_card').hide();

     function renderRowCards(items, typeLabel = '') {
    if (!items || !items.length) {
        return '<div class="text-muted">No details found</div>';
    }

    return items.map(item => `
        <div class="border rounded p-2 mb-2 bg-light d-flex justify-content-between align-items-start">
            <div>
                <div><strong>${item.course_code}</strong> - ${item.course_name ?? 'N/A'}</div>
                <div><strong>Faculty:</strong> ${item.faculty_name ?? 'N/A'}</div>
                <div><strong>Assigned User:</strong> ${item.assigned_user ?? 'N/A'}</div>
                <div><strong>Groups:</strong> ${item.groups ?? 'N/A'}</div>
                <div><strong>Venue(s):</strong> ${(item.venue_names || []).join(', ') || 'N/A'}</div>
                <div><strong>Time:</strong> ${item.time_start ?? ''} - ${item.time_end ?? ''}</div>
                <div><strong>Activity:</strong> ${item.activity ?? 'N/A'}</div>
            </div>

            <div class="ms-2 d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary auto-resolve-btn"
                        data-id="${item.id}"
                        title="Auto resolve this collision">
                    <i class="bi bi-magic"></i>
                </button>

                <button class="btn btn-sm btn-outline-danger delete-collision-btn"
                        data-id="${item.id}"
                        title="Delete this conflicting session">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </div>
        </div>
    `).join('');
}


                    if (hasCross) {
                        const crossHtml = crossFaculties.map(item => `
                            <div class="border rounded p-2 mb-2 bg-light">
                                <div><strong>Faculty:</strong> ${item.faculty_name}</div>
                                <div><strong>Assigned User:</strong> ${item.assigned_user}</div>
                                <div><strong>Groups:</strong> ${item.groups}</div>
                                <div><strong>Venue(s):</strong> ${(item.venue_names || []).join(', ') || 'N/A'}</div>
                                <div><strong>Time:</strong> ${item.time_start} - ${item.time_end}</div>
                                <div><strong>Activity:</strong> ${item.activity}</div>
                            </div>
                        `).join('');

                        $('#show_cross_faculties').html(crossHtml);
                        $('#cross_faculties_card').show();
                    }

                    $('#show_collision_lecturer').html(renderRowCards(collisions.lecturer));
                    $('#show_collision_faculty').html(renderRowCards(collisions.faculty));
                    $('#show_collision_group').html(renderRowCards(collisions.group));
                    $('#show_collision_venue').html(renderRowCards(collisions.venue));

                    if (hasCollisions) {
                        $('#collision_card').show();
                    }

                    if (!hasCross && !hasCollisions) {
                        $('#no_extra_info_card').show();
                    }

                    $('#showTimetableModal').modal('show');
                }
            });
        });

        $(document).on('click', '.auto-resolve-btn', function () {
    const id = $(this).data('id');

    Swal.fire({
        icon: 'question',
        title: 'Auto-resolve this collision?',
        text: 'The system will try to move this timetable to the nearest valid free slot.',
        showCancelButton: true,
        confirmButtonText: 'Resolve',
        confirmButtonColor: '#0d6efd'
    }).then(result => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: `{{ url('timetable') }}/${id}/auto-resolve`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                setup_id: $('#setup_id').val() || $('#setup_id_filter').val() || ''
            },
            success: function (response) {
                Swal.fire('Resolved', response.message || 'Collision resolved successfully.', 'success')
                    .then(() => location.reload());
            },
            error: function (xhr) {
                const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>')
                    || xhr.responseJSON?.message
                    || 'Auto-resolve failed.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });
});

        $(document).on('submit', '.delete-timetable-form', function (e) {
            e.preventDefault();

            const form = this;

            Swal.fire({
                icon: 'warning',
                title: 'Delete this timetable entry?',
                text: 'This action cannot be undone.',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: $(form).attr('action'),
                    method: 'POST',
                    data: $(form).serialize(),
                    success: function (response) {
                        showAlert('success', 'Deleted', response.message || 'Timetable entry deleted.');
                        location.reload();
                    },
                    error: function (xhr) {
                        const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>')
                            || xhr.responseJSON?.message
                            || 'Delete failed.';
                        showAlert('error', 'Error', msg);
                    }
                });
            });
        });
    });
</script>
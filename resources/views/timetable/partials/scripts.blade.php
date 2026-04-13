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

        function initSelect2Element(element, modalSelector = null) {
            const $el = $(element);
            if (!$el.length) return;

            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }

            const isMultiple = $el.prop('multiple');

            $el.select2({
                theme: 'classic',
                dropdownParent: modalSelector ? $(modalSelector) : $('body'),
                width: 'style',
                placeholder: $el.attr('placeholder') || 'Select an option',
                allowClear: !isMultiple,
                closeOnSelect: !isMultiple
            });

            $el.next('.select2-container').css('width', '100%');
        }


        function initSelect2Group(selector, modalSelector = null) {
            $(selector).each(function () {
                initSelect2Element(this, modalSelector);
            });
        }

        function enableModalWheelScroll(modalSelector) {
            const modalBody = document.querySelector(`${modalSelector} .modal-body`);
            if (!modalBody) return;

            $(document).off('wheel.select2modal', `${modalSelector} .select2-selection--multiple`);
            $(document).on('wheel.select2modal', `${modalSelector} .select2-selection--multiple`, function (e) {
                if (!modalBody) return;

                e.preventDefault();
                e.stopPropagation();

                modalBody.scrollTop += e.originalEvent.deltaY;
            });

            $(document).off('wheel.select2modalSingle', `${modalSelector} .select2-selection--single`);
            $(document).on('wheel.select2modalSingle', `${modalSelector} .select2-selection--single`, function (e) {
                if (!modalBody) return;

                e.preventDefault();
                e.stopPropagation();

                modalBody.scrollTop += e.originalEvent.deltaY;
            });
        }

        enableModalWheelScroll('#generateTimetableModal');
        enableModalWheelScroll('#addTimetableModal');
        enableModalWheelScroll('#editTimetableModal');

        function resetSelect2Options($select, options = [], config = {}) {
            const {
                placeholder = 'Select an option',
                selected = null,
                modalSelector = null
            } = config;

            $select.empty();

            if (!$select.prop('multiple')) {
                $select.append(new Option(placeholder, ''));
            }

            options.forEach(opt => {
                const option = new Option(opt.text, opt.value, false, false);

                if (opt.disabled) {
                    $(option).prop('disabled', true);
                }

                if (opt.attributes) {
                    Object.entries(opt.attributes).forEach(([key, value]) => {
                        $(option).attr(key, value);
                    });
                }

                $select.append(option);
            });

            initSelect2Element($select, modalSelector);

            if (selected !== null) {
                $select.val(selected).trigger('change.select2');
            } else {
                $select.val($select.prop('multiple') ? [] : '').trigger('change.select2');
            }
        }

       
        function setGenerateButtonState(loading = false) {
            const $btn = $('#generateSubmitBtn');

            if (loading) {
                $btn.prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-1"></i> Generating...');
            } else {
                $btn.prop('disabled', false)
                    .html('Generate');
            }
        }

        initSelect2Group('#setup_id');
        initSelect2Group('#setup_id_filter');
        initSelect2Group('#faculty');

        initSelect2Group('#generate_setup_id', '#generateTimetableModal');
        initSelect2Group('#generate_faculty_id', '#generateTimetableModal');
        initSelect2Group('#generate_venues', '#generateTimetableModal');

        initSelect2Group('#generateTimetableModal .course-code', '#generateTimetableModal');
        initSelect2Group('#generateTimetableModal .lecturer-select', '#generateTimetableModal');
        initSelect2Group('#generateTimetableModal .activity-select', '#generateTimetableModal');
        initSelect2Group('#generateTimetableModal .group-selection', '#generateTimetableModal');

        initSelect2Group('#modal_course_code', '#addTimetableModal');
        initSelect2Group('#modal_lecturer_id', '#addTimetableModal');
        initSelect2Group('#modal_activity', '#addTimetableModal');
        initSelect2Group('#modal_venue_id', '#addTimetableModal');
        initSelect2Group('#modal_group_selection', '#addTimetableModal');
        initSelect2Group('#modal_time_end', '#addTimetableModal');

        initSelect2Group('#edit_modal_course_code', '#editTimetableModal');
        initSelect2Group('#edit_modal_lecturer_id', '#editTimetableModal');
        initSelect2Group('#edit_modal_activity', '#editTimetableModal');
        initSelect2Group('#edit_modal_venue_id', '#editTimetableModal');
        initSelect2Group('#edit_modal_group_selection', '#editTimetableModal');
        initSelect2Group('#edit_modal_time_end', '#editTimetableModal');

        initSelect2Group('#add_semester_id', '#addTimetableSemesterModal');
        initSelect2Group('#edit_semester_id', '#editTimetableSemesterModal');

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

        $(document).on('select2:open', function () {
            setTimeout(function () {
                const $dropdown = $('.select2-container--open .select2-results__options');
                $dropdown.css({
                    'max-height': '200px',
                    'overflow-y': 'auto',
                    'overscroll-behavior': 'contain'
                });

                $dropdown.off('wheel.select2fix').on('wheel.select2fix', function (e) {
                    const el = this;
                    const delta = e.originalEvent.deltaY;
                    const atTop = el.scrollTop === 0;
                    const atBottom = el.scrollHeight - el.clientHeight - el.scrollTop <= 1;

                    if ((delta < 0 && !atTop) || (delta > 0 && !atBottom)) {
                        e.stopPropagation();
                    }
                });
            }, 0);
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
                    const options = [{
                        text: 'All Groups',
                        value: 'All Groups'
                    }];

                    (response.groups || []).forEach(group => {
                        options.push({
                            text: group.group_name,
                            value: group.group_name
                        });
                    });

                    resetSelect2Options($target, options, {
                        selected: selectedValues && selectedValues.length ? selectedValues : ['All Groups'],
                        modalSelector: modalSelector
                    });

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
                        const $select = $(this);
                        const current = $select.val();

                        const options = availableCourses.map(course => ({
                            text: `${course.course_code} - ${course.completion_text}`,
                            value: course.course_code,
                            disabled: course.is_complete && course.course_code !== current,
                            attributes: {
                                'data-cross': course.cross_catering ? 1 : 0,
                                'data-complete': course.is_complete ? 1 : 0,
                                'data-remaining': course.remaining_sessions ?? 0
                            }
                        }));

                        resetSelect2Options($select, options, {
                            placeholder: 'Select Course',
                            selected: current || '',
                            modalSelector: '#generateTimetableModal'
                        });
                    });
                }
            });
        }

        function renderGenerateCourseRow(index) {
            return `
                <div class="tt-course-selection course-selection">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <label class="form-label">Course</label>
                            <select name="courses[${index}]" class="form-control course-code" required>
                                <option value="">Select Course</option>
                                ${availableCourses.map(c => `
                                    <option value="${c.course_code}"
                                            data-cross="${c.cross_catering ? 1 : 0}"
                                            data-complete="${c.is_complete ? 1 : 0}"
                                            data-remaining="${c.remaining_sessions ?? 0}"
                                            ${c.is_complete ? 'disabled' : ''}>
                                        ${c.course_code} - ${c.completion_text}
                                    </option>
                                `).join('')}
                            </select>

                            <div class="tt-cross-note" style="display:none;">
                                Cross-catering course detected. Generation will apply shared session logic.
                            </div>

                            <div class="tt-course-complete-note text-danger" style="display:none;">
                                Lecture sessions for this course are already complete. Practical and Workshop can still be added manually.
                            </div>
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
                </div>
            `;
        }

        $('#add-course').on('click', function () {
            const index = $('.course-selection').length;

            $('#course-selections').append(renderGenerateCourseRow(index));

            const $row = $('#course-selections .course-selection:last');

            initSelect2Element($row.find('.course-code'), '#generateTimetableModal');
            initSelect2Element($row.find('.lecturer-select'), '#generateTimetableModal');
            initSelect2Element($row.find('.activity-select'), '#generateTimetableModal');
            initSelect2Element($row.find('.group-selection'), '#generateTimetableModal');

            const facultyId = $('#generate_faculty_id').val();
            if (facultyId) {
                loadGenerateGroups(
                    facultyId,
                    $row.find('.group-selection'),
                    '#generateTimetableModal',
                    ['All Groups']
                );
            }

            setTimeout(function () {
                const modalBody = document.querySelector('#generateTimetableModal .modal-body');
                if (modalBody) {
                    modalBody.scrollTo({
                        top: modalBody.scrollHeight,
                        behavior: 'smooth'
                    });
                }

                $('#generateTimetableModal').trigger('focus');
            }, 50);


        });

        $(document).on('click', '.remove-course', function () {
            if ($('.course-selection').length > 1) {
                $(this).closest('.course-selection').remove();
                reindexCourses();
            }
        });

        $('#generate_faculty_id, #generate_setup_id').on('change', function () {
            const facultyId = $('#generate_faculty_id').val();
            const setupId = $('#generate_setup_id').val();

            if (!facultyId || !setupId) return;

            loadGenerateCourses();

            $('.group-selection').each(function () {
                let val = $(this).val();
                if (!Array.isArray(val) || !val.length) {
                    $(this).val(['All Groups']).trigger('change.select2');
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

        $(document).on('change', '.course-code, .activity-select', function () {
            const $courseSelection = $(this).closest('.course-selection');
            const $courseSelect = $courseSelection.find('.course-code');
            const $userSelect = $courseSelection.find('.lecturer-select');

            const courseCode = $courseSelect.val();
            const selected = $courseSelect.find(':selected');
            const activity = $courseSelection.find('.activity-select').val();

            const isCross = String(selected.data('cross')) === '1';
            const isComplete = String(selected.data('complete')) === '1';
            const blocksByQuota = isComplete && String(activity).toLowerCase() === 'lecture';

            $courseSelection.find('.tt-cross-note').first().toggle(isCross);
            $courseSelection.find('.tt-course-complete-note').first().toggle(blocksByQuota);

            resetSelect2Options($userSelect, [], {
                placeholder: 'Select User',
                selected: '',
                modalSelector: '#generateTimetableModal'
            });

            if (!courseCode) return;

            if (blocksByQuota) {
                resetSelect2Options($userSelect, [{
                    text: 'Lecture sessions already complete',
                    value: '',
                    disabled: false
                }], {
                    placeholder: 'Select User',
                    selected: '',
                    modalSelector: '#generateTimetableModal'
                });
                return;
            }

            $.ajax({
                url: '{{ route('timetables.getLecturers') }}',
                method: 'GET',
                data: {
                    course_code: courseCode,
                    setup_id: $('#generate_setup_id').val()
                        || $('#modal_setup_id').val()
                        || $('#edit_modal_setup_id').val()
                        || $('#setup_id').val()
                        || $('#setup_id_filter').val()
                        || ''
                },
                success: function (response) {
                    const options = (response.lecturers || []).map(user => ({
                        text: user.name,
                        value: user.id
                    }));

                    resetSelect2Options($userSelect, options, {
                        placeholder: 'Select User',
                        selected: '',
                        modalSelector: '#generateTimetableModal'
                    });
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
                    const options = (response.course_codes || []).map(course => ({
                        text: `${course.course_code} - ${course.completion_text}`,
                        value: course.course_code,
                        disabled: !!course.is_complete,
                        attributes: {
                            'data-cross': course.cross_catering ? 1 : 0,
                            'data-complete': course.is_complete ? 1 : 0,
                            'data-remaining': course.remaining_sessions ?? 0
                        }
                    }));

                    resetSelect2Options($('#modal_course_code'), options, {
                        placeholder: 'Select Course Code',
                        selected: '',
                        modalSelector: '#addTimetableModal'
                    });
                }
            });

            loadGenerateGroups(
                facultyId,
                $('#modal_group_selection'),
                '#addTimetableModal'
            );

            resetSelect2Options($('#modal_lecturer_id'), [], {
                placeholder: 'Select User',
                selected: '',
                modalSelector: '#addTimetableModal'
            });

            $('#modal_activity').val('').trigger('change.select2');

            resetSelect2Options($('#modal_venue_id'), [{
                text: 'Loading available venues...',
                value: ''
            }], {
                placeholder: 'Select Venue',
                selected: '',
                modalSelector: '#addTimetableModal'
            });

            $('#modal_cross_note').hide();

            $('#addTimetableModal').modal('show');
        });

        function populateTimeEnd($target, startValue, selectedValue = '') {
            $target.empty().append('<option value="">Select End Time</option>');
            const startIndex = timeSlots.indexOf(startValue);
            timeSlots.slice(startIndex + 1).forEach(slot => {
                $target.append(new Option(slot, slot, false, slot === selectedValue));
            });

            initSelect2Element($target, $target.closest('.modal').length ? `#${$target.closest('.modal').attr('id')}` : null);
            $target.val(selectedValue || '').trigger('change.select2');
        }

        $(document).on('change', '#modal_course_code', function () {
            const courseCode = $(this).val();
            const facultyId = $('#modal_faculty_id').val();
            const setupId = $('#modal_setup_id').val();

            $('#modal_cross_note').hide();

            resetSelect2Options($('#modal_lecturer_id'), [], {
                placeholder: 'Select User',
                selected: '',
                modalSelector: '#addTimetableModal'
            });

            if (!courseCode) return;

            $.ajax({
                url: '{{ route('timetables.getCourses') }}',
                method: 'GET',
                data: { faculty_id: facultyId, setup_id: setupId },
                success: function (response) {
                    const selectedCourse = (response.course_codes || []).find(c => c.course_code === courseCode);
                    if (selectedCourse && selectedCourse.cross_catering) {
                        const isWorkshop = String($('#modal_activity').val() || '').toLowerCase() === 'workshop';

                        if (isWorkshop) {
                            $('#modal_cross_note')
                                .text('Cross-catering workshop will be stored as a single row only.')
                                .show();
                        } else {
                            $('#modal_cross_note')
                                .text('Cross-catering non-workshop may attach to an existing shared slot or create linked rows for related faculties.')
                                .show();
                        }
                    }
                }
            });

            $.ajax({
                url: '{{ route('timetables.getLecturers') }}',
                method: 'GET',
                data: {
                    course_code: courseCode,
                    setup_id: $('#generate_setup_id').val() || $('#modal_setup_id').val() || $('#edit_modal_setup_id').val() || $('#setup_id').val() || $('#setup_id_filter').val() || ''
                },
                success: function (response) {
                    const options = (response.lecturers || []).map(user => ({
                        text: user.name,
                        value: user.id
                    }));

                    resetSelect2Options($('#modal_lecturer_id'), options, {
                        placeholder: 'Select User',
                        selected: '',
                        modalSelector: '#addTimetableModal'
                    });
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
                            const options = (response.course_codes || []).map(course => ({
                                text: course.course_code,
                                value: course.course_code,
                                attributes: {
                                    'data-cross': course.cross_catering ? 1 : 0
                                }
                            }));

                            resetSelect2Options($('#edit_modal_course_code'), options, {
                                placeholder: 'Select Course Code',
                                selected: data.course_code,
                                modalSelector: '#editTimetableModal'
                            });
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
                        data: {
                            course_code: data.course_code,
                            setup_id: $('#generate_setup_id').val() || $('#modal_setup_id').val() || $('#edit_modal_setup_id').val() || $('#setup_id').val() || $('#setup_id_filter').val() || ''
                        },
                        success: function (response) {
                            const options = (response.lecturers || []).map(user => ({
                                text: user.name,
                                value: user.id
                            }));

                            resetSelect2Options($('#edit_modal_lecturer_id'), options, {
                                placeholder: 'Select User',
                                selected: String(data.lecturer_id),
                                modalSelector: '#editTimetableModal'
                            });
                        }
                    });

                    $('#edit_modal_activity').val(data.activity || '').trigger('change.select2');
                    $('#edit_modal_cross_note').toggle(!!data.is_cross_catering);
                    $('#edit_modal_venue_id').data('selected-venues', data.venue_ids || []);

                    $('#editTimetableModal').modal('show');
                }
            });
        });

        const isCross = !!data.is_cross_catering;
        const isWorkshop = String(data.activity || '').toLowerCase() === 'workshop';

        if (isCross && !isWorkshop) {
            Swal.fire({
                icon: 'info',
                title: 'Cross-catering edit',
                text: 'Editing this non-workshop cross-catering session will update all linked rows.',
                timer: 2500,
                showConfirmButton: false
            });
        } else if (isCross && isWorkshop) {
            Swal.fire({
                icon: 'info',
                title: 'Workshop edit',
                text: 'This cross-catering workshop will be edited as a single row only.',
                timer: 2500,
                showConfirmButton: false
            });
        }

        $(document).on('change', '#edit_modal_course_code', function () {
            const courseCode = $(this).val();

            $('#edit_modal_cross_note').hide();

            resetSelect2Options($('#edit_modal_lecturer_id'), [], {
                placeholder: 'Select User',
                selected: '',
                modalSelector: '#editTimetableModal'
            });

            if (!courseCode) return;

            const selected = $(this).find(':selected');
            if (String(selected.data('cross')) === '1') {
                $('#edit_modal_cross_note').show();
            }

            $.ajax({
                url: '{{ route('timetables.getLecturers') }}',
                method: 'GET',
                data: {
                    course_code: courseCode,
                    setup_id: $('#generate_setup_id').val() || $('#modal_setup_id').val() || $('#edit_modal_setup_id').val() || $('#setup_id').val() || $('#setup_id_filter').val() || ''
                },
                success: function (response) {
                    const options = (response.lecturers || []).map(user => ({
                        text: user.name,
                        value: user.id
                    }));

                    resetSelect2Options($('#edit_modal_lecturer_id'), options, {
                        placeholder: 'Select User',
                        selected: '',
                        modalSelector: '#editTimetableModal'
                    });
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
                initSelect2Element($venueSelect, $venueSelect.closest('.modal').length ? `#${$venueSelect.closest('.modal').attr('id')}` : null);
                $venueSelect.trigger('change.select2');
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
                    const modalSelector = $venueSelect.closest('.modal').length ? `#${$venueSelect.closest('.modal').attr('id')}` : null;
                    const options = (response.venues || []).map(venue => ({
                        text: venue.text,
                        value: venue.id
                    }));

                    resetSelect2Options($venueSelect, options, {
                        placeholder: 'Select Venue',
                        selected: selectedIds.length ? selectedIds : (isMultiple ? [] : ''),
                        modalSelector: modalSelector
                    });
                },
                error: function (xhr) {
                    $venueSelect.empty();

                    if (!isMultiple) {
                        $venueSelect.append('<option value="">No available venues</option>');
                    }

                    initSelect2Element($venueSelect, $venueSelect.closest('.modal').length ? `#${$venueSelect.closest('.modal').attr('id')}` : null);
                    $venueSelect.trigger('change.select2');

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

            reindexCourses();
            setGenerateButtonState(true);

            const completedSelections = [];

            $('.course-selection').each(function () {
                const $row = $(this);
                const selected = $row.find('.course-code option:selected');
                const activity = $row.find('.activity-select').val();

                if (
                    String(selected.data('complete')) === '1' &&
                    String(activity).toLowerCase() === 'lecture'
                ) {
                    completedSelections.push(selected.val());
                }
            });

            if (completedSelections.length) {
                setGenerateButtonState(false);
                showAlert(
                    'error',
                    'Completed Course Selected',
                    'One or more selected courses already have all required lecture sessions in this setup. Remove them or change activity.'
                );
                return;
            }

            const formAction = $(this).attr('action');
            const formData = $(this).serialize();

            $.ajax({
                url: formAction,
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.proceed && response.warnings) {
                        setGenerateButtonState(false);

                        Swal.fire({
                            icon: 'warning',
                            title: 'Warnings Found',
                            html: `<div class="text-start">${response.warnings.join('<br>')}</div>`,
                            showCancelButton: true,
                            confirmButtonText: 'Proceed Anyway',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#4b2e83'
                        }).then(result => {
                            if (!result.isConfirmed) return;

                            setGenerateButtonState(true);

                            $.post(formAction, formData + '&force_proceed=1')
                                .done(function (finalResponse) {
                                    showAlert('success', 'Success', finalResponse.message || 'Timetable generated successfully.');
                                    location.reload();
                                })
                                .fail(function (xhr) {
                                    const msg = Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>')
                                        || xhr.responseJSON?.message
                                        || 'Generation failed.';
                                    showAlert('error', 'Error', msg);
                                })
                                .always(function () {
                                    setGenerateButtonState(false);
                                });
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
                    setGenerateButtonState(false);
                }
            });
        });

          $('#addTimetableForm, #editTimetableForm, #addTimetableSemesterForm, #editTimetableSemesterForm').on('submit', function (e) {
    e.preventDefault();

    const $form = $(this);
    let payload = $form.serializeArray();

    if ($form.attr('id') === 'editTimetableForm') {
        let venueValues = $('#edit_modal_venue_id').val();

        if (Array.isArray(venueValues)) {
            venueValues = venueValues.join(',');
        } else {
            venueValues = venueValues ? String(venueValues) : '';
        }

        payload = payload.filter(item => item.name !== 'venue_id[]' && item.name !== 'venue_id');
        payload.push({
            name: 'venue_id',
            value: venueValues
        });
    }

    if ($form.attr('id') === 'addTimetableForm') {
        let venueValues = $('#modal_venue_id').val();

        if (Array.isArray(venueValues)) {
            venueValues = venueValues.join(',');
        } else {
            venueValues = venueValues ? String(venueValues) : '';
        }

        payload = payload.filter(item => item.name !== 'venue_id[]' && item.name !== 'venue_id');
        payload.push({
            name: 'venue_id',
            value: venueValues
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

                    function renderRowCards(items) {
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

        $(document).on('select2:open', () => {
            document.querySelectorAll('.select2-container--open .select2-search__field').forEach((input) => {
                input.focus();
            });
        });


        $(document).on('submit', '.delete-timetable-form', function (e) {
    e.preventDefault();

    const form = this;
    const isCross = String($(form).data('cross')) === '1';
    const isWorkshop = String($(form).data('workshop')) === '1';

    let title = 'Delete this timetable entry?';
    let text = 'This action cannot be undone.';

    if (isCross && !isWorkshop) {
        title = 'Delete linked cross-catering session?';
        text = 'All linked non-workshop cross-catering rows for this shared slot will be deleted.';
    } else if (isCross && isWorkshop) {
        title = 'Delete workshop row?';
        text = 'Only this workshop row will be deleted.';
    }

    Swal.fire({
        icon: 'warning',
        title: title,
        text: text,
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
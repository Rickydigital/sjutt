@extends('components.app-main-layout')

@section('styles')
    <style>
        .timetable-table { table-layout: fixed; width: 100%; }
        .timetable-table th, .timetable-table td { border: 2px solid #dee2e6 !important; vertical-align: middle; text-align: center; padding: 8px; }
        .timetable-table th { background: linear-gradient(135deg, #6f42c1, #4B2E83); color: white; font-weight: 600; }
        .timetable-table .empty-cell { height: 80px; background-color: #f8f9fa; transition: background-color 0.3s; }
        .timetable-table .empty-cell:hover { background-color: #e9ecef; }
        .timetable-table .event-cell { background: linear-gradient(135deg, #e2e8f0, #f8f9fa); }
        .card-header { background: linear-gradient(135deg, #6f42c1, #4B2E83); color: white; border-radius: 10px 10px 0 0; }
        .week-end { border-bottom: 3px solid #343a40 !important; }
        .month-end { border-bottom: 5px solid #343a40 !important; }
        .month-table { margin-bottom: 2rem; }
        .action-icon { cursor: pointer; margin: 0 5px; }
    </style>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="fw-bold" style="color: #4B2E83;">
                <i class="fas fa-clock me-2"></i> Academic and Meeting Calendar
            </h1>
            <div>
                @if($setup)
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#setupModal">
                        <i class="fas fa-cog"></i> Setup
                    </button>
                    <a href="{{ route('calendar.export') }}" class="btn btn-success">
                        <i class="fas fa-download"></i> Export to PDF
                    </a>
                @else
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#setupModal">
                        <i class="fas fa-cog"></i> Setup Calendar
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if(!$setup)
        <div class="alert alert-warning text-center">
            No calendar setup found. Please configure the calendar using the Setup button.
        </div>
    @else
        @foreach($calendarData as $monthData)
            <div class="row month-table">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0 text-white">{{ $monthData['month'] }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table timetable-table mb-0">
                                    <thead>
                                        <tr class="table-primary">
                                            <th scope="col">Month</th>
                                            <th scope="col" colspan="5">Week Number</th>
                                            <th scope="col">Days</th>
                                            <th scope="col">Academic Calendar</th>
                                            <th scope="col">Meeting/Activities Calendar</th>
                                        </tr>
                                        <tr class="table-secondary">
                                            <th scope="col"></th>
                                            <th scope="col">Degree Health</th>
                                            <th scope="col">Degree Non-Health</th>
                                            <th scope="col">Non-Degree Non-Health</th>
                                            <th scope="col">Non-Degree Health</th>
                                            <th scope="col">Masters</th>
                                            <th scope="col"></th>
                                            <th scope="col"></th>
                                            <th scope="col"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $currentWeek = null; $weekDays = []; @endphp
                                        @foreach($monthData['days'] as $index => $day)
                                            @if($day['weekNumber'] !== $currentWeek)
                                                @php
                                                    $currentWeek = $day['weekNumber'];
                                                    $weekDays = array_filter($monthData['days'], fn($d) => $d['weekNumber'] === $currentWeek);
                                                    $rowSpanCount = count($weekDays);
                                                @endphp
                                                <tr class="{{ $day['isWeekEnd'] ? 'week-end' : '' }} {{ $day['isMonthEnd'] ? 'month-end' : '' }}">
                                                    @if($index === 0)
                                                        <td rowspan="{{ count($monthData['days']) }}" class="fw-bold">{{ $monthData['month'] }}</td>
                                                    @endif
                                                    <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Degree Health'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Degree Health'] }}</td>
                                                    <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Degree Non-Health'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Degree Non-Health'] }}</td>
                                                    <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Non-Degree Non-Health'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Non-Degree Non-Health'] }}</td>
                                                    <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Non-Degree Health'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Non-Degree Health'] }}</td>
                                                    <td rowspan="{{ $rowSpanCount }}" class="{{ $day['events']['Masters'] ? 'event-cell' : 'empty-cell' }}">{{ $day['events']['Masters'] }}</td>
                                                    <td>{{ $day['dayName'] }} {{ $day['dayNumber'] }}</td>
                                                    <td class="{{ $day['events']['Academic Calendar'] ? 'event-cell' : 'empty-cell' }}">
                                                        @if($day['events']['Academic Calendar'])
                                                            {{ $day['events']['Academic Calendar'] }}
                                                            @if($day['eventIds']['Academic Calendar'])
                                                                <i class="fas fa-edit action-icon edit-event" data-event-id="{{ $day['eventIds']['Academic Calendar'] }}" data-date="{{ $day['date'] }}" data-category="Academic Calendar" data-description="{{ $day['events']['Academic Calendar'] }}"></i>
                                                                <i class="fas fa-trash action-icon delete-event" data-event-id="{{ $day['eventIds']['Academic Calendar'] }}"></i>
                                                            @endif
                                                        @else
                                                            <i class="fas fa-plus action-icon add-event" data-date="{{ $day['date'] }}" data-category="Academic Calendar"></i>
                                                        @endif
                                                    </td>
                                                    <td class="{{ $day['events']['Meeting/Activities Calendar'] ? 'event-cell' : 'empty-cell' }}">
                                                        @if($day['events']['Meeting/Activities Calendar'])
                                                            {{ $day['events']['Meeting/Activities Calendar'] }}
                                                            @if($day['eventIds']['Meeting/Activities Calendar'])
                                                                <i class="fas fa-edit action-icon edit-event" data-event-id="{{ $day['eventIds']['Meeting/Activities Calendar'] }}" data-date="{{ $day['date'] }}" data-category="Meeting/Activities Calendar" data-description="{{ $day['events']['Meeting/Activities Calendar'] }}"></i>
                                                                <i class="fas fa-trash action-icon delete-event" data-event-id="{{ $day['eventIds']['Meeting/Activities Calendar'] }}"></i>
                                                            @endif
                                                        @else
                                                            <i class="fas fa-plus action-icon add-event" data-date="{{ $day['date'] }}" data-category="Meeting/Activities Calendar"></i>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @else
                                                <tr class="{{ $day['isWeekEnd'] ? 'week-end' : '' }} {{ $day['isMonthEnd'] ? 'month-end' : '' }}">
                                                    <td>{{ $day['dayName'] }} {{ $day['dayNumber'] }}</td>
                                                    <td class="{{ $day['events']['Academic Calendar'] ? 'event-cell' : 'empty-cell' }}">
                                                        @if($day['events']['Academic Calendar'])
                                                            {{ $day['events']['Academic Calendar'] }}
                                                            @if($day['eventIds']['Academic Calendar'])
                                                                <i class="fas fa-edit action-icon edit-event" data-event-id="{{ $day['eventIds']['Academic Calendar'] }}" data-date="{{ $day['date'] }}" data-category="Academic Calendar" data-description="{{ $day['events']['Academic Calendar'] }}"></i>
                                                                <i class="fas fa-trash action-icon delete-event" data-event-id="{{ $day['eventIds']['Academic Calendar'] }}"></i>
                                                            @endif
                                                        @else
                                                            <i class="fas fa-plus action-icon add-event" data-date="{{ $day['date'] }}" data-category="Academic Calendar"></i>
                                                        @endif
                                                    </td>
                                                    <td class="{{ $day['events']['Meeting/Activities Calendar'] ? 'event-cell' : 'empty-cell' }}">
                                                        @if($day['events']['Meeting/Activities Calendar'])
                                                            {{ $day['events']['Meeting/Activities Calendar'] }}
                                                            @if($day['eventIds']['Meeting/Activities Calendar'])
                                                                <i class="fas fa-edit action-icon edit-event" data-event-id="{{ $day['eventIds']['Meeting/Activities Calendar'] }}" data-date="{{ $day['date'] }}" data-category="Meeting/Activities Calendar" data-description="{{ $day['events']['Meeting/Activities Calendar'] }}"></i>
                                                                <i class="fas fa-trash action-icon delete-event" data-event-id="{{ $day['eventIds']['Meeting/Activities Calendar'] }}"></i>
                                                            @endif
                                                        @else
                                                            <i class="fas fa-plus action-icon add-event" data-date="{{ $day['date'] }}" data-category="Meeting/Activities Calendar"></i>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    <!-- Setup Modal -->
    <div class="modal fade" id="setupModal" tabindex="-1" aria-labelledby="setupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="setupModalLabel">{{ $setup ? 'Edit Calendar Setup' : 'Create Calendar Setup' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="setupForm">
                        @csrf
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $setup ? $setup->start_date : '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $setup ? $setup->end_date : '' }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ $setup ? 'Update' : 'Create' }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Create Modal -->
    <div class="modal fade" id="eventCreateModal" tabindex="-1" aria-labelledby="eventCreateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventCreateModalLabel">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="eventCreateForm">
                        @csrf
                        <div class="mb-3">
                            <label for="event_date" class="form-label">Event Date</label>
                            <input type="date" class="form-control" id="event_date" name="event_date" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="event_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="event_description" name="event_description">
                        </div>
                        <div class="mb-3">
                            <label for="custom_week_number" class="form-label">Week Number (Optional)</label>
                            <input type="number" class="form-control" id="custom_week_number" name="custom_week_number" min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Week Number to Programs</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="all_programs" name="programs[]" value="All Programs">
                                <label class="form-check-label" for="all_programs">All Programs</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="degree_health" name="programs[]" value="Degree Health">
                                <label class="form-check-label" for="degree_health">Degree Health</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="degree_non_health" name="programs[]" value="Degree Non-Health">
                                <label class="form-check-label" for="degree_non_health">Degree Non-Health</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="non_degree_non_health" name="programs[]" value="Non-Degree Non-Health">
                                <label class="form-check-label" for="non_degree_non_health">Non-Degree Non-Health</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="non_degree_health" name="programs[]" value="Non-Degree Health">
                                <label class="form-check-label" for="non_degree_health">Non-Degree Health</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="masters" name="programs[]" value="Masters">
                                <label class="form-check-label" for="masters">Masters</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Edit Modal -->
    <div class="modal fade" id="eventEditModal" tabindex="-1" aria-labelledby="eventEditModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventEditModalLabel">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="eventEditForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="event_id" name="event_id">
                        <div class="mb-3">
                            <label for="edit_event_date" class="form-label">Event Date</label>
                            <input type="date" class="form-control" id="edit_event_date" name="event_date" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="edit_category" name="category" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_event_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="edit_event_description" name="event_description">
                        </div>
                        <div class="mb-3">
                            <label for="edit_custom_week_number" class="form-label">Week Number (Optional)</label>
                            <input type="number" class="form-control" id="edit_custom_week_number" name="custom_week_number" min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Week Number to Programs</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_all_programs" name="programs[]" value="All Programs">
                                <label class="form-check-label" for="edit_all_programs">All Programs</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_degree_health" name="programs[]" value="Degree Health">
                                <label class="form-check-label" for="edit_degree_health">Degree Health</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_degree_non_health" name="programs[]" value="Degree Non-Health">
                                <label class="form-check-label" for="edit_degree_non_health">Degree Non-Health</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_non_degree_non_health" name="programs[]" value="Non-Degree Non-Health">
                                <label class="form-check-label" for="edit_non_degree_non_health">Non-Degree Non-Health</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_non_degree_health" name="programs[]" value="Non-Degree Health">
                                <label class="form-check-label" for="edit_non_degree_health">Non-Degree Health</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_masters" name="programs[]" value="Masters">
                                <label class="form-check-label" for="edit_masters">Masters</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Delete Modal -->
    <div class="modal fade" id="eventDeleteModal" tabindex="-1" aria-labelledby="eventDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDeleteModalLabel">Delete Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this event?</p>
                    <form id="eventDeleteForm">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" id="delete_event_id" name="event_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('.add-event').click(function () {
                $('#event_date').val($(this).data('date'));
                $('#category').val($(this).data('category'));
                $('#event_description').val('');
                $('#custom_week_number').val('');
                $('input[name="programs[]"]').prop('checked', false);
                $('#eventCreateModal').modal('show');
            });

            $('.edit-event').click(function () {
                const eventId = $(this).data('event-id');
                $.get(`/calendar/${eventId}`, function (data) {
                    $('#event_id').val(data.id);
                    $('#edit_event_date').val(data.event_date);
                    $('#edit_category').val(data.category);
                    $('#edit_event_description').val(data.event_description);
                    $('#edit_custom_week_number').val(data.custom_week_number);
                    $('input[name="programs[]"]').prop('checked', false);
                    if (data.programs) {
                        data.programs.forEach(program => {
                            $(`input[name="programs[]"][value="${program}"]`).prop('checked', true);
                        });
                    }
                    $('#eventEditModal').modal('show');
                });
            });

            $('.delete-event').click(function () {
                $('#delete_event_id').val($(this).data('event-id'));
                $('#eventDeleteModal').modal('show');
            });

            $('#eventCreateForm').submit(function (e) {
                e.preventDefault();
                $.ajax({
                    url: '{{ route("calendar.store") }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: xhr.responseJSON.error || 'Failed to create event',
                        });
                    }
                });
            });

            $('#eventEditForm').submit(function (e) {
                e.preventDefault();
                const eventId = $('#event_id').val();
                $.ajax({
                    url: `/calendar/${eventId}`,
                    method: 'PUT',
                    data: $(this).serialize(),
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: xhr.responseJSON.error || 'Failed to update event',
                        });
                    }
                });
            });

            $('#eventDeleteForm').submit(function (e) {
                e.preventDefault();
                const eventId = $('#delete_event_id').val();
                $.ajax({
                    url: `/calendar/${eventId}`,
                    method: 'DELETE',
                    data: $(this).serialize(),
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: xhr.responseJSON.error || 'Failed to delete event',
                        });
                    }
                });
            });

            $('#setupForm').submit(function (e) {
                e.preventDefault();
                const url = '{{ $setup ? route("calendar.update", $setup->id) : route("calendar.store") }}';
                const method = '{{ $setup ? "PUT" : "POST" }}';
                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: xhr.responseJSON.error || 'Failed to save setup',
                        });
                    }
                });
            });

            // Handle "All Programs" checkbox
            $('#all_programs, #edit_all_programs').change(function () {
                const isChecked = $(this).is(':checked');
                const formId = $(this).attr('id').includes('edit') ? '#eventEditForm' : '#eventCreateForm';
                $(`${formId} input[name="programs[]"]`).not(this).prop('checked', isChecked);
            });
        });
    </script>
@endsection
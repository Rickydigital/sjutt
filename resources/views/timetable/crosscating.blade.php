@extends('components.app-main-layout')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                <div>
                    <strong class="card-title">Cross-Catering Courses</strong>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if ($crossCateringCourses->isEmpty())
                <p class="text-center">No cross-catering courses found.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Faculties</th>
                                <th>Lecturers</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($crossCateringCourses as $course)
                                <tr>
                                    <td>{{ $course->course_code }}</td>
                                    <td>{{ $course->name }}</td>
                                    <td>{{ $course->faculties->pluck('name')->join(', ') }}</td>
                                    <td>{{ $course->lecturers->take(2)->pluck('name')->join(', ') }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success generate-timetable" data-bs-toggle="modal" data-bs-target="#generateModal{{ $course->id }}" title="Generate">
                                            <i class="bi bi-gear-fill"></i>
                                        </button>
                                        <a href="#" class="action-icon text-primary" data-bs-toggle="modal" data-bs-target="#showModal{{ $course->id }}" title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <a href="{{ route('courses.edit', $course) }}" class="action-icon text-primary" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </td>
                                </tr>
                                <!-- Show Modal -->
                                <div class="modal fade" id="showModal{{ $course->id }}" tabindex="-1" aria-labelledby="showModalLabel{{ $course->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="showModalLabel{{ $course->id }}">{{ $course->name }} ({{ $course->course_code }})</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Code:</strong> {{ $course->course_code }}</p>
                                                <p><strong>Name:</strong> {{ $course->name }}</p>
                                                <p><strong>Description:</strong> {{ $course->description ?: 'None' }}</p>
                                                <p><strong>Credits:</strong> {{ $course->credits }}</p>
                                                <p><strong>Hours:</strong> {{ $course->hours }}</p>
                                                <p><strong>Session:</strong> {{ $course->session }}</p>
                                                <p><strong>Faculties:</strong> {{ $course->faculties->pluck('name')->join(', ') }}</p>
                                                <p><strong>Lecturers:</strong> {{ $course->lecturers->pluck('name')->join(', ') }}</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Generate Modal -->
                                <div class="modal fade" id="generateModal{{ $course->id }}" tabindex="-1" aria-labelledby="generateModalLabel{{ $course->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="generateModalLabel{{ $course->id }}">Generate Timetable for {{ $course->course_code }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="{{ route('cross-cating-timetable.generate', $course->id) }}" class="needs-validation" novalidate>
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="venues{{ $course->id }}" class="form-label">Select Venues <span class="text-danger">*</span></label>
                                                        <select name="venues[]" id="venues{{ $course->id }}" class="form-control select2" multiple required>
                                                            @foreach ($venues as $venue)
                                                                <option value="{{ $venue->id }}">{{ $venue->name }} (Capacity: {{ $venue->capacity }})</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="invalid-feedback">
                                                            Please select at least one venue.
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Generate</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for each modal when it is shown
            $('.modal').on('shown.bs.modal', function () {
                const modalId = $(this).attr('id');
                const select2 = $(`#${modalId} .select2`);
                
                select2.select2({
                    theme: 'bootstrap-5',
                    placeholder: "Select venues",
                    allowClear: true,
                    dropdownParent: $(`#${modalId}`), // Ensure dropdown is attached to modal
                    width: '100%'
                });
            });

            // Destroy Select2 when modal is hidden to prevent overlap issues
            $('.modal').on('hidden.bs.modal', function () {
                const modalId = $(this).attr('id');
                $(`#${modalId} .select2`).select2('destroy');
            });

            // Bootstrap form validation
            (function () {
                'use strict';
                const forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();
        });
    </script>
@endsection
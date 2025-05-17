@extends('layouts.admin')

@section('content')
    <div class="content">
        <h1 class="font-weight-bold" style="color: #4B2E83;">
            <i class="fa fa-calendar mr-2"></i> Exam Schedule Setup
        </h1>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Time Slot</th>
                                @foreach ($days as $day)
                                    <th>{{ \Carbon\Carbon::parse($day)->format('l, M d') }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($timeSlots as $slot)
                                <tr>
                                    <td>{{ $slot['name'] }} ({{ $slot['start_time'] }}-{{ $slot['end_time'] }})</td>
                                    @foreach ($days as $day)
                                        <td>
                                            @foreach ($faculties as $faculty)
                                                <div class="faculty">
                                                    {{ $faculty->name }}
                                                    <a href="{{ route('timetables.create', [
                                                        'date' => $day,
                                                        'time_slot' => json_encode($slot),
                                                        'faculty_id' => $faculty->id
                                                    ]) }}" class="btn btn-sm btn-primary">
                                                        Add Exam
                                                    </a>
                                                </div>
                                            @endforeach
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('timetables.index') }}" class="btn btn-secondary mt-3">View Timetable</a>
            </div>
        </div>
    </div>
@endsection

<style>
    .card { border: none; border-radius: 10px; overflow: hidden; }
    .table th, .table td { vertical-align: middle; text-align: center; }
    .faculty { margin-bottom: 10px; }
    .btn-primary { background-color: #4B2E83; border-color: #4B2E83; }
</style>
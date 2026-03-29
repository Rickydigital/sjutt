@extends('components.app-main-layout')

@section('content')
    @php
        $currentSetup = $timetableSemester ?? null;
        $currentSemesterLabel = $currentSetup
            ? ($currentSetup->semester->name ?? 'Unknown Semester') . ' • ' . ($currentSetup->academic_year ?? 'N/A')
            : 'No active timetable semester';

        $allTimeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];

        $venueMap = collect($venues ?? [])->keyBy('id');
    @endphp

    @include('timetable.partials.hero')
    @include('timetable.partials.setup-controls')
    @include('timetable.partials.faculty-filter')
    @include('timetable.partials.timetable-grid')

    @include('timetable.partials.modals.generate')
    @include('timetable.partials.modals.add-entry')
    @include('timetable.partials.modals.edit-entry')
    @include('timetable.partials.modals.show-entry')
    @include('timetable.partials.modals.add-setup')
    @include('timetable.partials.modals.edit-setup')
    @include('timetable.partials.modals.setup-decision')
    @include('timetable.partials.modals.import')
@endsection

@section('scripts')
    @include('timetable.partials.scripts')
@endsection
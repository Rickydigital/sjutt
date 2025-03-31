@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="animated fadeIn">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h1 class="font-weight-bold" style="color: #4B2E83;">
                        <i class="fa fa-edit mr-2"></i> Edit Calendar
                    </h1>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #4B2E83;">
                            <strong class="card-title" style="color: #4B2E83;">Update Calendar Entry</strong>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.calendars.update', $calendar->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label for="dates">Dates (e.g., Mon 1)</label>
                                    <input type="text" name="dates" id="dates" class="form-control" 
                                        value="{{ old('dates', $calendar->dates) }}" style="border-color: #4B2E83;" required>
                                    @error('dates')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="month">Select Month</label>
                                    <select name="month" id="month" class="form-control" style="border-color: #4B2E83;" required>
                                        <option value="">-- Select a Month --</option>
                                        <option value="January" {{ old('month', $calendar->month) == 'January' ? 'selected' : '' }}>January</option>
                                        <option value="February" {{ old('month', $calendar->month) == 'February' ? 'selected' : '' }}>February</option>
                                        <option value="March" {{ old('month', $calendar->month) == 'March' ? 'selected' : '' }}>March</option>
                                        <option value="April" {{ old('month', $calendar->month) == 'April' ? 'selected' : '' }}>April</option>
                                        <option value="May" {{ old('month', $calendar->month) == 'May' ? 'selected' : '' }}>May</option>
                                        <option value="June" {{ old('month', $calendar->month) == 'June' ? 'selected' : '' }}>June</option>
                                        <option value="July" {{ old('month', $calendar->month) == 'July' ? 'selected' : '' }}>July</option>
                                        <option value="August" {{ old('month', $calendar->month) == 'August' ? 'selected' : '' }}>August</option>
                                        <option value="September" {{ old('month', $calendar->month) == 'September' ? 'selected' : '' }}>September</option>
                                        <option value="October" {{ old('month', $calendar->month) == 'October' ? 'selected' : '' }}>October</option>
                                        <option value="November" {{ old('month', $calendar->month) == 'November' ? 'selected' : '' }}>November</option>
                                        <option value="December" {{ old('month', $calendar->month) == 'December' ? 'selected' : '' }}>December</option>
                                    </select>
                                    @error('month')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label for="academic_calendar">Academic Calendar</label>
                                    <input type="text" name="academic_calendar" id="academic_calendar" class="form-control" 
                                        value="{{ old('academic_calendar', $calendar->academic_calendar) }}" style="border-color: #4B2E83;">
                                    @error('academic_calendar')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="meeting_activities_calendar">Meeting Activities Calendar</label>
                                    <input type="text" name="meeting_activities_calendar" id="meeting_activities_calendar" 
                                        class="form-control" value="{{ old('meeting_activities_calendar', $calendar->meeting_activities_calendar) }}" 
                                        style="border-color: #4B2E83;">
                                    @error('meeting_activities_calendar')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="academic_year">Academic Year (e.g., 2023-2024)</label>
                                    <input type="text" name="academic_year" id="academic_year" class="form-control" 
                                        value="{{ old('academic_year', $calendar->academic_year) }}" style="border-color: #4B2E83;" required>
                                    @error('academic_year')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Week Numbers -->
                                <div id="week-numbers">
                                    <label>Week Numbers</label>
                                    @foreach ($calendar->weekNumbers as $index => $weekNumber)
                                        <div class="week-number-row mb-2">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <input type="text" name="week_numbers[{{ $index }}][week_number]" class="form-control" 
                                                        value="{{ $weekNumber->week_number }}" placeholder="Week Number" style="border-color: #4B2E83;">
                                                </div>
                                                <div class="col-md-6">
                                                    <select name="week_numbers[{{ $index }}][program_category]" class="form-control" 
                                                        style="border-color: #4B2E83;">
                                                        <option value="">Select Program Category</option>
                                                        @foreach ($programCategories as $category)
                                                            <option value="{{ $category }}" {{ $weekNumber->program_category == $category ? 'selected' : '' }}>
                                                                {{ $category }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-secondary mb-3" id="add-week-number">
                                    <i class="fa fa-plus mr-1"></i> Add Week Number
                                </button>

                                <div class="form-group">
                                    <button type="submit" class="btn" style="background-color: #4B2E83; color: white; border-radius: 20px;">
                                        <i class="fa fa-save mr-1"></i> Update Calendar
                                    </button>
                                    <a href="{{ route('admin.calendars.index') }}" class="btn btn-secondary" style="border-radius: 20px;">
                                        <i class="fa fa-arrow-left mr-1"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let weekNumberCount = {{ $calendar->weekNumbers->count() }};
        document.getElementById('add-week-number').addEventListener('click', function() {
            const container = document.getElementById('week-numbers');
            const newRow = `
                <div class="week-number-row mb-2">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="week_numbers[${weekNumberCount}][week_number]" class="form-control" 
                                placeholder="Week Number" style="border-color: #4B2E83;">
                        </div>
                        <div class="col-md-6">
                            <select name="week_numbers[${weekNumberCount}][program_category]" class="form-control" style="border-color: #4B2E83;">
                                <option value="">Select Program Category</option>
                                @foreach ($programCategories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>`;
            container.insertAdjacentHTML('beforeend', newRow);
            weekNumberCount++;
        });
    </script>
@endsection

<style>
    .btn:hover { opacity: 0.85; transform: translateY(-1px); transition: all 0.2s ease; }
    .card { border: none; border-radius: 10px; overflow: hidden; }
    .form-control:focus { border-color: #4B2E83; box-shadow: 0 0 5px rgba(75, 46, 131, 0.5); }
</style>
<!DOCTYPE html>
<html>
<head>
    <title>Examination Timetable</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            position: relative; 
        }
        h1 { 
            text-align: center; 
            font-size: 24px; 
            margin-bottom: 5px; 
        }
        h2 { 
            text-align: center; 
            font-size: 18px; 
            margin-bottom: 20px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 10px; 
            margin-bottom: 20px; 
        }
        th, td { 
            border: 1px solid #000; 
            padding: 4px; 
            text-align: center; 
            vertical-align: middle; 
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold; 
        }
        .time-slot-header { 
            background-color: #f8f9fa; 
        }
        .year-header { 
            background-color: #e9ecef; 
        }
        .page-break { 
            page-break-after: always; 
        }
        
        .watermark {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: -1; 
            opacity: 0.3; 
            width: 300px; 
            height: 300px; 
            background-image: url('{{ public_path('images/logo.png') }}');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
    </style>
</head>
<body>
    <!-- Watermark div -->
    <div class="watermark"></div>

    <h1>St John's University of Tanzania University Exams {{ $setup->academic_year }}</h1>
    <h2>Semester {{ $setup->semester }} - Draft {{ $setup->draft_number ?? 1 }}</h2>

    @foreach ($dateChunks as $chunkIndex => $dateChunk)
        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="time-slot-header">Time</th>
                    <th rowspan="2" class="year-header">Year</th>
                    @foreach ($dateChunk as $date)
                        @php
                            $carbonDate = \Carbon\Carbon::parse($date);
                            $formattedDate = $carbonDate->format('d-m') . ' (' . $carbonDate->format('l') . ')';
                        @endphp
                        <th colspan="{{ $programs->count() }}">{{ $formattedDate }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($dateChunk as $date)
                        @foreach ($programs as $program)
                            <th>{{ $program->short_name }}</th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($timeSlots as $slot)
                    @foreach ([1,2,3,4] as $yearNum)
                        <tr>
                            @if ($yearNum == 1)
                                <td rowspan="4" class="time-slot-header">{{ $slot['name'] }} ({{ $slot['start_time'] }} - {{ $slot['end_time'] }})</td>
                            @endif
                            <td class="year-header">Year {{ $yearNum }}</td>
                            @foreach ($dateChunk as $date)
                                @foreach ($programs as $program)
                                    @php
                                        $faculty = \App\Models\Faculty::where('program_id', $program->id)
                                            ->where('name', 'LIKE', "% {$yearNum}")
                                            ->first();
                                        $slotStartTime = \Carbon\Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i:s');
                                        $slotEndTime = \Carbon\Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i:s');
                                        $timetable = $timetables->firstWhere(function ($t) use ($faculty, $date, $slotStartTime, $slotEndTime) {
                                            return $t->faculty_id == ($faculty ? $faculty->id : null) &&
                                                   $t->exam_date == $date &&
                                                   $t->start_time == $slotStartTime &&
                                                   $t->end_time == $slotEndTime;
                                        });
                                    @endphp
                                    <td>
                                        @if ($timetable)
                                            {{ $timetable->course_code }} ({{ optional($timetable->venue)->name ?? '' }})
                                        @else
                                            
                                        @endif
                                    </td>
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        @if ($chunkIndex < count($dateChunks) - 1)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
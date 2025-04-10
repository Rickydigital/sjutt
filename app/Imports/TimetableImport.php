<?php

namespace App\Imports;

use App\Models\Timetable;
use App\Models\Faculty;
use App\Models\Year;
use App\Models\Venue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TimetableImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Timetable([
            'faculty_id'  => Faculty::where('name', $row['faculty'])->value('id'),
            'year_id'     => Year::where('year', $row['year'])->value('id'),
            'day'         => $row['day'],
            'time_start'  => $row['time_start'],
            'time_end'    => $row['time_end'],
            'course_code' => $row['course_code'],
            'activity'    => $row['activity'],
            'venue_id'    => Venue::where('name', $row['venue'])->value('id'),
        ]);

        if (!$facultyId || !$yearId || !$venueId) {
            return null; // Skip row if any ID is missing
        }
    }
}


<?php

namespace App\Imports;

use App\Models\Timetable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TimetableImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Timetable([
            'faculty' => $row['faculty'],
            'year' => $row['year'],
            'day' => $row['day'],
            'time_start' => $row['time_start'],
            'time_end' => $row['time_end'],
            'course_code' => $row['course_code'],
            'activity' => $row['activity'],
            'venue' => $row['venue'],
        ]);
    }
}

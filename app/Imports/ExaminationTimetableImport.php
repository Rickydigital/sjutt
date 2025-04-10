<?php

namespace App\Imports;

use App\Models\ExaminationTimetable;
use App\Models\Faculty;
use App\Models\Year;
use App\Models\Venue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ExaminationTimetableImport implements ToModel, WithHeadingRow {

    public function model(array $row) {
        return new ExaminationTimetable([
            'timetable_type' => $row['timetable_type'],
            'program'        => $row['program'],
            'semester'       => $row['semester'],
            'course_code'    => $row['course_code'],
            'faculty_id'     => Faculty::where('name', $row['faculty'])->value('id'),
            'year_id'        => Year::where('year', $row['year'])->value('id'),
            'exam_date'      => $row['exam_date'],
            'start_time'     => $row['start_time'],
            'end_time'       => $row['end_time'],
            'venue_id'       => Venue::where('name', $row['venue'])->value('id'),
        ]);

        if (!$facultyId || !$yearId || !$venueId) {
            return null; // Skip row if any ID is missing
        }
    }
}



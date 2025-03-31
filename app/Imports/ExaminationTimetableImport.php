<?php

namespace App\Imports;

use App\Models\ExaminationTimetable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ExaminationTimetableImport implements ToModel, WithHeadingRow {

    public function model(array $row) {
        return new ExaminationTimetable([
            'timetable_type' => $row['timetable_type'],
            'program'        => $row['program'],
            'semester'       => $row['semester'],
            'course_code'    => $row['course_code'],
            'faculty'        => $row['faculty'],
            'year'           => $row['year'],
            'exam_date'      => $row['exam_date'],
            'start_time'     => $row['start_time'],
            'end_time'       => $row['end_time'],
            'venue'          => $row['venue'],
        ]);
    }
}


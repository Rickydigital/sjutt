<?php

namespace App\Imports;

use App\Models\ExaminationTimetable;
use App\Models\Faculty;
use App\Models\Year;
use App\Models\Venue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ExaminationTimetableImport implements ToCollection, WithHeadingRow
{
    public $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;

            $facultyId = Faculty::where('name', $row['faculty'])->value('id');
            $yearId = Year::where('year', $row['year'])->value('id');
            $venueId = Venue::where('name', $row['venue'])->value('id');

            if (!$facultyId || !$yearId || !$venueId) {
                $this->errors[] = "Row $line: Missing or invalid faculty/year/venue.";
                continue;
            }

            if ($this->hasConflict($row['exam_date'], $row['start_time'], $row['end_time'], $facultyId, $yearId, $venueId)) {
                $this->errors[] = "Row $line: Conflict found (venue/session).";
                continue;
            }

            ExaminationTimetable::create([
                'timetable_type' => $row['timetable_type'],
                'program'        => $row['program'],
                'semester'       => $row['semester'],
                'course_code'    => $row['course_code'],
                'faculty_id'     => $facultyId,
                'year_id'        => $yearId,
                'exam_date'      => $row['exam_date'],
                'start_time'     => $row['start_time'],
                'end_time'       => $row['end_time'],
                'venue_id'       => $venueId,
            ]);
        }
    }

    private function hasConflict($date, $start, $end, $facultyId, $yearId, $venueId)
    {
        // Venue conflict
        $venueConflict = ExaminationTimetable::where('exam_date', $date)
            ->where('venue_id', $venueId)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        if ($venueConflict) return true;

        // Session conflict
        $sessionConflict = ExaminationTimetable::where('exam_date', $date)
            ->where('faculty_id', $facultyId)
            ->where('year_id', $yearId)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        return $sessionConflict;
    }
}

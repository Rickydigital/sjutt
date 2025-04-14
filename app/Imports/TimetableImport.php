<?php

namespace App\Imports;

use App\Models\Timetable;
use App\Models\Faculty;
use App\Models\Year;
use App\Models\Venue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TimetableImport implements ToCollection, WithHeadingRow
{
    public $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2; // Excel line (header is on line 1)

            $faculty = Faculty::where('name', $row['faculty'])->first();
            $year = Year::where('year', $row['year'])->first();
            $venue = Venue::where('name', $row['venue'])->first();

            if (!$faculty || !$year || !$venue) {
                $this->errors[] = "Row $line: Invalid faculty, year, or venue.";
                continue;
            }

            $conflict = $this->detectConflict(
                $row['day'],
                $row['time_start'],
                $row['time_end'],
                $faculty->id,
                $year->id,
                $venue->id
            );

            if ($conflict) {
                $this->errors[] = "Row $line: Conflict due to {$conflict}.";
                continue;
            }

            Timetable::create([
                'faculty_id'  => $faculty->id,
                'year_id'     => $year->id,
                'day'         => $row['day'],
                'time_start'  => $row['time_start'],
                'time_end'    => $row['time_end'],
                'course_code' => $row['course_code'],
                'activity'    => $row['activity'],
                'venue_id'    => $venue->id,
            ]);
        }
    }

    private function detectConflict($day, $timeStart, $timeEnd, $facultyId, $yearId, $venueId)
    {
        // Check for venue conflict
        $venueConflict = Timetable::where('day', $day)
            ->where('venue_id', $venueId)
            ->where('time_start', '<', $timeEnd)
            ->where('time_end', '>', $timeStart)
            ->exists();

        if ($venueConflict) return "venue conflict";

        // Check for session conflict (same faculty and year)
        $sessionConflict = Timetable::where('day', $day)
            ->where('faculty_id', $facultyId)
            ->where('year_id', $yearId)
            ->where('time_start', '<', $timeEnd)
            ->where('time_end', '>', $timeStart)
            ->exists();

        if ($sessionConflict) return "another session for this faculty and year";

        return null;
    }
}

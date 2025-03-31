<?php

// app/Imports/CalendarImport.php
namespace App\Imports;

use App\Models\Calendar;
use App\Models\WeekNumber;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class CalendarImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows->skip(1) as $row) { // Skip header row
            $calendar = Calendar::create([
                'month' => $row[0], // December, January
                'dates' => $row[1], // Mon-25 or Jan 1 - Jan 7
                'academic_calendar' => $row[2] ?? null, // Semester Start, Final day of 6-weeks field work
                'meeting_activities_calendar' => $row[3] ?? null, // Faculty Meeting, Students Services Committee
                'academic_year' => $row[4] ?? date('Y'), // Default to current year if null
            ]);

            // Handling Week Numbers
            if (!empty($row[5])) {
                $weekNumberPairs = explode(',', $row[5]); // "Degree Non-health:23,Degree Health:25"
                foreach ($weekNumberPairs as $pair) {
                    [$category, $week] = explode(':', trim($pair));
                    WeekNumber::create([
                        'calendar_id' => $calendar->id,
                        'week_number' => (int) trim($week),
                        'program_category' => trim($category),
                    ]);
                }
            }
        }
    }
}

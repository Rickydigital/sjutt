<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Faculty;
use App\Models\CalendarEvent;
use App\Models\CalendarSetup;
use Illuminate\Database\Seeder;

class CalendarSeeder extends Seeder
{
    public function run()
    {
        $academicYear = AcademicYear::create([
            'year' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
        ]);

        $programs = [
            ['name' => 'Nursing', 'short_name' => 'SON', 'total_years' => 4, 'description' => 'Bachelor of Science in Nursing', 'administrator_id' => 1],
            ['name' => 'Public Health', 'short_name' => 'PH', 'total_years' => 4, 'description' => 'Bachelor of Public Health', 'administrator_id' => 1],
            ['name' => 'Computer Science', 'short_name' => 'CS', 'total_years' => 4, 'description' => 'Bachelor of Computer Science', 'administrator_id' => 1],
            ['name' => 'Masters Nursing', 'short_name' => 'MSN', 'total_years' => 2, 'description' => 'Master of Science in Nursing', 'administrator_id' => 1],
        ];

        foreach ($programs as $programData) {
            $program = Program::create($programData);
            foreach ($program->getGeneratedFacultyNames() as $facultyName) {
                Faculty::create([
                    'name' => $facultyName,
                    'total_students_no' => 50,
                    'description' => "Year cohort for {$facultyName}",
                    'program_id' => $program->id,
                ]);
            }
        }

        CalendarSetup::create([
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
            'degree_health_programs' => [Program::where('short_name', 'SON')->first()->id, Program::where('short_name', 'PH')->first()->id],
            'degree_non_health_programs' => [Program::where('short_name', 'CS')->first()->id],
            'non_degree_health_programs' => [],
            'non_degree_non_health_programs' => [],
            'masters_health_programs' => [Program::where('short_name', 'MSN')->first()->id],
            'masters_non_health_programs' => [],
        ]);

        $events = [
            [
                'calendar_type' => 'Academic',
                'event_type' => 'Registration',
                'description' => 'Registration for SON 1',
                'start_date' => '2024-09-02',
                'end_date' => '2024-09-06',
                'week_number' => 1,
                'faculty_id' => Faculty::where('name', 'SON 1')->first()->id,
                'program_ids' => [Program::where('short_name', 'SON')->first()->id],
            ],
            [
                'calendar_type' => 'Meeting',
                'event_type' => 'Faculty Meeting',
                'description' => 'Faculty meeting for SON and PH',
                'start_date' => '2024-09-09',
                'end_date' => '2024-09-09',
                'week_number' => 2,
                'faculty_id' => null,
                'program_ids' => [Program::where('short_name', 'SON')->first()->id, Program::where('short_name', 'PH')->first()->id],
            ],
        ];

        foreach ($events as $event) {
            $calendarEvent = CalendarEvent::create([
                'faculty_id' => $event['faculty_id'],
                'academic_year_id' => $academicYear->id,
                'calendar_type' => $event['calendar_type'],
                'event_type' => $event['event_type'],
                'description' => $event['description'],
                'start_date' => $event['start_date'],
                'end_date' => $event['end_date'],
                'week_number' => $event['week_number'],
            ]);
            $calendarEvent->programs()->sync($event['program_ids']);
        }
    }
}
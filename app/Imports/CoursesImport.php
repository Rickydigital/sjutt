<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\User;
use App\Models\Faculty;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CoursesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $course = Course::firstOrCreate(
                ['course_code' => $row['course_code']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'credits' => (int) $row['credits']
                ]
            );

            // Attach Lecturers by email
            $emails = explode(',', $row['lecturer_emails']);
            $lecturerIds = User::whereIn('email', array_map('trim', $emails))->pluck('id')->toArray();
            $course->lecturers()->sync($lecturerIds);

            // Attach Faculties by name
            $facultyNames = explode(',', $row['faculty_names']);
            $facultyIds = Faculty::whereIn('name', array_map('trim', $facultyNames))->pluck('id')->toArray();
            $course->faculties()->sync($facultyIds);
        }
    }
}


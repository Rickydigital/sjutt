<?php

namespace App\Imports\Sheets;

use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CourseFacultyCountsImportSheet implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $courseCode = trim((string) ($row['course_code'] ?? ''));
            $facultyName = trim((string) ($row['faculty_name'] ?? ''));

            if ($courseCode === '' || $facultyName === '') {
                Log::warning("Faculty counts sheet row {$rowNumber}: missing course_code or faculty_name");
                continue;
            }

            $course = Course::where('course_code', $courseCode)->first();

            if (!$course) {
                Log::warning("Faculty counts sheet row {$rowNumber}: course not found {$courseCode}");
                continue;
            }

            $faculty = Faculty::where('name', $facultyName)->first();

            if (!$faculty) {
                Log::warning("Faculty counts sheet row {$rowNumber}: faculty not found {$facultyName}");
                continue;
            }

            $studentCount = (int) ($row['student_count'] ?? 0);

            if ($studentCount < 0) {
                $studentCount = 0;
            }

            $course->faculties()->syncWithoutDetaching([
                $faculty->id => [
                    'student_count' => $studentCount,
                ],
            ]);
        }
    }
}
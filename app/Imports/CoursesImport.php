<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Semester;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class CoursesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            Log::debug("Processing row {$index}", $row->toArray());

            if (empty($row['course_code']) || !is_string($row['course_code']) || trim($row['course_code']) === '') {
                Log::warning("Skipping row {$index} due to missing or invalid course_code", $row->toArray());
                continue;
            }

            $courseCode = trim($row['course_code']);

            $name = $row['name'] ?? 'Unnamed Course';
            if (str_word_count($name) > 5) {
                Log::warning("Skipping row {$index} for course_code {$courseCode}: Name exceeds 5 words");
                continue;
            }

            // Map semester_name to semester_id
            $semester = null;
            if (!empty($row['semester_name'])) {
                $semester = Semester::where('name', trim($row['semester_name']))->first();
                if (!$semester) {
                    Log::warning("Skipping row {$index} for course_code {$courseCode}: Invalid semester name {$row['semester_name']}");
                    continue;
                }
            } else {
                $semester = Semester::where('name', 'First Semester')->first();
            }

            try {
                $course = Course::firstOrCreate(
                    ['course_code' => $courseCode],
                    [
                        'name' => $name,
                        'description' => $row['description'] ?? null,
                        'credits' => (int) ($row['credits'] ?? 0),
                        'hours' => (int) ($row['hours'] ?? 0),
                        'practical_hrs' => isset($row['practical_hrs']) ? (int) $row['practical_hrs'] : null,
                        'session' => (int) ($row['session'] ?? 0),
                        'semester_id' => $semester ? $semester->id : null,
                        'cross_catering' => isset($row['cross_catering']) ? (bool) $row['cross_catering'] : false,
                        'is_workshop' => isset($row['is_workshop']) ? (bool) $row['is_workshop'] : false,
                    ]
                );

                if (!is_null($course->practical_hrs) && $course->practical_hrs > $course->hours) {
                    Log::warning("Skipping row {$index} for course_code {$courseCode}: practical_hrs ({$course->practical_hrs}) exceeds hours ({$course->hours})");
                    continue;
                }

                if (!empty($row['lecturer_emails'])) {
                    $emails = array_map('trim', explode(',', $row['lecturer_emails']));
                    $firstEmail = reset($emails);
                    $lecturer = User::where('email', $firstEmail)->first();
                    if ($lecturer) {
                        $course->lecturers()->sync([$lecturer->id]);
                    } else {
                        Log::warning("Skipping lecturer for row {$index} with course_code {$courseCode}: No user found for email {$firstEmail}");
                    }
                }

                if (!empty($row['faculty_names'])) {
                    $facultyNames = array_map('trim', explode(',', $row['faculty_names']));
                    $facultyIds = Faculty::whereIn('name', $facultyNames)->pluck('id')->toArray();
                    $course->faculties()->sync($facultyIds);
                }
            } catch (\Exception $e) {
                Log::error("Error processing row {$index} with course_code {$courseCode}: {$e->getMessage()}");
                continue;
            }
        }
    }
}
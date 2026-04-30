<?php

namespace App\Imports\Sheets;

use App\Models\Course;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CoursesImportSheet implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $courseCode = trim((string) ($row['course_code'] ?? ''));

            if ($courseCode === '') {
                Log::warning("Courses sheet row {$rowNumber}: missing course_code");
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                Log::warning("Courses sheet row {$rowNumber}: missing name for {$courseCode}");
                continue;
            }

            if (str_word_count($name) > 5) {
                Log::warning("Courses sheet row {$rowNumber}: name exceeds 5 words for {$courseCode}");
                continue;
            }

            $hours = (int) ($row['hours'] ?? 0);

            if ($hours < 1) {
                Log::warning("Courses sheet row {$rowNumber}: hours must be at least 1 for {$courseCode}");
                continue;
            }

            $practicalHrs = isset($row['practical_hrs']) && $row['practical_hrs'] !== ''
                ? (int) $row['practical_hrs']
                : null;

            if (!is_null($practicalHrs) && $practicalHrs > $hours) {
                Log::warning("Courses sheet row {$rowNumber}: practical_hrs exceeds hours for {$courseCode}");
                continue;
            }

            $semesterName = trim((string) ($row['semester_name'] ?? 'First Semester'));

            $semester = Semester::where('name', $semesterName)->first();

            if (!$semester) {
                Log::warning("Courses sheet row {$rowNumber}: semester not found {$semesterName}");
                continue;
            }

            $course = Course::updateOrCreate(
                ['course_code' => $courseCode],
                [
                    'name' => $name,
                    'description' => $row['description'] ?? null,
                    'credits' => (int) ($row['credits'] ?? 0),
                    'hours' => $hours,
                    'practical_hrs' => $practicalHrs,
                    'session' => (int) ($row['session'] ?? 0),
                    'semester_id' => $semester->id,
                    'cross_catering' => $this->toBoolean($row['cross_catering'] ?? false),
                    'is_workshop' => $this->toBoolean($row['is_workshop'] ?? false),
                ]
            );

            $this->syncLecturers($course, $row);
        }
    }

    private function syncLecturers(Course $course, $row): void
    {
        if (empty($row['lecturer_emails'])) {
            $course->lecturers()->sync([]);
            return;
        }

        $emails = collect(explode(',', $row['lecturer_emails']))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->unique()
            ->values();

        $lecturerIds = User::whereIn('email', $emails)->pluck('id')->toArray();

        $course->lecturers()->sync($lecturerIds);
    }

    private function toBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'yes', 'true', 'y'], true);
    }
}
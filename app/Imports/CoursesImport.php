<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CoursesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // because heading row is row 1

            Log::debug("Processing course import row {$rowNumber}", $row->toArray());

            $courseCode = trim((string) ($row['course_code'] ?? ''));

            if ($courseCode === '') {
                Log::warning("Skipping row {$rowNumber}: missing course_code", $row->toArray());
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                Log::warning("Skipping row {$rowNumber} for course_code {$courseCode}: missing course name");
                continue;
            }

            if (str_word_count($name) > 5) {
                Log::warning("Skipping row {$rowNumber} for course_code {$courseCode}: name exceeds 5 words");
                continue;
            }

            $hours = (int) ($row['hours'] ?? 0);
            $practicalHrs = isset($row['practical_hrs']) && $row['practical_hrs'] !== ''
                ? (int) $row['practical_hrs']
                : null;

            if ($hours < 1) {
                Log::warning("Skipping row {$rowNumber} for course_code {$courseCode}: hours must be at least 1");
                continue;
            }

            if (!is_null($practicalHrs) && $practicalHrs > $hours) {
                Log::warning("Skipping row {$rowNumber} for course_code {$courseCode}: practical_hrs exceeds hours");
                continue;
            }

            $semester = $this->resolveSemester($row, $rowNumber, $courseCode);

            if (!$semester) {
                continue;
            }

            try {
                $course = Course::updateOrCreate(
                    [
                        'course_code' => $courseCode,
                    ],
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

                $this->syncLecturers($course, $row, $rowNumber, $courseCode);

                $this->syncFacultiesWithStudentCounts($course, $row, $rowNumber, $courseCode);
            } catch (\Throwable $e) {
                Log::error("Error processing row {$rowNumber} with course_code {$courseCode}: {$e->getMessage()}", [
                    'row' => $row->toArray(),
                ]);

                continue;
            }
        }
    }

    private function resolveSemester($row, int $rowNumber, string $courseCode): ?Semester
    {
        $semesterName = trim((string) ($row['semester_name'] ?? ''));

        if ($semesterName === '') {
            $semesterName = 'First Semester';
        }

        $semester = Semester::where('name', $semesterName)->first();

        if (!$semester) {
            Log::warning("Skipping row {$rowNumber} for course_code {$courseCode}: invalid semester name {$semesterName}");
            return null;
        }

        return $semester;
    }

    private function syncLecturers(Course $course, $row, int $rowNumber, string $courseCode): void
    {
        if (empty($row['lecturer_emails'])) {
            return;
        }

        $emails = collect(explode(',', $row['lecturer_emails']))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return;
        }

        $lecturerIds = User::whereIn('email', $emails)
            ->pluck('id')
            ->toArray();

        if (empty($lecturerIds)) {
            Log::warning("No lecturers found for row {$rowNumber} with course_code {$courseCode}", [
                'emails' => $emails->toArray(),
            ]);

            return;
        }

        $course->lecturers()->sync($lecturerIds);
    }

    private function syncFacultiesWithStudentCounts(Course $course, $row, int $rowNumber, string $courseCode): void
    {
        if (empty($row['faculty_names'])) {
            return;
        }

        $facultyNames = collect(explode(',', $row['faculty_names']))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->values();

        if ($facultyNames->isEmpty()) {
            return;
        }

        $studentCounts = collect(explode(',', (string) ($row['faculty_student_counts'] ?? '')))
            ->map(fn ($count) => trim($count))
            ->values();

        $faculties = Faculty::whereIn('name', $facultyNames)
            ->get()
            ->keyBy('name');

        $syncData = [];

        foreach ($facultyNames as $position => $facultyName) {
            $faculty = $faculties->get($facultyName);

            if (!$faculty) {
                Log::warning("Faculty not found for row {$rowNumber} with course_code {$courseCode}: {$facultyName}");
                continue;
            }

            $count = $studentCounts->get($position, 0);

            $syncData[$faculty->id] = [
                'student_count' => is_numeric($count) ? (int) $count : 0,
            ];
        }

        if (empty($syncData)) {
            Log::warning("No valid faculties found for row {$rowNumber} with course_code {$courseCode}");
            return;
        }

        $course->faculties()->sync($syncData);
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
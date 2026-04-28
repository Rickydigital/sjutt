<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class CourseFacultyStudentCountsImport implements ToCollection
{
    public $errors = [];

    protected $semesterId;
    protected $programIds;

    public function __construct(int $semesterId, array $programIds)
    {
        $this->semesterId = $semesterId;
        $this->programIds = $programIds;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->errors[] = 'File is empty';
            return;
        }

        $headers = $rows->first()->map(fn($v) => trim((string) $v))->toArray();

        $courseIndex = array_search('course_code', $headers);

        if ($courseIndex === false) {
            $this->errors[] = 'Missing course_code column';
            return;
        }

        $faculties = Faculty::whereIn('program_id', $this->programIds)
            ->get()
            ->keyBy(fn($f) => strtolower(trim($f->name)));

        DB::transaction(function () use ($rows, $headers, $courseIndex, $faculties) {

            foreach ($rows->skip(1) as $rowNumber => $row) {

                $courseCode = trim((string) ($row[$courseIndex] ?? ''));

                if (!$courseCode) continue;

                $course = Course::where('semester_id', $this->semesterId)
                    ->where('course_code', $courseCode)
                    ->first();

                if (!$course) {
                    $this->errors[] = "Row " . ($rowNumber + 2) . ": Course {$courseCode} not found";
                    continue;
                }

                $sync = [];

                foreach ($headers as $index => $header) {

                    if (in_array($header, ['course_code', 'course_name'])) continue;

                    $faculty = $faculties->get(strtolower(trim($header)));

                    if (!$faculty) continue;

                    $value = $row[$index] ?? null;

                    if ($value === null || $value === '') continue;

                    if (!is_numeric($value)) {
                        $this->errors[] = "Row " . ($rowNumber + 2) . ": Invalid number for {$header}";
                        continue;
                    }

                    $sync[$faculty->id] = [
                        'student_count' => (int) $value
                    ];
                }

                if (!empty($sync)) {
                    $course->faculties()->syncWithoutDetaching($sync);
                }
            }
        });
    }
}
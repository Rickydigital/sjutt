<?php

namespace App\Exports;

use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CourseFacultyStudentTemplateExport implements FromCollection, ShouldAutoSize
{
    protected $semesterId;
    protected $programIds;

    public function __construct(int $semesterId, array $programIds)
    {
        $this->semesterId = $semesterId;
        $this->programIds = $programIds;
    }

    public function collection(): Collection
    {
        // Faculties filtered by selected programs
        $faculties = Faculty::whereIn('program_id', $this->programIds)
            ->orderBy('name')
            ->get();

        // Courses filtered by semester
        $courses = Course::with('faculties')
            ->where('semester_id', $this->semesterId)
            ->orderBy('course_code')
            ->get();

        $rows = collect();

        // Header
        $rows->push(array_merge(
            ['course_code', 'course_name'],
            $faculties->pluck('name')->toArray()
        ));

        // Rows
        foreach ($courses as $course) {
            $row = [
                $course->course_code,
                $course->name,
            ];

            foreach ($faculties as $faculty) {
                $pivot = $course->faculties->firstWhere('id', $faculty->id);
                $row[] = $pivot ? (int) ($pivot->pivot->student_count ?? 0) : '';
            }

            $rows->push($row);
        }

        return $rows;
    }
}
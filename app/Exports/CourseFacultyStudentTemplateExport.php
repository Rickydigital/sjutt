<?php

namespace App\Exports;

use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CourseFacultyStudentTemplateExport implements FromCollection, ShouldAutoSize
{
    protected int $semesterId;
    protected array $programIds;

    public function __construct(int $semesterId, array $programIds)
    {
        $this->semesterId = $semesterId;
        $this->programIds = $programIds;
    }

    public function collection(): Collection
    {
        $faculties = Faculty::query()
            ->whereIn('program_id', $this->programIds)
            ->orderBy('name')
            ->get();

        $facultyIds = $faculties->pluck('id')->toArray();

        $courses = Course::query()
            ->with(['faculties' => function ($query) use ($facultyIds) {
                $query->whereIn('faculties.id', $facultyIds);
            }])
            ->where('semester_id', $this->semesterId)
            ->whereHas('faculties', function ($query) use ($facultyIds) {
                $query->whereIn('faculties.id', $facultyIds);
            })
            ->orderBy('course_code')
            ->get();

        $rows = collect();

        $rows->push(array_merge(
            ['course_code', 'course_name'],
            $faculties->pluck('name')->toArray()
        ));

        foreach ($courses as $course) {
            $row = [
                $course->course_code,
                $course->name,
            ];

            foreach ($faculties as $faculty) {
                $attachedFaculty = $course->faculties->firstWhere('id', $faculty->id);

                $row[] = $attachedFaculty
                    ? (int) ($attachedFaculty->pivot->student_count ?? 0)
                    : '';
            }

            $rows->push($row);
        }

        return $rows;
    }
}
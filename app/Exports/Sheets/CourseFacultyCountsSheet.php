<?php

namespace App\Exports\Sheets;

use App\Models\Course;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CourseFacultyCountsSheet implements FromCollection, WithHeadings
{
    public function collection()
    {
        $rows = collect();

        Course::with('faculties')->get()->each(function ($course) use ($rows) {
            foreach ($course->faculties as $faculty) {
                $rows->push([
                    'course_code' => $course->course_code,
                    'faculty_name' => $faculty->name,
                    'student_count' => $faculty->pivot->student_count ?? 0,
                ]);
            }
        });

        return $rows;
    }

    public function headings(): array
    {
        return [
            'course_code',
            'faculty_name',
            'student_count',
        ];
    }
}
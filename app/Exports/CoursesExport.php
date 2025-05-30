<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CoursesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Course::with(['lecturers', 'faculties'])->get()->map(function ($course) {
            return [
                'course_code' => $course->course_code,
                'name' => $course->name,
                'description' => $course->description,
                'credits' => $course->credits,
                'lecturer_emails' => $course->lecturers->pluck('email')->implode(', '),
                'faculty_names' => $course->faculties->pluck('name')->implode(', ')
            ];
        });
    }

    public function headings(): array
    {
        return [
            'course_code',
            'name',
            'description',
            'credits',
            'lecturer_emails',
            'faculty_names',
        ];
    }
}


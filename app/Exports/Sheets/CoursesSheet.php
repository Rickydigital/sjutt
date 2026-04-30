<?php

namespace App\Exports\Sheets;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CoursesSheet implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Course::with(['lecturers', 'semester'])->get()->map(function ($course) {
            return [
                'course_code' => $course->course_code,
                'name' => $course->name,
                'description' => $course->description,
                'credits' => $course->credits,
                'hours' => $course->hours,
                'practical_hrs' => $course->practical_hrs,
                'session' => $course->session,
                'semester_name' => optional($course->semester)->name,
                'cross_catering' => $course->cross_catering ? 1 : 0,
                'is_workshop' => $course->is_workshop ? 1 : 0,
                'lecturer_emails' => $course->lecturers->pluck('email')->implode(', '),
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
            'hours',
            'practical_hrs',
            'session',
            'semester_name',
            'cross_catering',
            'is_workshop',
            'lecturer_emails',
        ];
    }
}
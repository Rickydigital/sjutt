<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CoursesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Course::with(['lecturers', 'faculties', 'semester'])->get()
            ->map(function ($course) {

                // Match faculty names with student counts (same order)
                $facultyNames = [];
                $studentCounts = [];

                foreach ($course->faculties as $faculty) {
                    $facultyNames[] = $faculty->name;
                    $studentCounts[] = $faculty->pivot->student_count ?? 0;
                }

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
                    'faculty_names' => implode(', ', $facultyNames),
                    'faculty_student_counts' => implode(', ', $studentCounts),
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
            'faculty_names',
            'faculty_student_counts',
        ];
    }
}
<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    protected $students;

    public function __construct($students)
    {
        $this->students = $students;
    }

    public function collection()
    {
        return $this->students->map(function ($student) {
            return [
                'Reg No'      => $student->reg_no,
                'First Name'  => $student->first_name,
                'Last Name'   => $student->last_name,
                'Phone'       => $student->phone ?? '—',
                'Email'       => $student->email,
                'Faculty'     => $student->faculty?->name ?? '—',
                'Program'     => $student->program?->name ?? '—',
                'Gender'      => ucfirst($student->gender),
                'Status'      => $student->status === 'Inactive' ? 'Not Activated' : $student->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Reg No',
            'First Name',
            'Last Name',
            'Phone',
            'Email',
            'Faculty',
            'Program',
            'Gender',
            'Status',
        ];
    }
}
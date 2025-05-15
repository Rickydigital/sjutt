<?php

namespace App\Exports;

use App\Models\Timetable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimetableExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Timetable::with('faculty', 'venue', 'lecturer');
        if ($this->request->faculty) {
            $query->where('faculty_id', $this->request->faculty);
        }
        if ($this->request->day) {
            $query->where('day', $this->request->day);
        }
        if ($this->request->search) {
            $query->where(function ($q) {
                $q->where('course_code', 'like', '%' . $this->request->search . '%')
                  ->orWhere('activity', 'like', '%' . $this->request->search . '%');
            });
        }
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Day',
            'Faculty',
            'Time Start',
            'Time End',
            'Course Code',
            'Course Name',
            'Activity',
            'Venue',
            'Venue Capacity',
            'Groups',
            'Lecturer',
        ];
    }

    public function map($timetable): array
    {
        return [
            $timetable->day,
            $timetable->faculty->name ?? 'N/A',
            $timetable->time_start,
            $timetable->time_end,
            $timetable->course_code,
            $timetable->course_name ?? 'N/A',
            $timetable->activity,
            $timetable->venue->name ?? 'N/A',
            $timetable->venue->capacity ?? 'N/A',
            $timetable->group_selection,
            $timetable->lecturer->name ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF4B2E83']],
            ],
            'A:Z' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}
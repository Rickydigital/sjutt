<?php

namespace App\Exports;

use App\Models\Program;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FacultiesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $rows = [];

        $programs = Program::with('faculties')->get();

        foreach ($programs as $program) {
            $shortName = $program->short_name;
            $totalYears = $program->total_years;

            for ($year = 1; $year <= $totalYears; $year++) {
                $facultyName = "{$shortName} {$year}";
                $rows[] = [
                    'Program Name' => $program->name,
                    'Faculty Name' => $facultyName,
                    'Total Students' => '',
                    'Group Names' => '',
                ];
            }
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return ['Program Name', 'Faculty Name', 'Total Students', 'Group Names'];
    }
}

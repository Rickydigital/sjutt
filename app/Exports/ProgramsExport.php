<?php

namespace App\Exports;

use App\Models\Program;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProgramsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Program::with('administrator')->get()->map(function ($program) {
            return [
                'Name' => $program->name,
                'Short Name' => $program->short_name,
                'Total Years' => $program->total_years,
                'Description' => $program->description,
                'Administrator Email' => optional($program->administrator)->email,
            ];
        });
    }

    public function headings(): array
    {
        return ['Name', 'Short Name', 'Total Years', 'Description', 'Administrator Email'];
    }
}

<?php

namespace App\Imports;

use App\Models\FeeStructure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FeeStructureImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new FeeStructure([
            'program_type' => $row['program_type'],
            'program_name' => $row['program_name'],
            'first_year' => $row['first_year'],
            'continuing_year' => $row['continuing_year'],
            'final_year' => $row['final_year'],
        ]);
    }
}

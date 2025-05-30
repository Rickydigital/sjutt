<?php

namespace App\Imports;

use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProgramsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $admin = User::where('email', $row['administrator_email'])->first();

            if ($admin) {
                Program::updateOrCreate(
                    ['short_name' => $row['short_name']],
                    [
                        'name' => $row['name'],
                        'total_years' => $row['total_years'],
                        'description' => $row['description'],
                        'administrator_id' => $admin->id,
                    ]
                );
            }
        }
    }
}


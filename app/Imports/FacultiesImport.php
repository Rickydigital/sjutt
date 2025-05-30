<?php

namespace App\Imports;

use App\Models\Faculty;
use App\Models\Program;
use App\Models\FacultyGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FacultiesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $program = Program::where('name', $row['program_name'])->first();

            if (!$program) {
                continue; // Skip if program not found
            }

            $faculty = Faculty::create([
                'name' => $row['faculty_name'],
                'total_students_no' => $row['total_students'],
                'program_id' => $program->id,
            ]);

            if (!empty($row['group_names'])) {
                $groupNames = array_map('trim', explode(',', $row['group_names']));
                $studentCount = $faculty->total_students_no / max(1, count($groupNames));

                foreach ($groupNames as $name) {
                    FacultyGroup::create([
                        'faculty_id' => $faculty->id,
                        'group_name' => $name,
                        'student_count' => floor($studentCount),
                    ]);
                }
            }
        }
    }
}

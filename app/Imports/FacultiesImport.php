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

            $desired = 40;
            $total = $faculty->total_students_no;
            $num_groups = $total > 0 ? floor($total / $desired) : 0;
            $remaining = $total - $num_groups * $desired;

            if ($num_groups > 0) {
                if ($remaining >= $desired / 2) {
                    // Create a new group for remaining students if they are at least half the desired size
                    $num_groups++;
                    for ($i = 0; $i < $num_groups; $i++) {
                        $count = ($i < $num_groups - 1) ? $desired : $remaining;
                        $name = $this->generateGroupName($i);

                        FacultyGroup::create([
                            'faculty_id' => $faculty->id,
                            'group_name' => $name,
                            'student_count' => $count,
                        ]);
                    }
                } else {
                    // Distribute remaining students across groups
                    $base_count = $desired;
                    $add_extra = $remaining > 0 ? floor($remaining / $num_groups) : 0;
                    $base_count += $add_extra;
                    $remaining -= $add_extra * $num_groups;

                    for ($i = 0; $i < $num_groups; $i++) {
                        $count = $base_count;
                        if ($i < $remaining) {
                            $count++;
                        }
                        $name = $this->generateGroupName($i);

                        FacultyGroup::create([
                            'faculty_id' => $faculty->id,
                            'group_name' => $name,
                            'student_count' => $count,
                        ]);
                    }
                }
            } elseif ($remaining > 0) {
                // If no full groups, create one group for all students
                FacultyGroup::create([
                    'faculty_id' => $faculty->id,
                    'group_name' => $this->generateGroupName(0),
                    'student_count' => $remaining,
                ]);
            }
        }
    }

    private function generateGroupName($index)
    {
        $name = '';
        $i = $index;
        while ($i >= 0) {
            $name = chr(65 + ($i % 26)) . $name;
            $i = floor($i / 26) - 1;
        }
        return 'Group ' . $name;
    }
}
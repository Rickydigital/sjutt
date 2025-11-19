<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;

class StudentsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithEvents
{
    public $imported = 0;
    public $duplicates = 0;
    public $seenRegNos = [];

    public function collection(Collection $rows)
    {
        $batch = [];

        foreach ($rows as $row) {
            $regNo = trim($row['idnumber'] ?? '');

            // Skip empty reg_no
            if (empty($regNo)) {
                continue;
            }

            // Skip if this reg_no already appeared in this file
            if (isset($this->seenRegNos[$regNo])) {
                $this->duplicates++;
                continue;
            }

            $this->seenRegNos[$regNo] = true;

            $batch[] = [
                'reg_no'     => $regNo,
                'first_name' => !empty(trim($row['firstname'] ?? '')) ? trim($row['firstname']) : null,
                'last_name'  => !empty(trim($row['lastname'] ?? '')) ? trim($row['lastname']) : null,
                'email'      => $this->getEmail($regNo, $row['email'] ?? ''),
                'password'   => Hash::make($regNo),
                'gender'     => $this->getGender($row['gender'] ?? ''),
                'is_online'  => false,
                'can_upload' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->imported++;

            // Insert every 1000 rows
            if (count($batch) === 1000) {
                Student::insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            Student::insert($batch);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                $import = $event->reader->getDelegate();

                Log::info("Import finished: {$this->imported} imported, {$this->duplicates} duplicates skipped");
            },
        ];
    }

    private function getEmail($regNo, $email)
{
    $email = trim($email ?? '');

    // IGNORE real emails completely → always use temp
    $clean = str_replace('/', '-', $regNo);
    return "temp.{$clean}@student.sjut.ac.tz";
}

   private function getGender($gender)
{
    $g = strtoupper(trim($gender ?? ''));

    return match ($g) {
        'M', 'MALE'     => 'male',
        'F', 'FEMALE'   => 'female',
        default         => null,
    };
}
}
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
    public $seenRegNos = []; // Tracks duplicates within the current file

    public function collection(Collection $rows)
    {
        $batch = [];
        $regNosInThisChunk = [];

        foreach ($rows as $row) {
            $regNo = trim($row['idnumber'] ?? '');

            // Skip empty reg_no
            if (empty($regNo)) {
                continue;
            }

            // Skip if already seen in this import file
            if (isset($this->seenRegNos[$regNo])) {
                $this->duplicates++;
                continue;
            }

            // Mark as seen in this import session
            $this->seenRegNos[$regNo] = true;
            $regNosInThisChunk[] = $regNo;

            // Prepare record
            $batch[] = [
                'reg_no'     => $regNo,
                'first_name' => !empty(trim($row['firstname'] ?? '')) ? trim($row['firstname']) : null,
                'last_name'  => !empty(trim($row['lastname'] ?? '')) ? trim($row['lastname']) : null,
                'email'      => $this->getEmail($regNo),
                'password'   => Hash::make($regNo),
                'gender'     => $this->getGender($row['gender'] ?? ''),
                'is_online'  => 0,
                'can_upload' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // If no valid rows, skip DB check
        if (empty($batch)) {
            return;
        }

        // Step 1: Check which reg_no already exist in DB
        $existingRegNos = Student::whereIn('reg_no', $regNosInThisChunk)
            ->pluck('reg_no')
            ->toArray();

        // Step 2: Filter out existing ones
        $newRecords = [];
        foreach ($batch as $record) {
            if (in_array($record['reg_no'], $existingRegNos, true)) {
                $this->duplicates++;
            } else {
                $newRecords[] = $record;
                $this->imported++;
            }
        }

        // Step 3: Bulk insert only new records (safe + fast)
        if (!empty($newRecords)) {
            Student::insert($newRecords);
        }
    }

    public function chunkSize(): int
    {
        return 1000; // Process 1000 rows at a time
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                Log::info("Students import finished: {$this->imported} imported, {$this->duplicates} duplicates skipped (file + database)");
            },
        ];
    }

    private function getEmail(string $regNo): string
    {
        $clean = str_replace('/', '-', $regNo);
        return "temp.{$clean}@student.sjut.ac.tz";
    }

    private function getGender($gender): ?string
    {
        $g = strtoupper(trim($gender ?? ''));
        return match ($g) {
            'M', 'MALE'       => 'male',
            'F', 'FEMALE'     => 'female',
            default           => null,
        };
    }
}
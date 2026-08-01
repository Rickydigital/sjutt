<?php

namespace Database\Seeders;

use App\Models\Alumni;
use App\Models\AlumniEducation;
use App\Models\AlumniEmployment;
use App\Models\Country;
use App\Models\EmploymentSector;
use App\Models\EmploymentState;
use App\Models\Faculty;
use App\Models\GraduationYear;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AlumniImportSeeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('seeders/data/alumni_import_ready_exact.csv');

        if (!file_exists($csv)) {
            $this->command->error("CSV not found: $csv");
            return;
        }

        $handle = fopen($csv, 'r');
        $headers = fgetcsv($handle); // consume header row
        $headers = array_map('trim', $headers);

        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($headers)) continue;

            $data = array_combine($headers, array_map('trim', $row));

            $email = strtolower(trim($data['email'] ?? ''));
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            if (Alumni::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            $lName = $data['l_name'] ?? '';
            $defaultPassword = strtolower($lName) ?: 'alumni123';

            $alumni = Alumni::create([
                'f_name'   => $data['f_name'] ?: null,
                'm_name'   => $data['m_name'] ?: null,
                'l_name'   => $lName ?: null,
                'email'    => $email,
                'password' => Hash::make($defaultPassword),
                'gender'   => $this->normaliseGender($data['gender'] ?? null),
                'phone'    => $data['phone'] ?: null,
                'settlement_region' => $data['region'] ?: null,
                'settlement_city'   => $data['city'] ?: null,
                'settlement_country_id' => $this->resolveCountry($data['country'] ?? null),
                'interested_meetings'       => $this->parseBool($data['interested_meetings'] ?? null),
                'interested_social_platform' => $this->parseBool($data['interested_social_platform'] ?? null),
                'status'    => 'pending',
                'is_active' => false,
                'imported_at' => now(),
            ]);

            // Education
            $facultyId  = $this->resolveByName(Faculty::class, $data['faculty'] ?? null);
            $programId  = $this->resolveByName(Program::class, $data['program'] ?? null);
            $gradYear   = is_numeric($data['graduation_year'] ?? null) ? (int) $data['graduation_year'] : null;

            if ($facultyId || $programId || $gradYear || ($data['degree_program_major'] ?? '')) {
                $gradYearId = $gradYear ? GraduationYear::firstOrCreate(['year' => $gradYear])->id : null;

                AlumniEducation::create([
                    'alumni_id'           => $alumni->id,
                    'faculty_id'          => $facultyId,
                    'program_id'          => $programId,
                    'graduation_year_id'  => $gradYearId,
                    'degree_program_major' => $data['degree_program_major'] ?: null,
                ]);
            }

            // Employment
            $empStateId  = $this->resolveByName(EmploymentState::class, $data['employment_state'] ?? null);
            $empSectorId = $this->resolveByName(EmploymentSector::class, $data['employment_sector'] ?? null);
            $org         = $data['organization'] ?? '';

            if ($empStateId || $empSectorId || $org) {
                AlumniEmployment::create([
                    'alumni_id'            => $alumni->id,
                    'employment_state_id'  => $empStateId,
                    'employment_sector_id' => $empSectorId,
                    'organization'         => $org ?: null,
                    'is_current'           => true,
                ]);
            }

            $created++;
        }

        fclose($handle);

        $this->command->info("Alumni import complete: $created created, $skipped skipped.");
        $this->command->info("Default password = lowercase last name (or 'alumni123' if last name is empty).");
    }

    private function resolveByName(string $model, ?string $name): ?int
    {
        if (!$name) return null;
        return $model::where('name', $name)->value('id');
    }

    private function resolveCountry(?string $name): ?int
    {
        if (!$name) return null;
        return Country::where('name', $name)->value('id');
    }

    private function normaliseGender(?string $value): ?string
    {
        $v = strtolower(trim($value ?? ''));
        if ($v === 'male' || $v === 'm') return 'male';
        if ($v === 'female' || $v === 'f') return 'female';
        return null;
    }

    private function parseBool(?string $value): bool
    {
        $v = strtolower(trim($value ?? ''));
        return in_array($v, ['yes', 'true', '1'], true);
    }
}

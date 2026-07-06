<?php

namespace App\Imports;

use App\Models\Alumni;
use App\Models\AlumniEducation;
use App\Models\AlumniEmployment;
use App\Models\Country;
use App\Models\EmploymentSector;
use App\Models\EmploymentState;
use App\Models\EmploymentYear;
use App\Models\Faculty;
use App\Models\GraduationYear;
use App\Models\Program;
use App\Notifications\AlumniTemporaryPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class AlumniImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $email = trim((string) ($row['email'] ?? $row['email_address'] ?? ''));

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $temporaryPassword = Str::password(10, letters: true, numbers: true, symbols: false);

            $country = $this->country($row['country'] ?? $row['settlement_country'] ?? null);
            $employmentState = $this->employmentState($row['employment_state'] ?? $row['state_of_employment'] ?? null);
            $employmentSector = $this->employmentSector($row['employment_sector'] ?? null);
            $employmentYear = $this->yearModel(EmploymentYear::class, $row['employment_year'] ?? $row['year_of_employment'] ?? null);
            $graduationYear = $this->yearModel(GraduationYear::class, $row['graduation_year'] ?? $row['year_of_graduation'] ?? null);
            $faculty = $this->faculty($row['faculty'] ?? $row['college_school_faculty'] ?? null);
            $program = $this->program($row['program'] ?? $row['degree_program'] ?? null);

            $alumnus = Alumni::updateOrCreate(
                ['email' => $email],
                [
                    'f_name' => $row['f_name'] ?? $row['first_name'] ?? $row['firstname'] ?? 'Unknown',
                    'm_name' => $row['m_name'] ?? $row['middle_name'] ?? null,
                    'l_name' => $row['l_name'] ?? $row['last_name'] ?? $row['lastname'] ?? 'Unknown',
                    'password' => Hash::make($temporaryPassword),
                    'date_of_birth' => $this->dateValue($row['date_of_birth'] ?? $row['dob'] ?? null),
                    'gender' => $this->gender($row['gender'] ?? null),
                    'phone' => $row['phone'] ?? $row['mobile_phone_number'] ?? null,
                    'nida_number' => $this->nullableValue($row['nida_number'] ?? null),
                    'settlement_country_id' => $country?->id,
                    'settlement_region' => $row['region'] ?? $row['province_county_region'] ?? null,
                    'settlement_city' => $row['city'] ?? $row['town_village'] ?? null,
                    'interested_meetings' => $this->yesNo($row['interested_meetings'] ?? null),
                    'interested_social_platform' => $this->yesNo($row['interested_social_platform'] ?? null),
                    'status' => 'pending',
                    'is_active' => false,
                    'imported_at' => now(),
                ]
            );

            AlumniEducation::updateOrCreate(
                ['alumni_id' => $alumnus->id, 'graduation_year_id' => $graduationYear?->id],
                [
                    'faculty_id' => $faculty?->id,
                    'program_id' => $program?->id,
                    'degree_program_major' => $row['degree_program_major'] ?? $row['major'] ?? null,
                ]
            );

            AlumniEmployment::updateOrCreate(
                ['alumni_id' => $alumnus->id, 'is_current' => true],
                [
                    'employment_state_id' => $employmentState?->id,
                    'employment_sector_id' => $employmentSector?->id,
                    'employment_year_id' => $employmentYear?->id,
                    'organization' => $row['organization'] ?? $row['work_organization'] ?? 'NIL',
                ]
            );

            if ($alumnus->wasRecentlyCreated) {
                $alumnus->notify(new AlumniTemporaryPasswordNotification($temporaryPassword));
            }
        }
    }

    private function nullableValue($value): ?string
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    return in_array(strtolower($value), [
        'nil',
        'nill',
        'null',
        'none',
        'n/a',
        'na',
        '-',
        '_',
        'no',
        'not available',
        'not applicable',
    ], true) ? null : $value;
}
    private function country($value): ?Country
    {
        $value = trim((string) $value);
        return $value ? Country::firstOrCreate(['name' => $value], ['code' => null]) : null;
    }

    private function employmentState($value): ?EmploymentState
    {
        $value = trim((string) $value);
        return $value ? EmploymentState::firstOrCreate(['name' => $value]) : null;
    }

    private function employmentSector($value): ?EmploymentSector
    {
        $value = trim((string) $value);
        return $value ? EmploymentSector::firstOrCreate(['name' => $value]) : null;
    }

    private function faculty($value): ?Faculty
    {
        $value = trim((string) $value);
        return $value ? Faculty::where('name', 'like', $value)->first() : null;
    }

    private function program($value): ?Program
    {
        $value = trim((string) $value);
        return $value ? Program::where('name', 'like', $value)->first() : null;
    }

    private function yearModel(string $model, $value)
    {
        preg_match('/\d{4}/', (string) $value, $matches);
        return isset($matches[0]) ? $model::firstOrCreate(['year' => $matches[0]]) : null;
    }

    private function yesNo($value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['yes', 'y', 'true', '1', 'ndiyo'], true);
    }

    private function gender($value): ?string
    {
        $value = strtolower(trim((string) $value));
        return match ($value) {
            'male', 'm', 'man' => 'male',
            'female', 'f', 'woman' => 'female',
            'other' => 'other',
            default => null,
        };
    }

    private function dateValue($value): ?string
    {
        if (!$value) return null;
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}

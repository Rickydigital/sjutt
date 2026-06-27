<?php

namespace App\Console\Commands;

use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class ImportCurrentStudents extends Command
{
    protected $signature = 'students:import-current
                            {file=database/seeders/data/current_students.xls : Excel file path}
                            {--mark-alumni : Mark students not in this Excel as Alumni}';

    protected $description = 'Import current registered students and mark old students as Alumni';

    private array $missingPrograms = [];
    private array $missingFaculties = [];
    private array $duplicates = [];
    private array $seenRegNos = [];

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $markedAlumni = 0;

    public function handle(): int
    {
        $file = base_path($this->argument('file'));

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $rows = Excel::toArray([], $file)[0] ?? [];

        if (count($rows) < 2) {
            $this->error('Excel file is empty.');
            return self::FAILURE;
        }

        $headers = array_map(fn ($h) => $this->cleanText((string) $h), $rows[0]);
        unset($rows[0]);

        $currentRegNos = [];

        foreach ($rows as $row) {
            $row = $this->rowToAssoc($headers, $row);

            $regNo = $this->cleanText((string) ($row['RegNo'] ?? ''));

            if (!$regNo || strtolower($regNo) === 'regno') {
                $this->skipped++;
                continue;
            }

            if (isset($this->seenRegNos[$regNo])) {
                $this->duplicates[] = $regNo;
                $this->skipped++;
                continue;
            }

            $this->seenRegNos[$regNo] = true;
            $currentRegNos[] = $regNo;

            $programmeName = $this->cleanText((string) ($row['ProgrammeName'] ?? ''));
            $class = $this->cleanText((string) ($row['Class'] ?? ''));

            if (!$programmeName || strtolower($programmeName) === 'programmename') {
                $this->skipped++;
                continue;
            }

            $programName = $this->resolveProgramName($programmeName);
            $facultyName = $this->resolveFacultyName($programmeName, $class);

            $program = $this->findProgram($programName);

            if (!$program) {
                $key = "{$programmeName} => {$programName}";

                if (!isset($this->missingPrograms[$key])) {
                    $this->missingPrograms[$key] = [
                        'excel_programme'  => $programmeName,
                        'resolved_program' => $programName,
                        'count'            => 0,
                    ];
                }

                $this->missingPrograms[$key]['count']++;
                $this->skipped++;
                continue;
            }

            $faculty = Faculty::where('program_id', $program->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($facultyName)])
                ->first();

            if (!$faculty) {
                $key = "{$program->name} => {$facultyName}";

                if (!isset($this->missingFaculties[$key])) {
                    $this->missingFaculties[$key] = [
                        'program_id'        => $program->id,
                        'program_name'      => $program->name,
                        'program_short'     => $program->short_name,
                        'excel_programme'   => $programmeName,
                        'excel_class'       => $class,
                        'resolved_faculty'  => $facultyName,
                        'count'             => 0,
                    ];
                }

                $this->missingFaculties[$key]['count']++;
                $this->skipped++;
                continue;
            }

            $payload = [
                'first_name'  => $this->cleanText((string) ($row['First Name'] ?? '')),
                'middle_name' => $this->nullableClean($row['Middle Name'] ?? null),
                'last_name'   => $this->cleanText((string) ($row['Last Name'] ?? '')),
                'form4_index' => $this->nullableClean($row['Form4_Index'] ?? null),
                'gender'      => $this->mapGender($row['Sex'] ?? null),
                'nationality' => $this->nullableClean($row['Nationality'] ?? null),
                'disability'  => $this->nullableClean($row['Disability'] ?? null),
                'program_id'  => $program->id,
                'faculty_id'  => $faculty->id,
                'status'      => 'Active',
            ];

            $student = Student::where('reg_no', $regNo)->first();

            if ($student) {
                $student->update($payload);
                $this->updated++;
            } else {
                Student::create(array_merge($payload, [
                    'reg_no'   => $regNo,
                    'password' => Hash::make($regNo),
                ]));

                $this->created++;
            }
        }

        if ($this->option('mark-alumni')) {
            $this->markedAlumni = Student::whereNotIn('reg_no', $currentRegNos)
                ->update(['status' => 'Alumni']);
        }

        $this->printReport();

        return self::SUCCESS;
    }

    private function rowToAssoc(array $headers, array $row): array
    {
        $assoc = [];

        foreach ($headers as $index => $header) {
            $assoc[$header] = $row[$index] ?? null;
        }

        return $assoc;
    }

    private function cleanText(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value));
    }

    private function nullableClean($value): ?string
    {
        $value = $this->cleanText((string) $value);

        return $value === '' ? null : $value;
    }

    private function findProgram(string $programName): ?Program
    {
        $programName = $this->cleanText($programName);

        return Program::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($programName)])
            ->first();
    }

    private function resolveProgramName(string $programmeName): string
    {
        $programmeName = $this->cleanText($programmeName);

        return match (true) {
            str_contains($programmeName, 'Community Development')
                => 'Institute of Development Studies',

            str_contains($programmeName, 'Medical Laboratory')
                => 'Medical Laboratory Technology',

            str_contains($programmeName, 'Basic Technician Certificate in Pharmaceutical Sciences')
        => 'Pharmaceutical Sciences Training',

    str_contains($programmeName, 'Technician Certificate in Pharmaceutical Science')
        => 'Pharmaceutical Sciences Training',

    str_contains($programmeName, 'Ordinary Diploma in Pharmaceutical Science')
        => 'Pharmaceutical Sciences Training',

            str_contains($programmeName, 'Nursing and Midwifery')
                => 'Nursing and Midwifery Training',

            default => $programmeName,
        };
    }

    private function resolveFacultyName(string $programmeName, string $class): string
    {
        $programmeName = $this->cleanText($programmeName);
        $class = strtolower($this->cleanText($class));

        return match (true) {
            str_contains($programmeName, 'Basic Technician Certificate in Community Development')
                => 'IDS 1',

            str_contains($programmeName, 'Technician Certificate in Community Development')
                => 'IDS 2',

            str_contains($programmeName, 'Ordinary Diploma in Community Development')
                => 'IDS 3',

            str_contains($programmeName, 'Basic Technician Certificate in Medical Laboratory')
                => 'MLT 4',

            str_contains($programmeName, 'Technician Certificate in Medical Laboratory')
                => 'MLT 5',

            str_contains($programmeName, 'Ordinary Diploma in Medical Laboratory')
                => 'MLT 6',

            str_contains($programmeName, 'Basic Technician Certificate in Pharmaceutical')
                => 'PSt 4',

            str_contains($programmeName, 'Technician Certificate in Pharmaceutical')
                => 'PSt 5',

            str_contains($programmeName, 'Ordinary Diploma in Pharmaceutical')
                => 'PSt 6',

            str_contains($programmeName, 'Basic Technician Certificate in Nursing')
                => 'NMT 4',

            str_contains($programmeName, 'Technician Certificate in Nursing')
                => 'NMT 5',

            str_contains($programmeName, 'Ordinary Diploma in Nursing')
                => 'NMT 6',

            default => $this->normalFacultyFromClass($programmeName, $class),
        };
    }

    private function normalFacultyFromClass(string $programmeName, string $class): string
    {
        $programName = $this->resolveProgramName($programmeName);
        $program = $this->findProgram($programName);

        if (!$program) {
            return $class;
        }

        $year = match ($class) {
            'first year'  => 1,
            'second year' => 2,
            'third year'  => 3,
            'fourth year' => 4,
            'fifth year'  => 5,
            'sixth year'  => 6,
            default       => null,
        };

        if (!$year) {
            return $class;
        }

        $shortName = $this->resolveShortName($program->short_name);

        return "{$shortName} {$year}";
    }

    private function resolveShortName(?string $shortName): string
    {
        $shortName = $this->cleanText((string) $shortName);

        return match ($shortName) {
            'BScN' => 'BSN',
            default => $shortName,
        };
    }

    private function mapGender($sex): ?string
    {
        $sex = strtolower($this->cleanText((string) $sex));

        return match ($sex) {
            'm', 'male'   => 'male',
            'f', 'female' => 'female',
            default       => null,
        };
    }

    private function printReport(): void
    {
        $this->info('Import completed.');
        $this->line("Created: {$this->created}");
        $this->line("Updated: {$this->updated}");
        $this->line("Skipped: {$this->skipped}");
        $this->line("Marked Alumni: {$this->markedAlumni}");

        if ($this->duplicates) {
            $this->warn('Duplicate RegNo:');

            foreach ($this->duplicates as $regNo) {
                $this->line("- {$regNo}");
            }
        }

        if ($this->missingPrograms) {
            $this->warn('Missing Programs:');

            foreach ($this->missingPrograms as $item) {
                $this->line(
                    "- Excel: {$item['excel_programme']} | Resolved: {$item['resolved_program']} | Count: {$item['count']}"
                );
            }
        }

        if ($this->missingFaculties) {
            $this->warn('Missing Faculties/Classes:');

            foreach ($this->missingFaculties as $item) {
                $this->line(
                    "- Missing Faculty: {$item['resolved_faculty']} | Program: {$item['program_name']} ({$item['program_short']}) | Program ID: {$item['program_id']} | Excel Programme: {$item['excel_programme']} | Excel Class: {$item['excel_class']} | Count: {$item['count']}"
                );
            }
        }
    }
}
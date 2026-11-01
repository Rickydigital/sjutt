<?php

namespace App\Console\Commands;

use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromoteStudents extends Command
{
    protected $signature = 'students:promote
                            {--program= : Promote only one program ID}
                            {--include-inactive : Include Inactive students as well as Active students}
                            {--execute : Apply changes; without this option the command is a dry run}';

    protected $description = 'Move students to the next faculty/year and mark final-year students as Alumni';

    public function handle(): int
    {
        $programs = Program::query()
            ->with(['faculties' => fn ($query) => $query->orderBy('id')])
            ->when($this->option('program'), fn ($query, $id) => $query->whereKey($id))
            ->orderBy('id')
            ->get();

        if ($programs->isEmpty()) {
            $this->error('No matching program was found.');
            return self::FAILURE;
        }

        $plans = collect();

        foreach ($programs as $program) {
            $facultiesByYear = $this->facultiesByYear($program->faculties);

            if ($facultiesByYear === null) {
                $this->error("{$program->name}: faculty names must end in a unique year number, for example '{$program->short_name} 1'.");
                return self::FAILURE;
            }

            $expectedYears = range(1, (int) $program->total_years);
            if ($facultiesByYear->keys()->values()->all() !== $expectedYears) {
                $this->error("{$program->name}: expected faculty years " . implode(', ', $expectedYears) . ', but found ' . $facultiesByYear->keys()->implode(', ') . '.');
                return self::FAILURE;
            }

            foreach ($facultiesByYear as $year => $faculty) {
                $query = Student::query()
                    ->where('program_id', $program->id)
                    ->where('faculty_id', $faculty->id)
                    ->where('status', '!=', 'Alumni');

                if (!$this->option('include-inactive')) {
                    $query->where('status', 'Active');
                }

                $plans->push([
                    'program' => $program,
                    'from' => $faculty,
                    'to' => $facultiesByYear->get($year + 1),
                    'count' => (clone $query)->count(),
                    'final_year' => $year === (int) $program->total_years,
                ]);
            }
        }

        $this->table(
            ['Program', 'From', 'Destination', 'Students', 'Resulting status'],
            $plans->map(fn (array $plan) => [
                $plan['program']->name,
                $plan['from']->name,
                $plan['final_year'] ? 'Alumni (faculty retained)' : $plan['to']->name,
                $plan['count'],
                $plan['final_year'] ? 'Alumni' : 'unchanged',
            ])->all()
        );

        $total = $plans->sum('count');
        if (!$this->option('execute')) {
            $this->warn("Dry run only: {$total} students would be updated. Add --execute to apply it.");
            return self::SUCCESS;
        }

        DB::transaction(function () use ($plans): void {
            // Process final year downward so a promoted student cannot be
            // promoted twice during the same command run.
            foreach ($plans->reverse() as $plan) {
                $query = Student::query()
                    ->where('program_id', $plan['program']->id)
                    ->where('faculty_id', $plan['from']->id)
                    ->where('status', '!=', 'Alumni');

                if (!$this->option('include-inactive')) {
                    $query->where('status', 'Active');
                }

                if ($plan['final_year']) {
                    $query->update(['status' => 'Alumni']);
                } else {
                    $query->update(['faculty_id' => $plan['to']->id]);
                }
            }

            $facultyIds = $plans
                ->flatMap(fn (array $plan) => [$plan['from']->id, $plan['to']?->id])
                ->filter()
                ->unique();

            foreach ($facultyIds as $facultyId) {
                Faculty::whereKey($facultyId)->update([
                    'total_students_no' => Student::where('faculty_id', $facultyId)
                        ->where('status', 'Active')
                        ->count(),
                ]);
            }
        });

        $this->info("Promotion completed: {$total} students updated.");
        return self::SUCCESS;
    }

    private function facultiesByYear(Collection $faculties): ?Collection
    {
        $mapped = collect();

        foreach ($faculties as $faculty) {
            if (!preg_match('/(\d+)\s*$/', trim($faculty->name), $matches)) {
                return null;
            }

            $year = (int) $matches[1];
            if ($year < 1 || $mapped->has($year)) {
                return null;
            }

            $mapped->put($year, $faculty);
        }

        return $mapped->sortKeys();
    }
}

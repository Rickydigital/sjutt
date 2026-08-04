<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $firstYear = 2025;
        $numberOfYears = 5;

        for ($offset = 0; $offset < $numberOfYears; $offset++) {
            $startYear = $firstYear + $offset;
            $endYear = $startYear + 1;

            AcademicYear::updateOrCreate(
                ['name' => "{$startYear}/{$endYear}"],
                [
                    'start_date' => Carbon::create($startYear, 9, 1)->toDateString(),
                    'end_date' => Carbon::create($endYear, 8, 31)->toDateString(),
                    'status' => $offset === 0 ? 'active' : 'draft',
                    'activated_at' => $offset === 0 ? now() : null,
                ]
            );
        }
    }
}

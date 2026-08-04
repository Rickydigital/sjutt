<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AlmanacSetup;
use Illuminate\Database\Seeder;

class AlmanacDemoSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::query()->latest('id')->first();
        if (!$year) {
            $this->command?->warn('No academic year exists. Demo Almanac was not created.');
            return;
        }

        $setup = AlmanacSetup::firstOrCreate(
            ['academic_year_id' => $year->id, 'title' => 'Academic Almanac for ' . $year->year],
            ['start_date' => $year->start_date, 'end_date' => $year->end_date, 'status' => 'draft']
        );

        $groups = [
            ['name' => 'Degree – SONU & SOPH', 'level' => 'Degree', 'display_order' => 1, 'background_color' => '#bfdbfe'],
            ['name' => 'Degree – FAHE, FaNAS, FOCB, SOTR, CICT', 'level' => 'Degree', 'display_order' => 2, 'background_color' => '#fde68a'],
            ['name' => 'Masters – FAHE, FOCB, IDS & SOPH', 'level' => 'Masters', 'display_order' => 3, 'background_color' => '#bfdbfe'],
            ['name' => 'Non-Degree – SONU & SOPH', 'level' => 'Non-Degree', 'display_order' => 4, 'background_color' => '#fde68a'],
            ['name' => 'Non-Degree – FOCB & IDS', 'level' => 'Non-Degree', 'display_order' => 5, 'background_color' => '#bfdbfe'],
        ];

        foreach ($groups as $group) {
            $setup->programGroups()->firstOrCreate(['name' => $group['name']], $group);
        }
    }
}

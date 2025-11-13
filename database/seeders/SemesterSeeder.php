<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Semester;

class SemesterSeeder extends Seeder
{
    public function run()
    {
        Semester::create(['name' => 'First Semester']);
        Semester::create(['name' => 'Second Semester']);
    }
}
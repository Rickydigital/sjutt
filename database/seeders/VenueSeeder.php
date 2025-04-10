<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run()
    {
        Venue::create([
            'name' => 'St. John\'s University',
            'lat' => -6.1736,
            'lng' => 35.8684,
        ]);
    }
}
<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Permission;

class DatabaseSeeder extends Seeder {
    /**
    * Seed the application's database.
    */

    public function run(): void {

        // $this->call( VenueSeeder::class );
        $this->call( RoleSeeder::class );
        $this->call( CalendarSeeder::class );
    }
}

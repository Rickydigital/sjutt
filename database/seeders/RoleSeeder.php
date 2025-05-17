<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Define roles
        $roles = [
            'User',
            'Admin',
            'Administrator',
            'Dean Of Students',
            'Director',
            'Lecturer',
            'Timetable Officer'
        ];

        // Create roles if they do not exist
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}

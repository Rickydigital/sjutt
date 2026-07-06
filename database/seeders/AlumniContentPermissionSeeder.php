<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AlumniContentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view alumni events',
            'manage alumni events',
            'view alumni calendar',
            'manage alumni calendar',
            'view alumni posts',
            'manage alumni posts',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['Admin', 'Convocation Chair', 'Convectional Chair'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo($permissions);
            $role->givePermissionTo('manage alumni');
        }
    }
}

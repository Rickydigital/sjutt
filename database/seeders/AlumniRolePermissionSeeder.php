<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AlumniRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view alumni',
            'manage alumni',
            'create alumni',
            'edit alumni',
            'import alumni',
            'export alumni',
            'activate alumni',
            'deactivate alumni',
            'suspend alumni',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($permissions);

        $chair = Role::firstOrCreate(['name' => 'Convectional Chair', 'guard_name' => 'web']);
        $chair->syncPermissions($permissions);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AlumniElectionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view alumni elections',
            'manage alumni elections',
            'create alumni elections',
            'edit alumni elections',
            'delete alumni elections',
            'open alumni applications',
            'approve alumni candidates',
            'assign alumni election officers',
            'open alumni voting',
            'close alumni voting',
            'publish alumni results',
            'view alumni election results',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $convocationChair = Role::firstOrCreate(['name' => 'Convocation Chair', 'guard_name' => 'web']);
        $convocationChair->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($permissions);
    }
}

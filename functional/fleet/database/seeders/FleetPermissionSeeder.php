<?php

namespace Functional\Fleet\Database\Seeders;

use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FleetPermissionSeeder extends Seeder
{
    public const PERMISSIONS = ['machines.manage'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        Role::findOrCreate(PermissionSeeder::EMPLOYEE_ROLE)->givePermissionTo(self::PERMISSIONS);
    }
}

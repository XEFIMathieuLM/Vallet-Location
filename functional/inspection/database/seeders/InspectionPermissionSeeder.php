<?php

namespace Functional\Inspection\Database\Seeders;

use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class InspectionPermissionSeeder extends Seeder
{
    public const PERMISSIONS = ['damages.manage', 'inspection_views.manage'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        Role::findOrCreate(PermissionSeeder::EMPLOYEE_ROLE)->givePermissionTo(self::PERMISSIONS);
    }
}

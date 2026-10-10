<?php

namespace Functional\Inspection\Database\Seeders;

use Functional\Inspection\Access\InspectionPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class InspectionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (InspectionPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    }
}

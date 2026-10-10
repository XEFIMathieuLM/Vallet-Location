<?php

namespace Functional\Fleet\Database\Seeders;

use Functional\Fleet\Access\FleetPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FleetPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (FleetPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    }
}

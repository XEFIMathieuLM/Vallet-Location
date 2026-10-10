<?php

namespace Functional\Portal\Database\Seeders;

use Functional\Portal\Access\PortalPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PortalPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PortalPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    }
}

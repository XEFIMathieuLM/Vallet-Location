<?php

namespace Functional\Sales\Database\Seeders;

use Functional\Sales\Access\SalesPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SalesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (SalesPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    }
}

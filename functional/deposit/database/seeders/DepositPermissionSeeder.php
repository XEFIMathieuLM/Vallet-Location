<?php

namespace Functional\Deposit\Database\Seeders;

use Functional\Deposit\Access\DepositPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DepositPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (DepositPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    }
}

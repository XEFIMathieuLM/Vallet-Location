<?php

namespace Functional\Accounts\Database\Seeders;

use Functional\Accounts\Access\AccountsPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AccountsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AccountsPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    }
}

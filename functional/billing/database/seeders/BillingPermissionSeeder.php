<?php

namespace Functional\Billing\Database\Seeders;

use Functional\Billing\Enums\BillingPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class BillingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (BillingPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    }
}

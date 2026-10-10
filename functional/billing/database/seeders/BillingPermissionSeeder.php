<?php

namespace Functional\Billing\Database\Seeders;

use Database\Seeders\PermissionSeeder;
use Functional\Billing\Enums\BillingPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BillingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate(BillingPermission::Manage->value);

        Role::findOrCreate(PermissionSeeder::EMPLOYEE_ROLE)->givePermissionTo(BillingPermission::Manage->value);
    }
}

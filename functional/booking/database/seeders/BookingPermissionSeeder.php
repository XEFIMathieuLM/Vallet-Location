<?php

namespace Functional\Booking\Database\Seeders;

use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BookingPermissionSeeder extends Seeder
{
    public const PERMISSIONS = ['reservations.manage'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        Role::findOrCreate(PermissionSeeder::EMPLOYEE_ROLE)->givePermissionTo(self::PERMISSIONS);
    }
}

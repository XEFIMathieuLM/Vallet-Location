<?php

namespace Functional\Booking\Database\Seeders;

use Functional\Booking\Access\BookingPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class BookingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (BookingPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    }
}

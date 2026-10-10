<?php

namespace Database\Seeders;

use App\Access\AppPermission;
use Functional\Booking\Access\BookingPermission;
use Functional\Booking\Database\Seeders\BookingPermissionSeeder;
use Functional\Fleet\Access\FleetPermission;
use Functional\Fleet\Database\Seeders\FleetPermissionSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const EMPLOYEE_ROLE = 'salarie';

    public function run(): void
    {
        $this->call([FleetPermissionSeeder::class, BookingPermissionSeeder::class]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AppPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Role::findOrCreate(self::EMPLOYEE_ROLE)->givePermissionTo(array_map(
            fn (AppPermission|FleetPermission|BookingPermission $permission): string => $permission->value,
            [...AppPermission::cases(), ...FleetPermission::cases(), ...BookingPermission::cases()],
        ));
    }
}

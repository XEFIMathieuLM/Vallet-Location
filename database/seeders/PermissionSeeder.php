<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const EMPLOYEE_ROLE = 'salarie';

    public const PERMISSIONS = ['machines.manage', 'reservations.manage', 'users.manage'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        Role::findOrCreate(self::EMPLOYEE_ROLE)->syncPermissions(self::PERMISSIONS);
    }
}

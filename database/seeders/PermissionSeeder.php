<?php

namespace Database\Seeders;

use App\Access\AppPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const EMPLOYEE_ROLE = 'salarie';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AppPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Role::findOrCreate(self::EMPLOYEE_ROLE)->givePermissionTo(Permission::all());
    }
}

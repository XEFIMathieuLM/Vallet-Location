<?php

namespace Functional\Certification\Database\Seeders;

use Functional\Certification\Enums\CertificationPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CertificationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (CertificationPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }
    }
}

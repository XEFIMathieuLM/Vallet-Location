<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Functional\Fleet\Database\Seeders\FleetPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_employee_role_gets_every_permission_created_by_the_layers(): void
    {
        $this->seed(FleetPermissionSeeder::class);
        Permission::findOrCreate('upper-layer.manage');

        $this->seed(PermissionSeeder::class);

        $employeeRole = Role::findByName(PermissionSeeder::EMPLOYEE_ROLE);
        $this->assertTrue($employeeRole->hasPermissionTo('upper-layer.manage'));
        $this->assertTrue($employeeRole->hasPermissionTo('machines.manage'));
        $this->assertTrue($employeeRole->hasPermissionTo('users.manage'));
    }

    public function test_a_layer_permission_seeder_never_touches_the_role(): void
    {
        $this->seed(FleetPermissionSeeder::class);

        $this->assertFalse(Role::query()->where('name', PermissionSeeder::EMPLOYEE_ROLE)->exists());
    }
}

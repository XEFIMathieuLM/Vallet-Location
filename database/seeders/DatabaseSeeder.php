<?php

namespace Database\Seeders;

use App\Models\User;
use Functional\Fleet\Database\Seeders\FleetSeeder;
use Functional\Fleet\Models\Agency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([FleetSeeder::class, PermissionSeeder::class]);

        Agency::query()->orderBy('name')->each(function (Agency $agency): void {
            User::factory()
                ->create([
                    'name' => "Salarié {$agency->name}",
                    'email' => Str::slug($agency->name).'@vallet-location.test',
                    'agency_id' => $agency->id,
                ])
                ->assignRole(PermissionSeeder::EMPLOYEE_ROLE);
        });
    }
}

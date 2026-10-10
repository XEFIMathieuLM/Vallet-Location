<?php

namespace Database\Seeders;

use App\Models\User;
use Functional\Booking\Database\Seeders\CustomerSeeder;
use Functional\Booking\Database\Seeders\ReservationSeeder;
use Functional\Fleet\Database\Seeders\FleetSeeder;
use Functional\Fleet\Models\Agency;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([FleetSeeder::class, PermissionSeeder::class]);

        $employees = Agency::query()->orderBy('name')->get()
            ->map(fn (Agency $agency): User => User::factory()->employee()->for($agency)->create());

        $this->call([CustomerSeeder::class, ReservationSeeder::class]);

        $this->command->table(
            ['Agency', 'Employee e-mail'],
            $employees->map(fn (User $employee): array => [$employee->agency->name, $employee->email])->all(),
        );
        $this->command->info('Password of every seeded employee: the UserFactory default.');
    }
}

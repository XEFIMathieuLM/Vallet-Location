<?php

namespace Database\Seeders;

use App\Models\User;
use Functional\Billing\Database\Seeders\BillingPermissionSeeder;
use Functional\Billing\Database\Seeders\BillingSeeder;
use Functional\Booking\Database\Seeders\BookingPermissionSeeder;
use Functional\Booking\Database\Seeders\CustomerSeeder;
use Functional\Booking\Database\Seeders\ReservationSeeder;
use Functional\Deposit\Database\Seeders\DepositDemoSeeder;
use Functional\Deposit\Database\Seeders\DepositPermissionSeeder;
use Functional\Fleet\Database\Seeders\FleetPermissionSeeder;
use Functional\Fleet\Database\Seeders\FleetSeeder;
use Functional\Fleet\Models\Agency;
use Functional\Inspection\Database\Seeders\InspectionPermissionSeeder;
use Functional\Inspection\Database\Seeders\InspectionSeeder;
use Functional\Sales\Database\Seeders\SalesDemoSeeder;
use Functional\Sales\Database\Seeders\SalesPermissionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([FleetPermissionSeeder::class, BookingPermissionSeeder::class, InspectionPermissionSeeder::class, BillingPermissionSeeder::class, DepositPermissionSeeder::class, SalesPermissionSeeder::class, PermissionSeeder::class, FleetSeeder::class]);

        $employees = Agency::query()->orderBy('name')->get()
            ->map(fn (Agency $agency): User => User::factory()->employee()->for($agency)->create());

        $this->call([CustomerSeeder::class, ReservationSeeder::class, InspectionSeeder::class, BillingSeeder::class, DepositDemoSeeder::class, SalesDemoSeeder::class]);

        $this->command->table(
            ['Agency', 'Employee e-mail'],
            $employees->map(fn (User $employee): array => [$employee->agency->name, $employee->email])->all(),
        );
        $this->command->info('Password of every seeded employee: the UserFactory default.');
    }
}

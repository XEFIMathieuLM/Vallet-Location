<?php

namespace Functional\Booking\Database\Seeders;

use Functional\Booking\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::factory()->count(15)->create();
        Customer::factory()->reachableByEmail()->count(5)->create();
    }
}

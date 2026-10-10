<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Billing\Money\Money;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Actions\ResolveDepositAmount;
use Functional\Deposit\Models\DepositRate;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveDepositAmountTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_migration_creates_a_single_default_amount_of_1500_euros(): void
    {
        $defaultRates = DepositRate::query()->whereNull('machine_category_id')->get();

        $this->assertCount(1, $defaultRates);
        $this->assertTrue($defaultRates->firstOrFail()->amount->equals(Money::fromStored(150000)));
    }

    public function test_a_category_without_its_own_amount_uses_the_default_amount(): void
    {
        $category = MachineCategory::factory()->create();

        $this->assertTrue(app(ResolveDepositAmount::class)->forCategory($category)->equals(Money::fromStored(150000)));
    }

    public function test_a_category_with_its_own_amount_uses_it(): void
    {
        $category = MachineCategory::factory()->create();
        DepositRate::factory()->forCategory($category)->create(['amount' => Money::fromStored(300000)]);

        $this->assertTrue(app(ResolveDepositAmount::class)->forCategory($category)->equals(Money::fromStored(300000)));
    }

    public function test_a_reservation_uses_the_amount_of_its_machine_category(): void
    {
        $category = MachineCategory::factory()->create();
        DepositRate::factory()->forCategory($category)->create(['amount' => Money::fromStored(300000)]);
        $reservation = Reservation::factory()->for(Machine::factory()->for($category, 'category'))->create();

        $this->assertTrue(app(ResolveDepositAmount::class)->forReservation($reservation)->equals(Money::fromStored(300000)));
    }

    public function test_the_database_refuses_a_second_default_amount(): void
    {
        $this->expectException(QueryException::class);

        DepositRate::factory()->default()->create();
    }

    public function test_the_database_refuses_two_amounts_for_the_same_category(): void
    {
        $category = MachineCategory::factory()->create();
        DepositRate::factory()->forCategory($category)->create();

        $this->expectException(QueryException::class);

        DepositRate::factory()->forCategory($category)->create();
    }
}

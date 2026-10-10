<?php

namespace Functional\Accounts\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Accounts\Models\KeyAccount;
use Functional\Booking\Livewire\CreateReservationForm;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class KeyAccountBadgeTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-11-01'));
    }

    public function test_a_key_account_is_flagged_in_the_reservation_form(): void
    {
        $keyAccount = KeyAccount::factory()->create();
        $keyAccount->customer->update(['name' => 'Bâti-Ouest']);
        Customer::factory()->create(['name' => 'Bâti-Est']);

        $this->actingAs($this->employee());
        Livewire::test(CreateReservationForm::class, ['machineId' => Machine::factory()->create()->id])
            ->set('customerSearch', 'Bâti')
            ->assertSee('Bâti-Ouest · Professionnel — Grand compte')
            ->assertDontSee('Bâti-Est · Professionnel — Grand compte')
            ->set('customerId', $keyAccount->customer_id)
            ->assertSee('Tarif négocié appliqué par la facturation')
            ->assertSee('bon de commande exigé avant la sortie');
    }

    public function test_the_badges_cost_the_same_queries_whatever_the_number_of_customers(): void
    {
        $this->actingAs($this->employee());
        $machine = Machine::factory()->create();
        KeyAccount::factory()->count(2)->create();
        $fewCustomers = $this->countQueriesWhileRendering($machine);

        KeyAccount::factory()->count(8)->create();
        Customer::factory()->count(8)->create();

        $this->assertSame($fewCustomers, $this->countQueriesWhileRendering($machine));
    }

    private function countQueriesWhileRendering(Machine $machine): int
    {
        $component = Livewire::test(CreateReservationForm::class, ['machineId' => $machine->id]);
        $queries = 0;
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries++;
        });
        $component->call('$refresh');

        return $queries;
    }
}

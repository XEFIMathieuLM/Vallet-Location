<?php

namespace Functional\Accounts\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Accounts\Access\AccountsPermission;
use Functional\Accounts\Livewire\MissingPurchaseOrders;
use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MissingPurchaseOrdersTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00:00', 'Europe/Paris'));
    }

    public function test_key_account_reservations_without_number_are_listed_by_departure_date(): void
    {
        $customer = KeyAccount::factory()->create()->customer;
        $inTenDays = $this->reservationOf($customer, 10, 'NAC-0010');
        $inTwoDays = $this->reservationOf($customer, 2, 'NAC-0002');
        ReservationPurchaseOrder::factory()->for($this->reservationOf($customer, 5, 'NAC-WITH'))->create();
        $this->reservationOf($customer, 4, 'NAC-CANCELLED')->update(['status' => 'cancelled']);
        $this->reservationOf(Customer::factory()->professional()->create(), 3, 'NAC-ORDINARY');

        $this->actingAs($this->employee());
        Livewire::test(MissingPurchaseOrders::class)
            ->assertSeeInOrder(['NAC-0002', 'NAC-0010'])
            ->assertSee($customer->name)
            ->assertSee($inTwoDays->start_date->format('d/m/Y'))
            ->assertDontSee('NAC-WITH')
            ->assertDontSee('NAC-CANCELLED')
            ->assertDontSee('NAC-ORDINARY')
            ->assertViewHas('reservations', fn ($reservations): bool => $reservations->pluck('id')->all() === [$inTwoDays->id, $inTenDays->id]);
    }

    public function test_a_departure_within_three_days_is_highlighted(): void
    {
        $customer = KeyAccount::factory()->create()->customer;
        $soon = $this->reservationOf($customer, 3, 'NAC-SOON');
        $later = $this->reservationOf($customer, 4, 'NAC-LATER');

        $this->actingAs($this->employee());
        Livewire::test(MissingPurchaseOrders::class)
            ->assertViewHas('highlightedIds', fn (array $highlightedIds): bool => in_array($soon->id, $highlightedIds, true) && ! in_array($later->id, $highlightedIds, true));
    }

    public function test_entering_a_number_inline_removes_the_reservation_from_the_list(): void
    {
        $reservation = $this->reservationOf(KeyAccount::factory()->create()->customer, 2, 'NAC-0002');

        $this->actingAs($this->employee());
        Livewire::test(MissingPurchaseOrders::class)
            ->set("numbers.{$reservation->id}", 'BC-2026-0412')
            ->call('save', $reservation->id)
            ->assertHasNoErrors()
            ->assertDontSee('NAC-0002');
        $this->assertSame('BC-2026-0412', ReservationPurchaseOrder::query()->where('reservation_id', $reservation->id)->value('number'));
    }

    public function test_the_list_is_filtered_by_the_home_agency_of_the_machine(): void
    {
        $customer = KeyAccount::factory()->create()->customer;
        $rouen = Agency::factory()->create();
        $this->reservationOf($customer, 2, 'NAC-ROUEN', $rouen);
        $this->reservationOf($customer, 2, 'NAC-CAEN');

        $this->actingAs($this->employee());
        Livewire::test(MissingPurchaseOrders::class)
            ->set('agencyId', $rouen->id)
            ->assertSee('NAC-ROUEN')
            ->assertDontSee('NAC-CAEN');
    }

    public function test_the_screen_requires_the_purchase_orders_permission(): void
    {
        $this->actingAs($this->userWithoutPermission())->get(route('accounts.missing-purchase-orders'))->assertForbidden();
        $this->actingAs($this->userWithPermissions(AccountsPermission::ManagePurchaseOrders))->get(route('accounts.missing-purchase-orders'))->assertOk();
    }

    public function test_the_list_costs_the_same_queries_whatever_its_length(): void
    {
        $this->actingAs($this->employee());
        $customer = KeyAccount::factory()->create()->customer;
        $this->reservationOf($customer, 2, 'NAC-A');
        $fewLines = $this->countRenderQueries();

        foreach (range(1, 6) as $position) {
            $this->reservationOf(KeyAccount::factory()->create()->customer, $position, "NAC-B{$position}");
        }

        $this->assertSame($fewLines, $this->countRenderQueries());
    }

    private function reservationOf(Customer $customer, int $daysBeforeDeparture, string $machineReference, ?Agency $homeAgency = null): Reservation
    {
        $startDate = CarbonImmutable::today()->addDays($daysBeforeDeparture);
        $machine = Machine::factory()->for($homeAgency ?? Agency::factory()->create())->create(['reference' => $machineReference]);

        return Reservation::factory()->for($customer)->for($machine)->between($startDate, $startDate->addDays(2))->create();
    }

    private function countRenderQueries(): int
    {
        $component = Livewire::test(MissingPurchaseOrders::class);
        $queries = 0;
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries++;
        });
        $component->call('$refresh');

        return $queries;
    }
}

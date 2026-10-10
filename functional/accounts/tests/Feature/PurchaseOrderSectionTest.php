<?php

namespace Functional\Accounts\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Accounts\Livewire\PurchaseOrderSection;
use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Accounts\Tests\Concerns\BuildsKeyAccountReservations;
use Functional\Booking\Livewire\CreateReservationForm;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PurchaseOrderSectionTest extends TestCase
{
    use BuildsKeyAccountReservations, CreatesUsers, RefreshDatabase;

    private const SECTION = 'accounts.purchase-order-section';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
    }

    public function test_the_section_asks_for_the_number_of_a_key_account_and_blocks_departure(): void
    {
        $this->actingAs($this->employee());

        Livewire::test(PurchaseOrderSection::class, ['reservation' => $this->keyAccountReservation()])
            ->assertSee('Exigé, à saisir')
            ->assertSee('tarif négocié appliqué par la facturation')
            ->assertDispatched('reservation-transition-readiness', step: 'departure', section: self::SECTION, is_ready: false);
    }

    public function test_entering_the_number_shows_it_and_makes_departure_ready(): void
    {
        $author = $this->employee();
        $this->actingAs($author);
        $reservation = $this->keyAccountReservation();

        Livewire::test(PurchaseOrderSection::class, ['reservation' => $reservation])
            ->set('number', 'BC-2026-0412')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('BC-2026-0412')
            ->assertSee($author->getAttribute('name'))
            ->assertDispatched('reservation-transition-readiness', step: 'departure', section: self::SECTION, is_ready: true);

        $purchaseOrder = ReservationPurchaseOrder::query()->where('reservation_id', $reservation->id)->sole();
        $this->assertSame($author->agencyId(), $purchaseOrder->agency_id);
        $this->assertSame(1, Activity::query()->where('log_name', 'accounts')->where('event', 'purchase_order_entered')->count());
    }

    public function test_the_number_is_entered_on_the_detail_opened_after_creating_the_reservation(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-01'));
        $this->actingAs($this->employee());
        $customer = KeyAccount::factory()->create()->customer;

        Livewire::test(CreateReservationForm::class, ['machineId' => Machine::factory()->create()->id])
            ->set('startDate', '2026-11-10')
            ->set('endDate', '2026-11-14')
            ->set('customerId', $customer->id)
            ->call('save');
        $reservation = Reservation::query()->where('customer_id', $customer->id)->sole();

        $this->get(route('reservations.show', $reservation))->assertOk()->assertSee('Exigé, à saisir');
        Livewire::test(PurchaseOrderSection::class, ['reservation' => $reservation])->set('number', 'BC-9')->call('save')->assertHasNoErrors();
        $this->assertSame('BC-9', ReservationPurchaseOrder::query()->where('reservation_id', $reservation->id)->value('number'));
    }

    public function test_the_section_of_an_individual_is_hidden_but_announces_departure_ready(): void
    {
        $this->actingAs($this->employee());

        Livewire::test(PurchaseOrderSection::class, ['reservation' => $this->reservationStartingToday(Customer::factory()->individual()->create())])
            ->assertDontSee('Bon de commande')
            ->assertDispatched('reservation-transition-readiness', step: 'departure', section: self::SECTION, is_ready: true);
    }

    public function test_the_section_of_an_ordinary_professional_offers_an_optional_number(): void
    {
        $this->actingAs($this->employee());

        Livewire::test(PurchaseOrderSection::class, ['reservation' => $this->reservationStartingToday(Customer::factory()->professional()->create())])
            ->assertSee('Facultatif')
            ->assertDispatched('reservation-transition-readiness', step: 'departure', section: self::SECTION, is_ready: true);
    }

    public function test_saving_requires_the_purchase_orders_permission(): void
    {
        $this->actingAs($this->userWithoutPermission());

        Livewire::test(PurchaseOrderSection::class, ['reservation' => $this->keyAccountReservation()])
            ->set('number', 'BC-1')
            ->call('save')
            ->assertForbidden();
    }
}

<?php

namespace Functional\Portal\Tests\Feature\Customer;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Functional\Portal\Livewire\Customer\MyReservations;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyReservationsTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        $this->customer = Customer::factory()->create();
    }

    public function test_an_attached_account_sees_every_reservation_of_its_record_without_internal_information(): void
    {
        $this->actingAs($this->attachedCustomerAccount($this->customer), 'customer');
        $rouen = Agency::factory()->create(['name' => 'Rouen']);
        $counterReservation = Reservation::factory()->for($this->customer)->for($this->reservableMachine(agency: $rouen, attributes: ['reference' => 'NAC-COMPTOIR']))
            ->between(CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-12'))
            ->create(['conflict_reason' => ConflictReason::MachineUnavailable]);
        Reservation::factory()->for($this->customer)->for($this->reservableMachine(attributes: ['reference' => 'NAC-FINIE']))->closed()->create();
        Reservation::factory()->for($this->reservableMachine(attributes: ['reference' => 'NAC-AUTRE-CLIENT']))->create();

        $this->get(route('portal.reservations'))->assertOk();
        Livewire::test(MyReservations::class)
            ->assertSee(['NAC-COMPTOIR', '10/11/2026', '12/11/2026', 'Rouen', __('portal::reservations.statuses.confirmed')])
            ->assertSee(['NAC-FINIE', __('portal::reservations.statuses.finished')])
            ->assertSee(__('portal::reservations.contact_agency', ['agency' => 'Rouen']))
            ->assertDontSee('NAC-AUTRE-CLIENT')
            ->assertDontSee(ConflictReason::MachineUnavailable->label())
            ->assertDontSee(__('portal::reservations.cancel_reservation'));
        $this->assertTrue($counterReservation->exists);
    }

    public function test_an_unattached_account_sees_an_explanation_instead_of_reservations(): void
    {
        $this->actingAs($this->customerAccount(), 'customer');
        Reservation::factory()->for($this->customer)->create();

        Livewire::test(MyReservations::class)->assertSee(__('portal::reservations.unattached'));
    }

    public function test_every_account_of_the_same_record_sees_the_same_reservations(): void
    {
        $reservation = Reservation::factory()->for($this->customer)->for($this->reservableMachine(attributes: ['reference' => 'NAC-PARTAGEE']))->create();
        $this->attachedCustomerAccount($this->customer);

        foreach (range(1, 2) as $colleague) {
            $this->actingAs($this->attachedCustomerAccount($this->customer), 'customer');
            Livewire::test(MyReservations::class)->assertSee('NAC-PARTAGEE');
        }
        $this->assertTrue($reservation->exists);
    }

    public function test_a_reservation_cancelled_by_the_agency_shows_cancelled_while_its_request_stays_confirmed(): void
    {
        $account = $this->attachedCustomerAccount($this->customer);
        $this->actingAs($account, 'customer');
        $reservation = Reservation::factory()->for($this->customer)->for($this->reservableMachine(attributes: ['reference' => 'NAC-ANNULEE']))->withStatus(ReservationStatus::Cancelled)->create();
        ReservationRequest::factory()->for($account, 'account')->confirmed($reservation)->create();

        Livewire::test(MyReservations::class)->assertSee(['NAC-ANNULEE', __('portal::reservations.statuses.cancelled')]);
        $this->assertSame('confirmed', ReservationRequest::query()->sole()->status->value);
    }
}

<?php

namespace Functional\Billing\Tests\Feature;

use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Livewire\ReservationBillingSection;
use Functional\Billing\Livewire\Transmissions;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Billing\Tests\Concerns\RecordsReservationLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FailedTransmissionsTest extends TestCase
{
    use BuildsBillingFixtures, RecordsReservationLifecycle, RefreshDatabase;

    public function test_a_transmission_refused_for_an_unknown_customer_is_listed_with_its_reason(): void
    {
        $this->actingAs($this->employee());
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00', hasBillingAccount: false);
        $this->recordReturn($reservation, '2026-11-14 17:00:00');

        $this->get(route('billing.transmissions'))
            ->assertOk()
            ->assertSee($reservation->machine->reference)
            ->assertSee($reservation->customer->name)
            ->assertSee('Période finale')
            ->assertSee('14/11/2026')
            ->assertSee('Client inconnu du logiciel de facturation');
    }

    public function test_once_the_customer_reference_is_set_a_retry_transmits_and_leaves_the_list(): void
    {
        $this->actingAs($this->employee());
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00', hasBillingAccount: false);
        $this->recordReturn($reservation, '2026-11-14 17:00:00');
        $transmission = Transmission::query()->where('reservation_id', $reservation->id)->sole();

        Livewire::test(Transmissions::class)
            ->set("customerRefs.{$reservation->customer_id}", 'CLI-4521')
            ->call('saveCustomerRef', $reservation->customer_id)
            ->assertDispatched('toast-show')
            ->call('retry', $transmission->id)
            ->assertDispatched('toast-show')
            ->assertHasNoErrors()
            ->assertDontSee($reservation->machine->reference);

        $this->assertSame(TransmissionStatus::Sent, $transmission->refresh()->status);
        $this->assertSame('CLI-4521', $this->fakeGateway()->received()[$transmission->uuid]['customer_ref']);
    }

    public function test_retrying_a_sent_transmission_is_refused(): void
    {
        $this->actingAs($this->employee());
        $transmission = Transmission::factory()->sent()->create();

        Livewire::test(Transmissions::class)
            ->call('retry', $transmission->id)
            ->assertHasErrors(['refusal' => 'Impossible de relancer une transmission à l\'état « Transmise ».']);

        $this->assertSame(TransmissionStatus::Sent, $transmission->refresh()->status);
    }

    public function test_the_screen_requires_the_billing_permission(): void
    {
        $this->seedPermissions();
        $this->actingAs($this->userWithoutPermission());

        $this->get(route('billing.transmissions'))->assertForbidden();
    }

    public function test_a_failed_transmission_can_be_retried_from_the_reservation_billing_section(): void
    {
        $this->actingAs($this->employee());
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $period = BillablePeriod::factory()->create(['reservation_id' => $reservation->id]);
        $transmission = Transmission::factory()->failed()->create(['billable_period_id' => $period->id, 'reservation_id' => $reservation->id]);

        Livewire::test(ReservationBillingSection::class, ['reservation' => $reservation])
            ->assertSee('Relancer')
            ->call('retry', $transmission->id)
            ->assertHasNoErrors()
            ->assertDontSee('Relancer');

        $this->assertSame(TransmissionStatus::Sent, $transmission->refresh()->status);
    }

    public function test_the_billing_section_retries_only_its_own_reservation_transmissions_and_requires_the_permission(): void
    {
        $this->actingAs($this->employee());
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $otherTransmission = Transmission::factory()->failed()->create();

        Livewire::test(ReservationBillingSection::class, ['reservation' => $reservation])
            ->call('retry', $otherTransmission->id)
            ->assertNotFound();

        $this->actingAs($this->userWithoutPermission());
        Livewire::test(ReservationBillingSection::class, ['reservation' => $reservation])
            ->call('retry', $otherTransmission->id)
            ->assertForbidden();
        $this->assertSame(TransmissionStatus::Failed, $otherTransmission->refresh()->status);
    }
}

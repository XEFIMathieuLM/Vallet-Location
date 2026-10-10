<?php

namespace Functional\Billing\Tests\Feature;

use App\Models\User;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Livewire\Transmissions;
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
            ->call('retry', $transmission->id)
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
            ->assertHasErrors('refusal');

        $this->assertSame(TransmissionStatus::Sent, $transmission->refresh()->status);
    }

    public function test_the_screen_requires_the_billing_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('billing.transmissions'))->assertForbidden();
    }
}

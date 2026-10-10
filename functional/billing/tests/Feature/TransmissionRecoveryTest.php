<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\FakeGatewayMode;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Billing\Tests\Concerns\RecordsReservationLifecycle;
use Functional\Booking\Enums\ReservationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransmissionRecoveryTest extends TestCase
{
    use BuildsBillingFixtures, RecordsReservationLifecycle, RefreshDatabase;

    public function test_the_return_is_recorded_while_the_billing_software_is_unreachable_and_left_pending(): void
    {
        $this->fakeGateway()->switchTo(FakeGatewayMode::Unreachable);
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');

        $this->recordReturn($reservation, '2026-11-14 17:00:00');

        $this->assertSame(ReservationStatus::Closed, $reservation->status);
        $transmission = Transmission::query()->where('reservation_id', $reservation->id)->sole();
        $this->assertSame(TransmissionStatus::Pending, $transmission->status);
        $this->assertNotNull($transmission->next_attempt_at);
    }

    public function test_a_pending_transmission_leaves_on_its_own_once_the_billing_software_is_back(): void
    {
        $this->fakeGateway()->switchTo(FakeGatewayMode::Unreachable);
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');
        $this->recordReturn($reservation, '2026-11-14 17:00:00');
        $this->fakeGateway()->switchTo(FakeGatewayMode::Accept);

        $this->reconcileOn('2026-11-14 17:01:00');

        $this->assertSame(TransmissionStatus::Sent, Transmission::query()->where('reservation_id', $reservation->id)->sole()->status);
    }

    public function test_a_closed_reservation_whose_event_was_lost_is_caught_up(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');

        $this->reconcileOn('2026-11-14 17:05:00');

        $this->assertSingleFinalPeriod($reservation, '2026-11-10', '2026-11-14', 5);
        $this->assertSame(TransmissionStatus::Sent, Transmission::query()->where('reservation_id', $reservation->id)->sole()->status);
    }

    public function test_a_reservation_returned_before_go_live_is_not_caught_up(): void
    {
        config(['billing.go_live_date' => '2026-11-15']);
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');

        $this->reconcileOn('2026-11-15 10:00:00');

        $this->assertSame([], $this->periodsOf($reservation));
    }

    public function test_a_transmission_whose_retry_is_not_due_is_not_sent(): void
    {
        $this->fakeGateway()->switchTo(FakeGatewayMode::Unreachable);
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');
        $this->recordReturn($reservation, '2026-11-14 17:00:00');
        $this->fakeGateway()->switchTo(FakeGatewayMode::Accept);

        $this->reconcileOn('2026-11-14 17:00:30');

        $transmission = Transmission::query()->where('reservation_id', $reservation->id)->sole();
        $this->assertSame(TransmissionStatus::Pending, $transmission->status);
        $this->assertSame(1, $transmission->attempts);
    }

    public function test_reconcile_reports_each_caught_up_reservation_and_resent_transmission(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $pendingTransmission = Transmission::factory()->create();
        CarbonImmutable::setTestNow('2026-11-14 17:05:00');

        $this->artisan('billing:reconcile')
            ->expectsOutputToContain("Recording the final period of reservation #{$reservation->id}.")
            ->expectsOutputToContain("Sending transmission #{$pendingTransmission->id}.")
            ->expectsOutputToContain('Reconciled 1 reservation(s) and queued 1 transmission(s).')
            ->assertSuccessful();
    }

    private function reconcileOn(string $moment): void
    {
        CarbonImmutable::setTestNow($moment);
        $this->artisan('billing:reconcile')->assertSuccessful();
    }
}

<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\SendTransmission;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Enums\FakeGatewayMode;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Booking\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendTransmissionTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    public function test_an_accepted_transmission_is_sent_with_every_field_of_the_rental_period(): void
    {
        CarbonImmutable::setTestNow('2026-11-14 18:00:00');
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $transmission = $this->periodTransmission($reservation, '2026-11-10', '2026-11-14', BillablePeriodKind::Final);

        app(SendTransmission::class)->handle($transmission);

        $transmission->refresh();
        $this->assertSame(TransmissionStatus::Sent, $transmission->status);
        $this->assertNotNull($transmission->external_ref);
        $this->assertNotNull($transmission->sent_at);
        $reservation->load('machine.category', 'machine.agency', 'agency');
        $this->assertSame([
            'idempotency_key' => $transmission->uuid,
            'type' => 'rental_period',
            'customer_ref' => CustomerBillingAccount::query()->where('customer_id', $reservation->customer_id)->value('external_ref'),
            'reservation_ref' => (string) $reservation->id,
            'machine_reference' => $reservation->machine->reference,
            'machine_category' => $reservation->machine->category->name,
            'home_agency' => $reservation->machine->agency->name,
            'booking_agency' => $reservation->agency->name,
            'period_start' => '2026-11-10',
            'period_end' => '2026-11-14',
            'period_kind' => 'final',
            'days' => 5,
            'damage_view' => null,
            'damage_comment' => null,
            'label' => null,
            'amount_excl_tax_cents' => null,
        ], $this->fakeGateway()->received()[$transmission->uuid]);
    }

    public function test_an_accepted_damage_transmission_carries_the_damage_fields_and_no_period(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $damage = $this->unresolvedDamage($reservation, 'Gauche', 'Capot enfoncé');
        $settlement = DamageSettlement::factory()->billed(45000, 'Remplacement capot')->create(['damage_id' => $damage->id]);
        $transmission = Transmission::factory()->create(['billable_period_id' => null, 'damage_settlement_id' => $settlement->id, 'reservation_id' => $reservation->id]);

        app(SendTransmission::class)->handle($transmission);

        $line = $this->fakeGateway()->received()[$transmission->uuid];
        $this->assertSame('damage', $line['type']);
        $this->assertSame((string) $reservation->id, $line['reservation_ref']);
        $this->assertSame('Gauche', $line['damage_view']);
        $this->assertSame('Capot enfoncé', $line['damage_comment']);
        $this->assertSame('Remplacement capot', $line['label']);
        $this->assertSame(45000, $line['amount_excl_tax_cents']);
        $this->assertNull($line['period_start']);
        $this->assertNull($line['period_end']);
        $this->assertNull($line['period_kind']);
        $this->assertNull($line['days']);
    }

    public function test_a_customer_without_billing_reference_fails_without_calling_the_billing_software(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00', hasBillingAccount: false);
        $transmission = $this->periodTransmission($reservation, '2026-11-10', '2026-11-14', BillablePeriodKind::Final);

        app(SendTransmission::class)->handle($transmission);

        $transmission->refresh();
        $this->assertSame(TransmissionStatus::Failed, $transmission->status);
        $this->assertSame(TransmissionFailureReason::CustomerUnknown, $transmission->failure_reason);
        $this->assertSame([], $this->fakeGateway()->received());
    }

    public function test_a_rejected_transmission_fails_with_the_reason_given_by_the_billing_software(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $transmission = $this->periodTransmission($reservation, '2026-11-10', '2026-11-14', BillablePeriodKind::Final);
        $this->fakeGateway()->reject($transmission->uuid, 'Machine inconnue');

        app(SendTransmission::class)->handle($transmission);

        $transmission->refresh();
        $this->assertSame(TransmissionStatus::Failed, $transmission->status);
        $this->assertSame(TransmissionFailureReason::Rejected, $transmission->failure_reason);
        $this->assertSame('Machine inconnue', $transmission->last_error);
    }

    public function test_an_unreachable_billing_software_keeps_the_transmission_pending_with_growing_retry_delays(): void
    {
        CarbonImmutable::setTestNow('2026-11-14 18:00:00');
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $transmission = $this->periodTransmission($reservation, '2026-11-10', '2026-11-14', BillablePeriodKind::Final);
        $this->fakeGateway()->switchTo(FakeGatewayMode::Unreachable);

        foreach ([1, 5, 15, 60, 60] as $attempt => $expectedDelayMinutes) {
            app(SendTransmission::class)->handle($transmission);

            $transmission->refresh();
            $this->assertSame(TransmissionStatus::Pending, $transmission->status);
            $this->assertSame($attempt + 1, $transmission->attempts);
            $this->assertEquals(CarbonImmutable::now()->addMinutes($expectedDelayMinutes), $transmission->next_attempt_at);
            $this->assertEquals(CarbonImmutable::now(), $transmission->last_attempt_at);
        }
    }

    public function test_a_sent_or_exported_transmission_is_never_sent_again(): void
    {
        $sent = Transmission::factory()->sent()->create();
        $exported = Transmission::factory()->exported()->create();

        app(SendTransmission::class)->handle($sent);
        app(SendTransmission::class)->handle($exported);

        $this->assertSame([], $this->fakeGateway()->received());
        $this->assertSame(1, $sent->refresh()->attempts);
    }

    public function test_sending_the_same_transmission_twice_reaches_the_billing_software_once(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $transmission = $this->periodTransmission($reservation, '2026-11-10', '2026-11-14', BillablePeriodKind::Final);

        app(SendTransmission::class)->handle($transmission);
        app(SendTransmission::class)->handle($transmission);

        $this->assertCount(1, $this->fakeGateway()->received());
        $this->assertSame(1, $transmission->refresh()->attempts);
    }

    private function periodTransmission(Reservation $reservation, string $startDate, string $endDate, BillablePeriodKind $kind): Transmission
    {
        $period = BillablePeriod::factory()
            ->between(CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate), $kind)
            ->create(['reservation_id' => $reservation->id]);

        return Transmission::factory()->create(['billable_period_id' => $period->id, 'reservation_id' => $reservation->id]);
    }
}

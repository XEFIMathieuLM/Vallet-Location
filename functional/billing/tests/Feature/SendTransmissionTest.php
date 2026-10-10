<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\CreateBillingExport;
use Functional\Billing\Actions\SendTransmission;
use Functional\Billing\Contracts\BillingGateway;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Enums\FakeGatewayMode;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Exceptions\NothingToExportException;
use Functional\Billing\Jobs\SendTransmissionJob;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Money\Money;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Billing\Tests\Doubles\ObservingBillingGateway;
use Functional\Billing\ValueObjects\BillableLine;
use Functional\Booking\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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
        $settlement = DamageSettlement::factory()->billed(Money::fromStored(45000), 'Remplacement capot')->create(['damage_id' => $damage->id]);
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

    public function test_the_billing_software_is_called_outside_any_transaction_and_an_export_skips_the_reserved_transmission(): void
    {
        Storage::fake('billing-exports');
        $employee = $this->employee();
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $transmission = $this->periodTransmission($reservation, '2026-11-10', '2026-11-14', BillablePeriodKind::Final);
        $baseTransactionLevel = DB::transactionLevel();
        $observedTransactionLevel = null;
        $this->app->instance(BillingGateway::class, new ObservingBillingGateway(function (BillableLine $line) use (&$observedTransactionLevel, $employee): string {
            $observedTransactionLevel = DB::transactionLevel();
            $this->assertThrows(fn () => app(CreateBillingExport::class)->handle($employee), NothingToExportException::class);

            return 'EXT-1';
        }));

        app(SendTransmission::class)->handle($transmission);

        $this->assertSame($baseTransactionLevel, $observedTransactionLevel);
        $transmission->refresh();
        $this->assertSame(TransmissionStatus::Sent, $transmission->status);
        $this->assertNull($transmission->reserved_until);
    }

    public function test_a_transmission_reserved_by_another_send_is_skipped_until_its_reservation_expires(): void
    {
        CarbonImmutable::setTestNow('2026-11-14 18:00:00');
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $transmission = $this->periodTransmission($reservation, '2026-11-10', '2026-11-14', BillablePeriodKind::Final);
        $transmission->update(['reserved_until' => CarbonImmutable::now()->addSeconds(30)]);

        app(SendTransmission::class)->handle($transmission);
        $this->assertSame([], $this->fakeGateway()->received());

        CarbonImmutable::setTestNow('2026-11-14 18:00:31');
        app(SendTransmission::class)->handle($transmission);
        $this->assertSame(TransmissionStatus::Sent, $transmission->refresh()->status);
    }

    public function test_an_unexpected_failure_of_the_call_records_no_outcome_and_keeps_the_reservation(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $transmission = $this->periodTransmission($reservation, '2026-11-10', '2026-11-14', BillablePeriodKind::Final);
        $this->app->instance(BillingGateway::class, new ObservingBillingGateway(fn (): string => throw new RuntimeException('Adapter bug')));

        $this->assertThrows(fn () => app(SendTransmission::class)->handle($transmission), RuntimeException::class);

        $transmission->refresh();
        $this->assertSame(TransmissionStatus::Pending, $transmission->status);
        $this->assertNull($transmission->external_ref);
        $this->assertNull($transmission->sent_at);
        $this->assertNotNull($transmission->reserved_until);
    }

    public function test_the_send_job_and_the_reservation_are_bounded_by_the_gateway_timeout(): void
    {
        config(['billing.gateway_timeout_seconds' => 7, 'billing.reservation_margin_seconds' => 5]);
        CarbonImmutable::setTestNow('2026-11-14 18:00:00');
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $transmission = $this->periodTransmission($reservation, '2026-11-10', '2026-11-14', BillablePeriodKind::Final);
        $reservedUntil = null;
        $this->app->instance(BillingGateway::class, new ObservingBillingGateway(function () use ($transmission, &$reservedUntil): string {
            $reservedUntil = $transmission->refresh()->reserved_until;

            return 'EXT-1';
        }));

        app(SendTransmission::class)->handle($transmission);

        $this->assertSame(12, (new SendTransmissionJob($transmission->id))->timeout);
        $this->assertEquals(CarbonImmutable::parse('2026-11-14 18:00:12'), $reservedUntil);
    }

    private function periodTransmission(Reservation $reservation, string $startDate, string $endDate, BillablePeriodKind $kind): Transmission
    {
        $period = BillablePeriod::factory()
            ->between(CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate), $kind)
            ->create(['reservation_id' => $reservation->id]);

        return Transmission::factory()->create(['billable_period_id' => $period->id, 'reservation_id' => $reservation->id]);
    }
}

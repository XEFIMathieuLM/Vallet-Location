<?php

namespace Functional\Accounts\Tests\Feature;

use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Billing\Actions\BillDamage;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Money\Money;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Billing\Tests\Concerns\RecordsReservationLifecycle;
use Functional\Booking\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTransmissionTest extends TestCase
{
    use BuildsBillingFixtures, RecordsReservationLifecycle, RefreshDatabase;

    public function test_the_final_period_carries_the_purchase_order_number_and_no_rental_amount(): void
    {
        $reservation = $this->keyAccountRentalWithPurchaseOrder('2026-11-10 08:00:00', 'BC-2026-0412');

        $this->recordReturn($reservation, '2026-11-14 17:00:00');

        $line = $this->receivedLineOf(Transmission::query()->where('reservation_id', $reservation->id)->sole());
        $this->assertSame('BC-2026-0412', $line['purchase_order_number']);
        $this->assertNull($line['amount_excl_tax_cents']);
        $this->assertSame('purchase_order_number', array_key_last($line));
    }

    public function test_the_month_end_period_carries_the_same_number(): void
    {
        $reservation = $this->keyAccountRentalWithPurchaseOrder('2026-11-20 08:00:00', 'BC-2026-0412');

        $this->closeMonthsOn('2026-12-01 00:15:00');

        $this->assertSame('BC-2026-0412', $this->receivedLineOf(Transmission::query()->where('reservation_id', $reservation->id)->sole())['purchase_order_number']);
    }

    public function test_a_billed_damage_carries_the_number_of_its_rental(): void
    {
        $reservation = $this->keyAccountRentalWithPurchaseOrder('2026-11-10 08:00:00', 'BC-2026-0412');
        $reservation = $this->recordReturn($reservation, '2026-11-14 17:00:00');

        app(BillDamage::class)->handle($this->unresolvedDamage($reservation), Money::fromStored(45000), 'Remplacement capot', $this->employee());

        $damageTransmission = Transmission::query()->where('reservation_id', $reservation->id)->whereNotNull('damage_settlement_id')->sole();
        $this->assertSame('BC-2026-0412', $this->receivedLineOf($damageTransmission)['purchase_order_number']);
    }

    public function test_a_rental_without_number_is_transmitted_without_one(): void
    {
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');

        $this->recordReturn($reservation, '2026-11-14 17:00:00');

        $this->assertNull($this->receivedLineOf(Transmission::query()->where('reservation_id', $reservation->id)->sole())['purchase_order_number']);
    }

    public function test_a_transmission_already_sent_is_not_sent_again_when_a_number_exists(): void
    {
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');
        $this->recordReturn($reservation, '2026-11-14 17:00:00');
        $transmission = Transmission::query()->where('reservation_id', $reservation->id)->sole();
        ReservationPurchaseOrder::factory()->for($reservation)->create(['number' => 'BC-LATE']);

        $this->artisan('billing:reconcile')->assertSuccessful();

        $this->assertSame(TransmissionStatus::Sent, $transmission->refresh()->status);
        $this->assertCount(1, $this->fakeGateway()->received());
        $this->assertNull($this->receivedLineOf($transmission)['purchase_order_number']);
    }

    private function keyAccountRentalWithPurchaseOrder(string $departedAt, string $number): Reservation
    {
        $reservation = $this->inProgressReservation($departedAt);
        KeyAccount::factory()->for($reservation->customer)->create();
        ReservationPurchaseOrder::factory()->for($reservation)->create(['number' => $number]);

        return $reservation;
    }

    /**
     * @return array<string, string|int|null>
     */
    private function receivedLineOf(Transmission $transmission): array
    {
        return $this->fakeGateway()->received()[$transmission->uuid];
    }
}

<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Models\Deposit;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcileDepositsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_deposit_missed_by_the_listeners_is_caught_up(): void
    {
        $reservation = Reservation::factory()->for(Customer::factory()->individual())->withStatus(ReservationStatus::Closed)->create();
        $deposit = Deposit::factory()->for($reservation)->create();

        $this->artisan('deposit:reconcile')->assertSuccessful();

        $this->assertSame(DepositStatus::ToRefund, $deposit->fresh()?->status);
    }

    public function test_a_deposit_of_a_reservation_still_out_is_left_collected(): void
    {
        $reservation = Reservation::factory()->for(Customer::factory()->individual())->withStatus(ReservationStatus::InProgress)->create();
        $deposit = Deposit::factory()->for($reservation)->create();

        $this->artisan('deposit:reconcile')->assertSuccessful();

        $this->assertSame(DepositStatus::Collected, $deposit->fresh()?->status);
    }

    public function test_the_reconciliation_runs_every_five_minutes_without_overlapping(): void
    {
        $reconciliation = collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'deposit:reconcile'));

        $this->assertNotNull($reconciliation);
        $this->assertSame('*/5 * * * *', $reconciliation->expression);
        $this->assertTrue($reconciliation->withoutOverlapping);
    }
}

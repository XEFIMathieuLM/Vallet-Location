<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Exceptions\MissingGoLiveDateException;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Billing\Tests\Concerns\RecordsReservationLifecycle;
use Functional\Booking\Enums\ReservationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthEndPeriodsTest extends TestCase
{
    use BuildsBillingFixtures, RecordsReservationLifecycle, RefreshDatabase;

    private const INTERMEDIATE = BillablePeriodKind::Intermediate->value;

    private const FINAL = BillablePeriodKind::Final->value;

    public function test_the_elapsed_month_of_a_running_rental_is_transmitted_after_month_end(): void
    {
        $reservation = $this->inProgressReservation('2026-11-20 08:00:00');

        $this->closeMonthsOn('2026-12-01 00:15:00');

        $this->assertSame([[self::INTERMEDIATE, '2026-11-20', '2026-11-30', 11]], $this->periodsOf($reservation));
        $this->assertSame(ReservationStatus::InProgress, $reservation->refresh()->status);
        $this->assertSame(TransmissionStatus::Sent, Transmission::query()->where('reservation_id', $reservation->id)->sole()->status);
    }

    public function test_the_return_only_transmits_the_days_after_the_last_period(): void
    {
        $reservation = $this->inProgressReservation('2026-11-20 08:00:00');
        $this->closeMonthsOn('2026-12-01 00:15:00');

        $this->recordReturn($reservation, '2026-12-05 17:00:00');

        $this->assertSame([
            [self::INTERMEDIATE, '2026-11-20', '2026-11-30', 11],
            [self::FINAL, '2026-12-01', '2026-12-05', 5],
        ], $this->periodsOf($reservation));
        $this->assertCount(2, $this->fakeGateway()->received());
    }

    public function test_a_return_on_the_last_day_of_the_month_produces_a_single_final_period(): void
    {
        $reservation = $this->inProgressReservation('2026-11-20 08:00:00');
        $this->recordReturn($reservation, '2026-11-30 17:00:00');

        $this->closeMonthsOn('2026-12-01 00:15:00');

        $this->assertSingleFinalPeriod($reservation, '2026-11-20', '2026-11-30', 11);
    }

    public function test_a_rental_over_three_months_has_two_intermediate_periods_and_a_final_one(): void
    {
        $reservation = $this->inProgressReservation('2026-10-15 08:00:00');
        $this->closeMonthsOn('2026-11-01 00:15:00');
        $this->closeMonthsOn('2026-12-01 00:15:00');

        $this->recordReturn($reservation, '2026-12-10 11:00:00');

        $this->assertSame([
            [self::INTERMEDIATE, '2026-10-15', '2026-10-31', 17],
            [self::INTERMEDIATE, '2026-11-01', '2026-11-30', 30],
            [self::FINAL, '2026-12-01', '2026-12-10', 10],
        ], $this->periodsOf($reservation));
        $this->assertSame(57, array_sum(array_column($this->periodsOf($reservation), 3)));
    }

    public function test_running_the_command_again_creates_nothing(): void
    {
        $reservation = $this->inProgressReservation('2026-11-20 08:00:00');
        $this->closeMonthsOn('2026-12-01 00:15:00');

        $this->closeMonthsOn('2026-12-01 00:20:00');
        $this->closeMonthsOn('2026-12-02 00:15:00');

        $this->assertCount(1, $this->periodsOf($reservation));
    }

    public function test_a_run_on_the_third_catches_up_the_previous_month(): void
    {
        $reservation = $this->inProgressReservation('2026-11-20 08:00:00');

        $this->closeMonthsOn('2026-12-03 00:15:00');

        $this->assertSame([[self::INTERMEDIATE, '2026-11-20', '2026-11-30', 11]], $this->periodsOf($reservation));
    }

    public function test_a_rental_running_at_go_live_is_transmitted_from_its_departure(): void
    {
        config(['billing.go_live_date' => '2026-10-01']);
        $reservation = $this->inProgressReservation('2026-08-15 08:00:00');

        $this->closeMonthsOn('2026-09-01 00:15:00');
        $this->assertSame([], $this->periodsOf($reservation));

        $this->closeMonthsOn('2026-10-01 00:15:00');
        $this->assertSame([
            [self::INTERMEDIATE, '2026-08-15', '2026-08-31', 17],
            [self::INTERMEDIATE, '2026-09-01', '2026-09-30', 30],
        ], $this->periodsOf($reservation));
    }

    public function test_without_go_live_date_the_command_fails_and_creates_nothing(): void
    {
        config(['billing.go_live_date' => null]);
        $reservation = $this->inProgressReservation('2026-11-20 08:00:00');
        $this->travelTo('2026-12-01 00:15:00');

        $this->assertThrows(fn () => $this->artisan('billing:close-months')->run(), MissingGoLiveDateException::class);
        $this->assertSame([], $this->periodsOf($reservation));
    }

    public function test_the_return_does_not_take_back_the_days_of_a_failed_intermediate_period(): void
    {
        $reservation = $this->inProgressReservation('2026-11-20 08:00:00', hasBillingAccount: false);
        $this->closeMonthsOn('2026-12-01 00:15:00');
        $this->assertSame(TransmissionStatus::Failed, Transmission::query()->where('reservation_id', $reservation->id)->sole()->status);

        $this->recordReturn($reservation, '2026-12-05 17:00:00');

        $this->assertSame([
            [self::INTERMEDIATE, '2026-11-20', '2026-11-30', 11],
            [self::FINAL, '2026-12-01', '2026-12-05', 5],
        ], $this->periodsOf($reservation));
    }

    public function test_close_months_reports_each_running_rental_and_a_summary(): void
    {
        $reservation = $this->inProgressReservation('2026-11-20 08:00:00');
        CarbonImmutable::setTestNow('2026-12-01 00:15:00');

        $this->artisan('billing:close-months')
            ->expectsOutputToContain("Closing elapsed months of reservation #{$reservation->id}.")
            ->expectsOutputToContain('Closed elapsed months of 1 running rental(s).')
            ->assertSuccessful();
    }
}

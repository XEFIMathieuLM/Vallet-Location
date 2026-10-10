<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Actions\RecordFinalPeriod;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Livewire\ReservationBillingSection;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Billing\Tests\Concerns\RecordsReservationLifecycle;
use Functional\Booking\Actions\CancelReservation;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Booking\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinalPeriodTest extends TestCase
{
    use BuildsBillingFixtures, RecordsReservationLifecycle, RefreshDatabase;

    public function test_recording_the_return_transmits_the_rental_on_its_real_dates(): void
    {
        $this->app->instance(ReservationTransitionGuards::class, new ReservationTransitionGuards);
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');
        CarbonImmutable::setTestNow('2026-11-14 17:30:00');

        app(ReturnReservation::class)->handle($reservation, ReturnCondition::cases()[0]);

        $this->assertSingleFinalPeriod($reservation, '2026-11-10', '2026-11-14', 5);
        $transmission = Transmission::query()->where('reservation_id', $reservation->id)->sole();
        $this->assertSame(TransmissionStatus::Sent, $transmission->status);
        $this->assertSame(5, $this->fakeGateway()->received()[$transmission->uuid]['days']);
    }

    public function test_an_early_return_is_transmitted_on_the_real_dates(): void
    {
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');

        $this->recordReturn($reservation, '2026-11-12 16:00:00');

        $this->assertSingleFinalPeriod($reservation, '2026-11-10', '2026-11-12', 3);
    }

    public function test_a_late_return_is_transmitted_on_the_real_dates(): void
    {
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');

        $this->recordReturn($reservation, '2026-11-17 09:00:00');

        $this->assertSingleFinalPeriod($reservation, '2026-11-10', '2026-11-17', 8);
    }

    public function test_a_cancelled_reservation_is_never_transmitted(): void
    {
        $reservation = Reservation::factory()->create();

        app(CancelReservation::class)->handle($reservation);

        $this->assertSame([], $this->periodsOf($reservation));
        $this->assertSame([], $this->fakeGateway()->received());
    }

    public function test_a_second_trigger_does_not_transmit_the_rental_twice(): void
    {
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');
        $this->recordReturn($reservation, '2026-11-14 17:00:00');

        app(RecordFinalPeriod::class)->handle($reservation);
        $this->recordReturn($reservation, '2026-11-14 17:00:00');

        $this->assertSingleFinalPeriod($reservation, '2026-11-10', '2026-11-14', 5);
        $this->assertCount(1, $this->fakeGateway()->received());
    }

    public function test_a_reservation_returned_the_day_before_go_live_is_not_transmitted(): void
    {
        config(['billing.go_live_date' => '2026-11-15']);
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');

        $this->recordReturn($reservation, '2026-11-14 23:30:00');

        $this->assertSame([], $this->periodsOf($reservation));
    }

    public function test_a_reservation_returned_on_go_live_day_is_transmitted_from_its_departure(): void
    {
        config(['billing.go_live_date' => '2026-11-15']);
        $reservation = $this->inProgressReservation('2026-11-02 08:00:00');

        $this->recordReturn($reservation, '2026-11-15 00:30:00');

        $this->assertSingleFinalPeriod($reservation, '2026-11-02', '2026-11-15', 14);
    }

    public function test_without_go_live_date_the_return_is_recorded_and_nothing_is_transmitted(): void
    {
        config(['billing.go_live_date' => null]);
        $this->app->instance(ReservationTransitionGuards::class, new ReservationTransitionGuards);
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');
        CarbonImmutable::setTestNow('2026-11-14 17:30:00');

        app(ReturnReservation::class)->handle($reservation, ReturnCondition::cases()[0]);

        $this->assertNotNull($reservation->refresh()->returned_at);
        $this->assertSame([], $this->periodsOf($reservation));
    }

    public function test_dates_are_taken_in_paris_time(): void
    {
        $reservation = $this->inProgressReservation('2026-11-09 23:30:00 UTC');

        $this->recordReturn($reservation, '2026-11-14 23:30:00 UTC');

        $this->assertSingleFinalPeriod($reservation, '2026-11-10', '2026-11-15', 6);
    }

    public function test_the_reservation_detail_shows_the_transmitted_periods_and_when(): void
    {
        $this->actingAs($this->employee());
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');
        $this->recordReturn($reservation, '2026-11-14 17:00:00');

        $this->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Facturation');
        Livewire::test(ReservationBillingSection::class, ['reservation' => $reservation])
            ->assertSee('Période finale')
            ->assertSee('Du 10/11/2026 au 14/11/2026')
            ->assertSee('5 jours')
            ->assertSee('Transmise')
            ->assertSee('transmise le 14/11/2026 17:00');
    }
}

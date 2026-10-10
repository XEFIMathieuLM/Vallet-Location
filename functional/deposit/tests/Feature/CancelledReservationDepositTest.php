<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Booking\Actions\CancelReservation;
use Functional\Booking\Models\Customer;
use Functional\Deposit\Actions\RefundDeposit;
use Functional\Deposit\Enums\DepositSituationKind;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Queries\DepositSituation;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelledReservationDepositTest extends TestCase
{
    use AssertsRefusals, BuildsDepositFixtures, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = $this->seededEmployee();
        $this->actingAs($this->author);
    }

    public function test_cancelling_a_reservation_makes_its_collected_deposit_refundable(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        $deposit = $this->collect($reservation, $this->author);

        app(CancelReservation::class)->handle($reservation);

        $deposit->refresh();
        $this->assertSame(DepositStatus::ToRefund, $deposit->status);
        $this->assertNotNull($deposit->awaiting_since);
    }

    public function test_a_cancelled_reservation_without_deposit_has_nothing_to_refund(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());

        app(CancelReservation::class)->handle($reservation);

        $this->assertSame(DepositSituationKind::NotTracked, app(DepositSituation::class)->for($reservation->refresh())->kind);
    }

    public function test_the_deposit_of_a_cancelled_reservation_is_refunded_without_damage_confirmation(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        $deposit = $this->collect($reservation, $this->author);
        app(CancelReservation::class)->handle($reservation);

        $refunded = app(RefundDeposit::class)->handle($deposit->refresh(), false, $this->author);

        $this->assertSame(DepositStatus::Refunded, $refunded->status);
        $this->assertFalse($refunded->is_no_damage_confirmed);
    }

    public function test_no_deposit_is_collected_on_a_cancelled_reservation(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        app(CancelReservation::class)->handle($reservation);

        $this->assertRefused(DepositRefusedException::class, 'réservation confirmée', fn () => $this->collect($reservation, $this->author));
    }
}

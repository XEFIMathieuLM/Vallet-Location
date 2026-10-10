<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Booking\Models\Customer;
use Functional\Deposit\Actions\RefundDeposit;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RefundDepositTest extends TestCase
{
    use AssertsRefusals, BuildsDepositFixtures, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->author = $this->seededEmployee();
        $this->actingAs($this->author);
    }

    public function test_the_deposit_is_refunded_with_the_no_damage_confirmation(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);

        $refunded = app(RefundDeposit::class)->handle($deposit, true, $this->author);

        $this->assertSame(DepositStatus::Refunded, $refunded->status);
        $this->assertSame($deposit->amount->minorUnits, $refunded->refunded?->minorUnits);
        $this->assertSame(0, $refunded->retained?->minorUnits);
        $this->assertTrue($refunded->is_no_damage_confirmed);
        $this->assertSame($this->author->getKey(), $refunded->closed_by);
        $this->assertSame($this->author->agencyId(), $refunded->closed_agency_id);
        $this->assertNotNull($refunded->closed_at);
        $activity = Activity::query()->where('log_name', 'deposit')->where('event', 'refunded')->sole();
        $this->assertTrue($activity->properties->get('is_no_damage_confirmed'));
    }

    public function test_the_no_damage_confirmation_is_required_after_a_return(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);

        $this->assertRefused(DepositRefusedException::class, 'aucun dégât', fn () => app(RefundDeposit::class)->handle($deposit, false, $this->author));
    }

    public function test_a_damage_to_settle_prevents_the_refund_even_when_the_status_is_late(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        $this->unresolvedDamageOn($deposit->reservation);

        $this->assertRefused(DepositRefusedException::class, 'Un dégât est à régler', fn () => app(RefundDeposit::class)->handle($deposit, true, $this->author));
        $this->assertSame(DepositStatus::BlockedByDamage, $deposit->fresh()?->status);
    }

    public function test_a_deposit_of_a_reservation_not_returned_cannot_be_refunded(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        $deposit = $this->collect($reservation, $this->author);

        $this->assertRefused(DepositRefusedException::class, 'ne peut pas être restituée', fn () => app(RefundDeposit::class)->handle($deposit, true, $this->author));
    }

    public function test_a_refunded_deposit_cannot_be_refunded_again(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        app(RefundDeposit::class)->handle($deposit, true, $this->author);

        $this->assertRefused(DepositRefusedException::class, 'déjà restituée', fn () => app(RefundDeposit::class)->handle($deposit->refresh(), true, $this->author));
        $this->assertSame(1, Deposit::query()->where('status', DepositStatus::Refunded)->count());
    }
}

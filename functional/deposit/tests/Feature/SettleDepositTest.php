<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Deposit\Actions\SettleDeposit;
use Functional\Deposit\Actions\SyncDepositStatus;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettleDepositTest extends TestCase
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

    public function test_the_billed_damages_are_retained_and_the_rest_refunded(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        $this->billedDamageOn($deposit->reservation, 45000);
        app(SyncDepositStatus::class)->for($deposit->reservation);

        $settled = app(SettleDeposit::class)->handle($deposit->refresh(), $this->author);

        $this->assertSame(DepositStatus::Settled, $settled->status);
        $this->assertSame(45000, $settled->retained?->minorUnits);
        $this->assertSame(105000, $settled->refunded?->minorUnits);
        $this->assertSame($this->author->agencyId(), $settled->closed_agency_id);
    }

    public function test_the_retention_is_capped_at_the_deposit(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        $this->billedDamageOn($deposit->reservation, 200000);
        app(SyncDepositStatus::class)->for($deposit->reservation);

        $settled = app(SettleDeposit::class)->handle($deposit->refresh(), $this->author);

        $this->assertSame(150000, $settled->retained?->minorUnits);
        $this->assertSame(0, $settled->refunded?->minorUnits);
    }

    public function test_a_deposit_not_to_settle_cannot_be_settled(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);

        $this->assertRefused(DepositRefusedException::class, 'ne peut pas être soldée', fn () => app(SettleDeposit::class)->handle($deposit, $this->author));
    }
}

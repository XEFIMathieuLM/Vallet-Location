<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Billing\Actions\BillDamage;
use Functional\Billing\Actions\WaiveDamage;
use Functional\Billing\Money\Money;
use Functional\Booking\Models\Customer;
use Functional\Deposit\Actions\SyncDepositStatus;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Inspection\Actions\ReportDamage;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SyncDepositStatusTest extends TestCase
{
    use BuildsDepositFixtures, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        config(['billing.go_live_date' => '2026-01-01', 'billing.gateway' => 'fake']);
        $this->author = $this->seededEmployee();
        $this->actingAs($this->author);
    }

    public function test_a_return_without_damage_makes_the_deposit_refundable(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);

        $this->assertSame(DepositStatus::ToRefund, $deposit->status);
        $this->assertNotNull($deposit->awaiting_since);
    }

    public function test_a_damage_reported_after_the_return_blocks_the_deposit(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);

        $this->reportDamage($deposit);

        $this->assertSame(DepositStatus::BlockedByDamage, $deposit->fresh()?->status);
    }

    public function test_a_billed_damage_makes_the_deposit_to_settle(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        $damage = $this->reportDamage($deposit);

        app(BillDamage::class)->handle($damage, Money::fromStored(45000), 'Capot', $this->author);

        $this->assertSame(DepositStatus::ToSettle, $deposit->fresh()?->status);
    }

    public function test_a_waived_damage_alone_makes_the_deposit_refundable_again(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        $damage = $this->reportDamage($deposit);

        app(WaiveDamage::class)->handle($damage, 'Usure normale', $this->author);

        $this->assertSame(DepositStatus::ToRefund, $deposit->fresh()?->status);
    }

    public function test_a_new_damage_blocks_a_deposit_to_settle(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        app(BillDamage::class)->handle($this->reportDamage($deposit), Money::fromStored(45000), 'Capot', $this->author);

        $this->reportDamage($deposit);

        $this->assertSame(DepositStatus::BlockedByDamage, $deposit->fresh()?->status);
    }

    public function test_synchronising_twice_changes_the_deposit_once(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        $activityCount = Activity::query()->where('log_name', 'deposit')->count();

        app(SyncDepositStatus::class)->for($deposit->reservation);
        app(SyncDepositStatus::class)->for($deposit->reservation);

        $this->assertSame($activityCount, Activity::query()->where('log_name', 'deposit')->count());
        $this->assertSame(DepositStatus::ToRefund, $deposit->fresh()?->status);
    }

    public function test_a_final_deposit_is_never_changed(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        $reservation->update(['status' => 'closed']);
        ReservationView::factory()->for($reservation)->create();
        $deposit = Deposit::factory()->for($reservation)->withStatus(DepositStatus::Refunded)->create();
        $this->unresolvedDamageOn($reservation);

        app(SyncDepositStatus::class)->for($reservation);

        $this->assertSame(DepositStatus::Refunded, $deposit->fresh()?->status);
    }

    private function reportDamage(Deposit $deposit): Damage
    {
        $viewId = ReservationView::query()->whereBelongsTo($deposit->reservation)->value('id');

        return app(ReportDamage::class)->handle($deposit->reservation, (int) $viewId, 'Bras rayé', $this->author);
    }
}

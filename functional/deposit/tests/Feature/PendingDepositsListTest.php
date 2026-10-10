<?php

namespace Functional\Deposit\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Access\BookingPermission;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Livewire\PendingDepositsList;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Queries\PendingDeposits;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PendingDepositsListTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
    }

    public function test_only_deposits_awaiting_an_action_are_listed_from_every_agency(): void
    {
        $toRefund = $this->depositIn(Agency::factory()->create(['name' => 'Rouen']), DepositStatus::ToRefund);
        $toSettle = $this->depositIn(Agency::factory()->create(['name' => 'Caen']), DepositStatus::ToSettle);
        $blocked = $this->depositIn(Agency::factory()->create(['name' => 'Évreux']), DepositStatus::BlockedByDamage);
        foreach ([DepositStatus::Collected, DepositStatus::Refunded, DepositStatus::Settled] as $status) {
            $this->depositIn(Agency::factory()->create(), $status);
        }

        Livewire::actingAs($this->employee())
            ->test(PendingDepositsList::class)
            ->assertViewHas('deposits', fn ($deposits): bool => $deposits->pluck('id')->sort()->values()->all() === collect([$toRefund->id, $toSettle->id, $blocked->id])->sort()->values()->all())
            ->assertSee('Rouen')
            ->assertSee($toRefund->reservation->customer->name)
            ->assertSee('1500,00 €')
            ->assertSee('10/11/2026');
    }

    public function test_the_list_is_filtered_by_machine_agency_and_status(): void
    {
        $rouen = Agency::factory()->create();
        $inRouen = $this->depositIn($rouen, DepositStatus::ToRefund);
        $this->depositIn($rouen, DepositStatus::ToSettle);
        $this->depositIn(Agency::factory()->create(), DepositStatus::ToRefund);

        Livewire::actingAs($this->employee())
            ->test(PendingDepositsList::class)
            ->set('agencyId', $rouen->id)
            ->set('status', DepositStatus::ToRefund->value)
            ->assertViewHas('deposits', fn ($deposits): bool => $deposits->pluck('id')->all() === [$inRouen->id]);
    }

    public function test_deposits_waiting_more_than_seven_days_to_refund_or_settle_are_overdue(): void
    {
        $toRefund = $this->depositIn(Agency::factory()->create(), DepositStatus::ToRefund);
        $blocked = $this->depositIn(Agency::factory()->create(), DepositStatus::BlockedByDamage);

        $this->travelTo(CarbonImmutable::parse('2026-11-18 09:00'));

        $this->assertTrue(app(PendingDeposits::class)->isOverdue($toRefund->refresh()));
        $this->assertFalse(app(PendingDeposits::class)->isOverdue($blocked->refresh()));
        Livewire::actingAs($this->employee())->test(PendingDepositsList::class)->assertSee('En retard');
    }

    public function test_the_screen_requires_the_deposit_permission(): void
    {
        $this->actingAs($this->userWithPermissions(BookingPermission::ManageReservations))
            ->get(route('deposit.pending.index'))
            ->assertForbidden();

        $this->actingAs($this->employee())->get(route('deposit.pending.index'))->assertOk();
    }

    public function test_the_list_does_not_query_per_row(): void
    {
        foreach (range(1, 5) as $index) {
            $this->depositIn(Agency::factory()->create(), DepositStatus::ToRefund);
        }
        $employee = $this->employee();

        DB::enableQueryLog();
        Livewire::actingAs($employee)->test(PendingDepositsList::class);
        $fiveRowsQueries = count(DB::getQueryLog());

        $this->depositIn(Agency::factory()->create(), DepositStatus::ToRefund);
        DB::flushQueryLog();
        Livewire::actingAs($employee)->test(PendingDepositsList::class);

        $this->assertSame($fiveRowsQueries, count(DB::getQueryLog()));
    }

    private function depositIn(Agency $agency, DepositStatus $status): Deposit
    {
        $reservation = Reservation::factory()->for(Machine::factory()->for($agency))->create();

        return Deposit::factory()->for($reservation)->withStatus($status)->create();
    }
}

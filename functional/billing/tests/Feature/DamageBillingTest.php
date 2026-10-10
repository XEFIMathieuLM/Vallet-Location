<?php

namespace Functional\Billing\Tests\Feature;

use App\Models\User;
use Functional\Billing\Actions\BillDamage;
use Functional\Billing\Actions\WaiveDamage;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Exceptions\DamageAlreadySettledException;
use Functional\Billing\Exceptions\InvalidDamageSettlementException;
use Functional\Billing\Livewire\ReservationBillingSection;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Billing\Tests\Concerns\RecordsReservationLifecycle;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Queries\ReservationsToReinvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DamageBillingTest extends TestCase
{
    use BuildsBillingFixtures, RecordsReservationLifecycle, RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = $this->employee();
        $this->actingAs($this->employee);
    }

    public function test_a_billed_damage_is_transmitted_with_the_original_rental_view_and_comment(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $damage = $this->unresolvedDamage($reservation, 'Gauche', 'Capot enfoncé');

        app(BillDamage::class)->handle($damage, 45000, 'remplacement capot', $this->employee);

        $settlement = DamageSettlement::query()->where('damage_id', $damage->id)->sole();
        $this->assertSame(DamageOutcome::Billed, $settlement->outcome);
        $this->assertTrue($damage->refresh()->isResolved());
        $transmission = Transmission::query()->where('damage_settlement_id', $settlement->id)->sole();
        $this->assertSame(TransmissionStatus::Sent, $transmission->status);
        $line = $this->fakeGateway()->received()[$transmission->uuid];
        $this->assertSame([(string) $reservation->id, 'Gauche', 'Capot enfoncé', 'remplacement capot', 45000], [
            $line['reservation_ref'], $line['damage_view'], $line['damage_comment'], $line['label'], $line['amount_excl_tax_cents'],
        ]);
        $this->assertSame($reservation->machine->reference, $line['machine_reference']);
    }

    public function test_a_damage_of_an_already_transmitted_rental_is_sent_alone(): void
    {
        $reservation = $this->inProgressReservation('2026-11-10 08:00:00');
        $this->recordReturn($reservation, '2026-11-14 17:00:00');
        $damage = $this->unresolvedDamage($reservation);

        app(BillDamage::class)->handle($damage, 45000, 'remplacement capot', $this->employee);

        $receivedTypes = array_column($this->fakeGateway()->received(), 'type');
        $this->assertSame(['rental_period', 'damage'], $receivedTypes);
        $this->assertSame(2, Transmission::query()->where('reservation_id', $reservation->id)->count());
    }

    public function test_a_waived_damage_leaves_the_list_without_transmission_and_keeps_reason_author_and_date(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $damage = $this->unresolvedDamage($reservation);

        app(WaiveDamage::class)->handle($damage, 'usure normale', $this->employee);

        $settlement = DamageSettlement::query()->where('damage_id', $damage->id)->sole();
        $this->assertSame([DamageOutcome::Waived, 'usure normale', $this->employee->id], [$settlement->outcome, $settlement->waiver_reason, $settlement->settled_by]);
        $this->assertNotNull($settlement->settled_at);
        $this->assertSame(0, Transmission::query()->count());
        $this->assertSame([], $this->fakeGateway()->received());
        $this->assertFalse(app(ReservationsToReinvoice::class)->get()->has($reservation->id));
    }

    public function test_a_waiver_without_reason_is_refused(): void
    {
        $damage = $this->unresolvedDamage($this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00'));

        $this->assertThrows(fn () => app(WaiveDamage::class)->handle($damage, '   ', $this->employee), InvalidDamageSettlementException::class);
        $this->assertSame(0, DamageSettlement::query()->count());
        $this->assertFalse($damage->refresh()->isResolved());
    }

    public function test_a_settled_damage_cannot_be_changed_in_the_tool(): void
    {
        $damage = $this->unresolvedDamage($this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00'));
        app(BillDamage::class)->handle($damage, 45000, 'remplacement capot', $this->employee);

        $this->assertThrows(
            fn () => app(BillDamage::class)->handle($damage, 30000, 'remplacement capot', $this->employee),
            DamageAlreadySettledException::class,
            'avoir',
        );
        $this->assertSame(45000, DamageSettlement::query()->sole()->amount_cents);
    }

    public function test_a_zero_or_negative_amount_or_an_empty_label_is_refused(): void
    {
        $damage = $this->unresolvedDamage($this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00'));

        $this->assertThrows(fn () => app(BillDamage::class)->handle($damage, 0, 'remplacement capot', $this->employee), InvalidDamageSettlementException::class);
        $this->assertThrows(fn () => app(BillDamage::class)->handle($damage, -100, 'remplacement capot', $this->employee), InvalidDamageSettlementException::class);
        $this->assertThrows(fn () => app(BillDamage::class)->handle($damage, 45000, ' ', $this->employee), InvalidDamageSettlementException::class);
        $this->assertSame(0, DamageSettlement::query()->count());
    }

    public function test_a_damage_already_resolved_by_hand_cannot_be_settled(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $damage = Damage::factory()->resolved()->create(['reservation_id' => $reservation->id, 'reservation_view_id' => $this->unresolvedDamage($reservation)->reservation_view_id]);

        $this->assertThrows(fn () => app(WaiveDamage::class)->handle($damage, 'usure normale', $this->employee), DamageAlreadySettledException::class);
    }

    public function test_a_damage_of_a_rental_returned_before_go_live_can_be_billed(): void
    {
        config(['billing.go_live_date' => '2026-12-01']);
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');

        app(BillDamage::class)->handle($this->unresolvedDamage($reservation), 45000, 'remplacement capot', $this->employee);

        $this->assertSame(['damage'], array_column($this->fakeGateway()->received(), 'type'));
    }

    public function test_the_reservation_section_shows_billed_and_waived_damages(): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        app(BillDamage::class)->handle($this->unresolvedDamage($reservation, 'Gauche'), 45050, 'remplacement capot', $this->employee);
        app(WaiveDamage::class)->handle($this->unresolvedDamage($reservation, 'Droite'), 'usure normale', $this->employee);

        Livewire::test(ReservationBillingSection::class, ['reservation' => $reservation])
            ->assertSee('Refacturé')
            ->assertSee('remplacement capot')
            ->assertSee('450,50 € HT')
            ->assertSee('Transmise')
            ->assertSee('Non refacturé')
            ->assertSee('usure normale')
            ->assertSee($this->employee->name);
    }
}

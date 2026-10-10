<?php

namespace Functional\Billing\Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Functional\Billing\Livewire\DamageBillingActions;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Database\Seeders\InspectionPermissionSeeder;
use Functional\Inspection\Livewire\Comparison;
use Functional\Inspection\Livewire\DamagesList;
use Functional\Inspection\Models\Damage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DamageBillingActionsScreensTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    private Reservation $reservation;

    private Damage $damage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('photos');
        $this->actingAs($this->employee());
        $this->reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $this->damage = $this->unresolvedDamage($this->reservation, 'Gauche', 'Capot enfoncé');
    }

    public function test_both_damage_screens_offer_billing_actions_instead_of_marking_resolved(): void
    {
        Livewire::test(DamagesList::class)
            ->assertSeeLivewire(DamageBillingActions::class)
            ->assertDontSee('Marquer traité');
        Livewire::test(Comparison::class, ['reservation' => $this->reservation])
            ->assertSeeLivewire(DamageBillingActions::class)
            ->assertDontSee('Marquer traité');

        Livewire::test(DamageBillingActions::class, ['damage' => $this->damage])
            ->assertSee('Refacturer')
            ->assertSee('Ne pas refacturer');
    }

    public function test_billing_from_the_comparison_settles_and_transmits_the_damage(): void
    {
        Livewire::test(DamageBillingActions::class, ['damage' => $this->damage])
            ->set('amount', '450,00')
            ->set('label', 'remplacement capot')
            ->call('bill')
            ->assertHasNoErrors();

        $settlement = DamageSettlement::query()->where('damage_id', $this->damage->id)->sole();
        $this->assertSame(45000, $settlement->amount?->minorUnits);
        $this->assertSame(1, Transmission::query()->where('damage_settlement_id', $settlement->id)->count());
        Livewire::test(Comparison::class, ['reservation' => $this->reservation])->assertDontSeeLivewire(DamageBillingActions::class);
    }

    public function test_the_form_reports_missing_amount_label_and_reason(): void
    {
        Livewire::test(DamageBillingActions::class, ['damage' => $this->damage])
            ->set('amount', '0')
            ->call('bill')
            ->assertHasErrors(['amount', 'label'])
            ->call('waive')
            ->assertHasErrors(['waiverReason']);

        $this->assertSame(0, DamageSettlement::query()->count());
    }

    public function test_an_employee_without_billing_permission_can_neither_see_nor_use_the_actions(): void
    {
        $this->seed([PermissionSeeder::class, InspectionPermissionSeeder::class]);
        $this->actingAs(User::factory()->create()->givePermissionTo(['damages.manage', 'reservations.manage']));

        Livewire::test(DamageBillingActions::class, ['damage' => $this->damage])
            ->assertDontSee('Refacturer')
            ->set('waiverReason', 'usure normale')
            ->call('waive')
            ->assertForbidden();

        $this->assertSame(0, DamageSettlement::query()->count());
    }
}

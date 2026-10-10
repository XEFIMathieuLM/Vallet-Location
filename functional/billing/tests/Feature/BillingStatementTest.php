<?php

namespace Functional\Billing\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Livewire\Statement;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Money\Money;
use Functional\Billing\Queries\BillingStatement;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Inspection\Models\Damage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class BillingStatementTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    private Agency $rouen;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-12-02 09:00:00');
        $this->employee = $this->employee();
        $this->actingAs($this->employee);
        $this->rouen = Agency::factory()->create(['name' => 'Rouen']);

        $reservations = collect(range(1, 10))->map(fn (int $day): Reservation => $this->transmittedRental($this->rouen, sprintf('2026-11-%02d', $day + 5)));
        DamageSettlement::factory()->billed(Money::fromStored(45000), 'Remplacement capot')->create(['damage_id' => $this->damageOf($reservations[0])->id, 'settled_at' => '2026-11-20 10:00:00']);
        DamageSettlement::factory()->waived('usure normale')->create(['damage_id' => $this->damageOf($reservations[1])->id, 'settled_at' => '2026-11-21 10:00:00', 'settled_by' => $this->employee->id]);
        $this->unresolvedDamage($reservations[2], 'Gauche', 'Bras rayé')->update(['reported_at' => '2026-11-22 10:00:00']);
        $this->transmittedRental(Agency::factory()->create(['name' => 'Caen']), '2026-11-10');
    }

    public function test_the_month_statement_of_an_agency_shows_rentals_billed_waived_and_pending_damages(): void
    {
        $billingStatement = app(BillingStatement::class)->for($this->rouen->id, CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30'));

        $this->assertSame(10, $billingStatement->transmittedRentalsCount);
        $this->assertSame(45000, $billingStatement->billedDamagesTotal->minorUnits);
        $this->assertSame(['usure normale'], $billingStatement->waivedDamages->pluck('waiver_reason')->all());
        $this->assertSame(['Bras rayé'], $billingStatement->unresolvedDamages->pluck('comment')->all());
    }

    public function test_without_agency_filter_every_agency_is_counted(): void
    {
        $billingStatement = app(BillingStatement::class)->for(null, CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30'));

        $this->assertSame(11, $billingStatement->transmittedRentalsCount);
    }

    public function test_the_screen_highlights_damages_waiting_for_more_than_seven_days(): void
    {
        Livewire::test(Statement::class)
            ->set('agencyId', $this->rouen->id)
            ->set('month', '2026-11')
            ->assertSee('10')
            ->assertSee('450,00 € HT')
            ->assertSee('usure normale')
            ->assertSee($this->employee->name)
            ->assertSee('Bras rayé')
            ->assertSee('En retard')
            ->assertSee('10 jours');
    }

    public function test_counts_and_totals_are_computed_by_the_database(): void
    {
        $queries = $this->statementQueries();
        $this->assertCount(1, array_filter($queries, fn (string $query): bool => str_contains(strtolower($query), 'count(distinct')));
        $this->assertCount(1, array_filter($queries, fn (string $query): bool => str_contains(strtolower($query), 'sum(')));

        $this->transmittedRental($this->rouen, '2026-11-20');
        $this->unresolvedDamage(Reservation::query()->latest('id')->firstOrFail());
        $this->assertCount(count($queries), $this->statementQueries());
    }

    /**
     * @return list<string>
     */
    private function statementQueries(): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(BillingStatement::class)->for($this->rouen->id, CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30'));
        DB::disableQueryLog();

        return array_column(DB::getQueryLog(), 'query');
    }

    public function test_empty_sections_of_the_statement_explain_how_to_widen_it(): void
    {
        Livewire::test(Statement::class)
            ->set('agencyId', Agency::factory()->create()->id)
            ->set('month', '2026-11')
            ->assertSee('Aucun dégât classé non refacturé')
            ->assertSee('Changez d’agence ou de mois')
            ->assertSee('Aucun dégât en attente');
    }

    private function transmittedRental(Agency $agency, string $returnDate): Reservation
    {
        $returnedAt = CarbonImmutable::parse("{$returnDate} 17:00:00");
        $reservation = Reservation::factory()
            ->for(Machine::factory()->create(['agency_id' => $agency->id]))
            ->between($returnedAt->subDays(3)->startOfDay(), $returnedAt->startOfDay())
            ->withStatus(ReservationStatus::Closed)
            ->create(['departed_at' => $returnedAt->subDays(3), 'returned_at' => $returnedAt]);
        $period = BillablePeriod::factory()
            ->between($returnedAt->subDays(3)->startOfDay(), $returnedAt->startOfDay(), BillablePeriodKind::Final)
            ->create(['reservation_id' => $reservation->id]);
        Transmission::factory()->sent()->create(['billable_period_id' => $period->id, 'reservation_id' => $reservation->id]);

        return $reservation;
    }

    private function damageOf(Reservation $reservation): Damage
    {
        $damage = $this->unresolvedDamage($reservation, 'Gauche', 'Constat de retour');
        $damage->update(['resolved_by' => $this->employee->id, 'resolved_at' => CarbonImmutable::now()]);

        return $damage;
    }
}

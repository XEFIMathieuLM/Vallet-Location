<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Extensions\CustomerChangeGuards;
use Functional\Booking\Models\Customer;
use Functional\Booking\Tests\Doubles\RefusingCustomerChangeGuard;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Livewire\ReservationDepositSection;
use Functional\Deposit\Livewire\Section\QualifyCustomerForm;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Queries\DepositSituation;
use Functional\Deposit\Enums\DepositSituationKind;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QualifyCustomerFromSectionTest extends TestCase
{
    use BuildsDepositFixtures, RefreshDatabase;

    public function test_the_section_asks_to_qualify_an_untyped_customer(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->untyped()->create());

        Livewire::actingAs($this->seededEmployee())
            ->test(ReservationDepositSection::class, ['reservation' => $reservation])
            ->assertSee('Type de client à renseigner')
            ->assertSeeLivewire(QualifyCustomerForm::class);
    }

    public function test_qualifying_as_individual_requires_a_deposit(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->untyped()->create());

        Livewire::actingAs($this->seededEmployee())
            ->test(QualifyCustomerForm::class, ['reservation' => $reservation])
            ->call('qualify', 'individual')
            ->assertHasNoErrors()
            ->assertDispatched('deposit-updated');

        $this->assertSame(CustomerType::Individual, $reservation->customer->fresh()?->type);
        $this->assertSame(DepositSituationKind::ToCollect, app(DepositSituation::class)->for($reservation->fresh() ?? $reservation)->kind);
    }

    public function test_qualifying_as_professional_makes_the_departure_ready(): void
    {
        $employee = $this->seededEmployee();
        $reservation = $this->confirmedReservationFor(Customer::factory()->untyped()->create());

        Livewire::actingAs($employee)
            ->test(QualifyCustomerForm::class, ['reservation' => $reservation])
            ->call('qualify', 'professional');

        Livewire::actingAs($employee)
            ->test(ReservationDepositSection::class, ['reservation' => $reservation->fresh()])
            ->assertSee('Non requise (client professionnel)')
            ->assertDispatched('reservation-transition-readiness', step: 'departure', section: 'deposit.reservation-section', is_ready: true);
    }

    public function test_a_collected_deposit_stays_tracked_when_the_customer_becomes_professional(): void
    {
        $employee = $this->seededEmployee();
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        $this->collect($reservation, $employee);

        Livewire::actingAs($employee)
            ->test(QualifyCustomerForm::class, ['reservation' => $reservation])
            ->call('qualify', 'professional');

        $this->assertSame(DepositStatus::Collected, Deposit::query()->whereBelongsTo($reservation)->sole()->status);
        $this->assertSame(DepositSituationKind::Tracked, app(DepositSituation::class)->for($reservation->fresh() ?? $reservation)->kind);
    }

    public function test_a_professional_requalified_as_individual_must_pay_a_deposit(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->professional()->create());

        Livewire::actingAs($this->seededEmployee())
            ->test(QualifyCustomerForm::class, ['reservation' => $reservation])
            ->call('qualify', 'individual');

        $this->assertSame(DepositSituationKind::ToCollect, app(DepositSituation::class)->for($reservation->fresh() ?? $reservation)->kind);
    }

    public function test_a_change_guard_refusal_is_displayed(): void
    {
        app(CustomerChangeGuards::class)->register(RefusingCustomerChangeGuard::class);
        $reservation = $this->confirmedReservationFor(Customer::factory()->professional()->create());

        Livewire::actingAs($this->seededEmployee())
            ->test(QualifyCustomerForm::class, ['reservation' => $reservation])
            ->call('qualify', 'individual')
            ->assertSee('Requalification refusée.');

        $this->assertSame(CustomerType::Professional, $reservation->customer->fresh()?->type);
    }
}

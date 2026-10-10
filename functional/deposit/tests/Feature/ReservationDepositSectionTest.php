<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Booking\Access\BookingPermission;
use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Models\Customer;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Livewire\ReservationDepositSection;
use Functional\Deposit\Livewire\Section\CollectDepositForm;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationDepositSectionTest extends TestCase
{
    use BuildsDepositFixtures, RefreshDatabase;

    public function test_the_section_guards_the_departure(): void
    {
        $this->assertTrue(app(ReservationDetailSections::class)->isGuardedBy(ReservationDepositSection::NAME, ReservationTransition::Departure));
    }

    public function test_an_individual_reservation_shows_the_amount_to_collect_and_blocks_the_departure(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());

        Livewire::actingAs($this->seededEmployee())
            ->test(ReservationDepositSection::class, ['reservation' => $reservation])
            ->assertSee('À encaisser')
            ->assertSee('1500,00 €')
            ->assertDispatched('reservation-transition-readiness', step: 'departure', section: 'deposit.reservation-section', is_ready: false);
    }

    public function test_a_professional_reservation_needs_no_deposit_and_is_ready(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->professional()->create());

        Livewire::actingAs($this->seededEmployee())
            ->test(ReservationDepositSection::class, ['reservation' => $reservation])
            ->assertSee('Non requise (client professionnel)')
            ->assertDispatched('reservation-transition-readiness', step: 'departure', section: 'deposit.reservation-section', is_ready: true);
    }

    public function test_collecting_from_the_form_marks_the_deposit_collected_and_the_section_ready(): void
    {
        $employee = $this->seededEmployee();
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());

        Livewire::actingAs($employee)
            ->test(CollectDepositForm::class, ['reservation' => $reservation])
            ->set('method', 'cheque')
            ->set('reference', '7654321')
            ->call('collect')
            ->assertHasNoErrors()
            ->assertDispatched('deposit-updated');

        $this->assertSame(DepositStatus::Collected, Deposit::query()->whereBelongsTo($reservation)->sole()->status);

        Livewire::actingAs($employee)
            ->test(ReservationDepositSection::class, ['reservation' => $reservation])
            ->assertSee('Encaissée')
            ->assertDispatched('reservation-transition-readiness', step: 'departure', section: 'deposit.reservation-section', is_ready: true);
    }

    public function test_a_refusal_is_displayed_by_the_form(): void
    {
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());
        Deposit::factory()->for($reservation)->create();

        Livewire::actingAs($this->seededEmployee())
            ->test(CollectDepositForm::class, ['reservation' => $reservation])
            ->set('method', 'cash')
            ->call('collect')
            ->assertSee('déjà encaissée');
    }

    public function test_without_the_deposit_permission_the_section_shows_the_situation_without_actions(): void
    {
        $this->seedPermissions();
        $reservation = $this->confirmedReservationFor(Customer::factory()->individual()->create());

        Livewire::actingAs($this->userWithPermissions(BookingPermission::ManageReservations))
            ->test(ReservationDepositSection::class, ['reservation' => $reservation])
            ->assertSee('À encaisser')
            ->assertDontSeeLivewire(CollectDepositForm::class)
            ->assertDispatched('reservation-transition-readiness', step: 'departure', section: 'deposit.reservation-section', is_ready: false);
    }
}

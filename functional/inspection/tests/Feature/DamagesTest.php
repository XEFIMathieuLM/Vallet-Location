<?php

namespace Functional\Inspection\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Actions\ReportDamage;
use Functional\Inspection\Actions\ResolveDamage;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\DamageAlreadyResolvedException;
use Functional\Inspection\Exceptions\DamageNotReportableException;
use Functional\Inspection\Livewire\Comparison;
use Functional\Inspection\Livewire\DamagesList;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Support\DamageActions;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Functional\Inspection\Tests\Fixtures\FakeDamageAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DamagesTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->employee = $this->employee();
        $this->actingAs($this->employee);
    }

    public function test_the_comparison_shows_departure_and_return_photos_with_dates_and_authors(): void
    {
        CarbonImmutable::setTestNow('2026-10-10 08:15:00');
        $reservation = $this->returnedReservation();

        $this->get(route('inspection.comparison', $reservation))
            ->assertOk()
            ->assertSee($reservation->machine->reference)
            ->assertSeeInOrder(['Avant', 'Départ', 'Prise le 10/10/2026 08:15', 'Retour', 'Prise le 10/10/2026 08:15'])
            ->assertSee(Photo::query()->firstOrFail()->session->author->name);
    }

    public function test_a_reported_damage_puts_the_reservation_in_the_list_to_reinvoice(): void
    {
        $reservation = $this->returnedReservation();
        $left = $this->reservationView($reservation, 'Gauche');

        Livewire::test(Comparison::class, ['reservation' => $reservation])
            ->set('reservationViewId', $left->id)
            ->set('comment', 'Rayure profonde sur le capot')
            ->call('report')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('damages', ['reservation_id' => $reservation->id, 'reservation_view_id' => $left->id, 'reported_by' => $this->employee->id, 'resolved_at' => null]);

        $this->actingAs($this->employee());
        $this->get(route('inspection.damages'))
            ->assertOk()
            ->assertSee($reservation->machine->reference)
            ->assertSee($reservation->customer->name)
            ->assertSee($reservation->agency->name)
            ->assertSee('Gauche : Rayure profonde sur le capot')
            ->assertSee("Signalé par {$this->employee->name}");
    }

    public function test_a_resolved_damage_leaves_the_list_and_keeps_its_history(): void
    {
        $reservation = $this->returnedReservation();
        $damage = Damage::factory()->for($reservation)->create(['reservation_view_id' => $this->reservationView($reservation, 'Gauche')->id]);

        Livewire::test(DamagesList::class)
            ->assertSee('Marquer traité')
            ->call('resolveDamage', $damage->id)
            ->assertHasNoErrors()
            ->assertSee('Aucun dégât à traiter.');

        $damage->refresh();
        $this->assertSame($this->employee->id, $damage->resolved_by);
        $this->assertNotNull($damage->resolved_at);
        Livewire::test(Comparison::class, ['reservation' => $reservation])->assertSee("Traité par {$this->employee->name}");
    }

    public function test_a_damage_cannot_be_reported_before_the_return_photos_are_complete(): void
    {
        $reservation = $this->reservationStartingToday(ReservationStatus::InProgress);
        $this->photographEveryView($reservation, InspectionStep::Departure);
        $front = $this->reservationView($reservation, 'Avant');
        Photo::factory()->forView($front, InspectionStep::Return)->create();

        Livewire::test(Comparison::class, ['reservation' => $reservation])
            ->set('reservationViewId', $front->id)
            ->set('comment', 'Choc')
            ->call('report')
            ->assertHasErrors('refusal');

        $this->assertSame(0, Damage::query()->count());
    }

    public function test_a_damage_needs_a_comment(): void
    {
        $reservation = $this->returnedReservation();

        Livewire::test(Comparison::class, ['reservation' => $reservation])
            ->set('reservationViewId', $this->reservationView($reservation, 'Avant')->id)
            ->set('comment', '   ')
            ->call('report')
            ->assertHasErrors('comment');

        $this->assertThrows(
            fn () => app(ReportDamage::class)->handle($reservation, $this->reservationView($reservation, 'Avant')->id, '  ', $this->employee),
            DamageNotReportableException::class,
        );
    }

    public function test_a_damage_cannot_target_a_view_of_another_reservation(): void
    {
        $reservation = $this->returnedReservation();
        $foreignView = ReservationView::factory()->create();

        Livewire::test(Comparison::class, ['reservation' => $reservation])
            ->set('reservationViewId', $foreignView->id)
            ->set('comment', 'Choc')
            ->call('report')
            ->assertNotFound();
    }

    public function test_damages_require_the_damages_permission(): void
    {
        $reservation = $this->returnedReservation();
        $this->actingAs(User::factory()->create()->givePermissionTo('reservations.manage'));

        $this->get(route('inspection.damages'))->assertForbidden();
        Livewire::test(Comparison::class, ['reservation' => $reservation])
            ->set('reservationViewId', $this->reservationView($reservation, 'Avant')->id)
            ->set('comment', 'Choc')
            ->call('report')
            ->assertForbidden();
    }

    public function test_resolve_damage_can_be_called_from_another_layer_and_refuses_a_resolved_damage(): void
    {
        $damage = Damage::factory()->for($this->returnedReservation())->create();

        app(ResolveDamage::class)->handle($damage, $this->employee);

        $this->assertTrue($damage->refresh()->isResolved());
        $this->assertThrows(fn () => app(ResolveDamage::class)->handle($damage, $this->employee), DamageAlreadyResolvedException::class);
    }

    public function test_registered_damage_actions_replace_the_resolve_button_on_both_screens(): void
    {
        $reservation = $this->returnedReservation();
        $damage = Damage::factory()->for($reservation)->create(['reservation_view_id' => $this->reservationView($reservation, 'Gauche')->id]);

        Livewire::test(DamagesList::class)->assertSee('Marquer traité');
        Livewire::test(Comparison::class, ['reservation' => $reservation])->assertSee('Marquer traité');

        Livewire::component('fake-damage-action', FakeDamageAction::class);
        app(DamageActions::class)->register('fake-damage-action', 10);

        Livewire::test(DamagesList::class)
            ->assertSee("Transmettre à la facturation #{$damage->id}")
            ->assertDontSee('Marquer traité');
        Livewire::test(Comparison::class, ['reservation' => $reservation])
            ->assertSee("Transmettre à la facturation #{$damage->id}")
            ->assertDontSee('Marquer traité');
    }

    private function returnedReservation(): Reservation
    {
        $reservation = $this->reservationStartingToday();
        $this->photographEveryView($reservation, InspectionStep::Departure);
        $this->photographEveryView($reservation, InspectionStep::Return);
        $reservation->update(['status' => ReservationStatus::Closed]);

        return $reservation;
    }

    private function reservationView(Reservation $reservation, string $label): ReservationView
    {
        return ReservationView::query()->where('reservation_id', $reservation->id)->where('label', $label)->firstOrFail();
    }
}

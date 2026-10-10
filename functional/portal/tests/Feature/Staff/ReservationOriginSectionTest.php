<?php

namespace Functional\Portal\Tests\Feature\Staff;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Extensions\ReservationDetailSections;
use Functional\Booking\Extensions\ReservationTransitionGuards;
use Functional\Booking\Livewire\ReservationDetail;
use Functional\Booking\Models\Reservation;
use Functional\Portal\Livewire\Staff\ReservationOriginSection;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationOriginSectionTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        $this->seedPermissions();
        $this->actingAs($this->employee());
    }

    public function test_a_reservation_born_from_an_online_request_shows_its_origin_and_the_customer_comment(): void
    {
        $reservation = Reservation::factory()->create();
        $account = $this->customerAccount(['name' => 'Terrassements Martin', 'email' => 'contact@martin.fr']);
        ReservationRequest::factory()->confirmed($reservation)->for($account, 'account')->create([
            'machine_id' => $reservation->machine_id,
            'comment' => 'Chantier au 12 rue des Lilas',
            'created_at' => CarbonImmutable::parse('2026-10-08 14:00'),
        ]);

        Livewire::test(ReservationOriginSection::class, ['reservation' => $reservation])
            ->assertSee(__('portal::staff.origin.heading'))
            ->assertSee(['08/10/2026', 'Terrassements Martin', 'contact@martin.fr', 'Chantier au 12 rue des Lilas']);

        $this->get(route('reservations.show', $reservation))->assertOk()->assertSee('Chantier au 12 rue des Lilas');
    }

    public function test_a_counter_reservation_shows_nothing(): void
    {
        Livewire::test(ReservationOriginSection::class, ['reservation' => Reservation::factory()->create()])
            ->assertDontSee(__('portal::staff.origin.heading'));
    }

    public function test_the_section_never_guards_a_transition_nor_blocks_the_departure(): void
    {
        $sections = app(ReservationDetailSections::class);
        foreach (ReservationTransition::cases() as $transition) {
            $this->assertFalse($sections->isGuardedBy(ReservationOriginSection::NAME, $transition));
        }
        $this->app->instance(ReservationTransitionGuards::class, new ReservationTransitionGuards);
        $onlyOriginSection = new ReservationDetailSections;
        $onlyOriginSection->register(ReservationOriginSection::NAME, 5);
        $this->app->instance(ReservationDetailSections::class, $onlyOriginSection);
        $reservation = Reservation::factory()->between(CarbonImmutable::today(), CarbonImmutable::today()->addDays(2))->create();
        ReservationRequest::factory()->confirmed($reservation)->create(['machine_id' => $reservation->machine_id]);

        Livewire::test(ReservationDetail::class, ['reservation' => $reservation])
            ->assertSet('readinessBySections', [])
            ->call('depart')
            ->assertHasNoErrors();

        $this->assertSame('in_progress', $reservation->fresh()?->status->value);
    }
}

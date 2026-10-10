<?php

namespace Functional\Booking\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Actions\CancelReservation;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Tests\Concerns\WithoutTransitionExtensions;
use Functional\Fleet\Actions\ChangeMachineStatus;
use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Models\Machine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase, WithoutTransitionExtensions;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
        $this->author = User::factory()->employee()->create();
        $this->actingAs($this->author);
    }

    public function test_creation_departure_and_return_are_logged_with_author_and_date(): void
    {
        $machine = Machine::factory()->create();
        $reservation = $this->reserve($machine);

        app(DepartReservation::class)->handle($reservation);
        app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState);

        $this->assertSame(
            [['created', 'confirmed'], ['updated', 'in_progress'], ['updated', 'closed']],
            $this->activitiesOf($reservation)->map(fn (Activity $activity): array => [$activity->event, $activity->attribute_changes?->get('attributes')['status'] ?? null])->all(),
        );
        $this->assertSame(['available', 'rented_out', 'available'], $this->activitiesOf($machine)->map(fn (Activity $activity) => $activity->attribute_changes?->get('attributes')['status'] ?? null)->all());
    }

    public function test_cancellation_and_status_change_are_logged_with_author_and_date(): void
    {
        $machine = Machine::factory()->create();
        $reservation = $this->reserve($machine);

        app(CancelReservation::class)->handle($reservation);
        app(ChangeMachineStatus::class)->handle($machine, MachineTransition::SendToWorkshop);

        $this->assertSame('cancelled', $this->activitiesOf($reservation)->last()?->attribute_changes?->get('attributes')['status']);
        $this->assertSame('workshop', $this->activitiesOf($machine)->last()?->attribute_changes?->get('attributes')['status']);
    }

    private function reserve(Machine $machine): Reservation
    {
        return app(CreateReservation::class)->handle(
            $this->author,
            $machine,
            Customer::factory()->create(),
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-14'),
        );
    }

    /**
     * @return Collection<int, Activity>
     */
    private function activitiesOf(Model $subject): Collection
    {
        $activities = Activity::query()->whereMorphedTo('subject', $subject)->orderBy('id')->get();

        foreach ($activities as $activity) {
            $this->assertTrue($this->author->is($activity->causer), 'every entry names its author');
            $this->assertNotNull($activity->created_at, 'every entry is dated');
        }

        return $activities->toBase();
    }
}

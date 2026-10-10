<?php

namespace Functional\Fleet\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Fleet\Actions\ChangeMachineStatus;
use Functional\Fleet\Actions\UpdateMachineVgp;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Events\MachineChanged;
use Functional\Fleet\Exceptions\IllegalMachineTransitionException;
use Functional\Fleet\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChangeMachineStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_legal_transition_changes_the_status_and_announces_it(): void
    {
        Event::fake([MachineChanged::class]);
        $machine = Machine::factory()->create();

        app(ChangeMachineStatus::class)->handle($machine, MachineTransition::MarkOutOfOrder);

        $this->assertSame(MachineStatus::OutOfOrder, $machine->fresh()?->status);
        Event::assertDispatched(MachineChanged::class, fn (MachineChanged $event): bool => $event->machine->is($machine));
    }

    public function test_an_illegal_transition_is_refused_and_nothing_changes(): void
    {
        Event::fake([MachineChanged::class]);
        $machine = Machine::factory()->withStatus(MachineStatus::Retired)->create();

        $this->expectException(IllegalMachineTransitionException::class);

        rescue(
            fn () => app(ChangeMachineStatus::class)->handle($machine, MachineTransition::MakeAvailable),
            function ($exception) use ($machine): never {
                $this->assertSame(MachineStatus::Retired, $machine->fresh()?->status);
                Event::assertNotDispatched(MachineChanged::class);

                throw $exception;
            },
            report: false,
        );
    }

    public function test_updating_the_vgp_due_date_announces_the_change(): void
    {
        Event::fake([MachineChanged::class]);
        $machine = Machine::factory()->subjectToVgpUntil(null)->create();

        app(UpdateMachineVgp::class)->handle($machine, CarbonImmutable::parse('2027-03-31'));

        $this->assertSame('2027-03-31', $machine->fresh()?->vgp_due_date?->toDateString());
        Event::assertDispatched(MachineChanged::class);
    }
}

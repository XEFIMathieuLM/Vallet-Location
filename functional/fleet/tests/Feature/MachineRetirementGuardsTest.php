<?php

namespace Functional\Fleet\Tests\Feature;

use Functional\Fleet\Actions\RetireMachine;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Exceptions\MachineRetirementRefusedException;
use Functional\Fleet\Extensions\MachineRetirementGuards;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Doubles\RecordingRetirementGuard;
use Functional\Fleet\Tests\Doubles\RefusingRetirementGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineRetirementGuardsTest extends TestCase
{
    use AssertsRefusals, RefreshDatabase;

    public function test_every_registered_guard_is_consulted_before_the_retirement(): void
    {
        $recordingGuard = new RecordingRetirementGuard;
        $this->app->instance(RecordingRetirementGuard::class, $recordingGuard);
        app(MachineRetirementGuards::class)->register(RecordingRetirementGuard::class);
        $machine = Machine::factory()->create();

        app(RetireMachine::class)->handle($machine);

        $this->assertSame([$machine->id], $recordingGuard->checkedMachineIds);
        $this->assertSame(MachineStatus::Retired, $machine->fresh()?->status);
    }

    public function test_one_refusing_guard_among_several_cancels_the_retirement(): void
    {
        $this->app->instance(RecordingRetirementGuard::class, new RecordingRetirementGuard);
        app(MachineRetirementGuards::class)->register(RecordingRetirementGuard::class);
        app(MachineRetirementGuards::class)->register(RefusingRetirementGuard::class);
        $machine = Machine::factory()->create();

        $this->assertRefused(MachineRetirementRefusedException::class, 'réservation', fn () => app(RetireMachine::class)->handle($machine));

        $this->assertSame(MachineStatus::Available, $machine->fresh()?->status);
    }

    public function test_a_machine_without_any_restriction_is_retired(): void
    {
        $machine = Machine::factory()->create();

        app(RetireMachine::class)->handle($machine);

        $this->assertSame(MachineStatus::Retired, $machine->fresh()?->status);
    }
}

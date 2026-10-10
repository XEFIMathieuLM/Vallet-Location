<?php

namespace Functional\Fleet\Tests\Feature;

use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Fleet\Actions\CreateMachine;
use Functional\Fleet\Actions\RetireMachine;
use Functional\Fleet\Actions\UpdateMachine;
use Functional\Fleet\Contracts\MachineRetirementGuard;
use Functional\Fleet\Data\MachineAttributes;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Enums\MachineTransition;
use Functional\Fleet\Exceptions\DuplicateMachineReferenceException;
use Functional\Fleet\Livewire\MachineIndex;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Functional\Fleet\Tests\Doubles\RefusingRetirementGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageMachinesTest extends TestCase
{
    use AssertsRefusals, CreatesUsers, RefreshDatabase;

    public function test_a_machine_is_created_with_a_normalized_reference(): void
    {
        $machine = app(CreateMachine::class)->handle($this->attributes(' nac-0042 '));

        $this->assertSame('NAC-0042', $machine->reference);
        $this->assertSame(MachineStatus::Available, $machine->status);
    }

    public function test_creating_a_machine_with_an_existing_reference_is_refused_whatever_the_case_or_spaces(): void
    {
        Machine::factory()->create(['reference' => 'NAC-0042']);

        $this->assertRefused(DuplicateMachineReferenceException::class, 'NAC-0042', fn () => app(CreateMachine::class)->handle($this->attributes('  nac-0042')));
    }

    public function test_updating_a_machine_to_another_machine_reference_is_refused(): void
    {
        Machine::factory()->create(['reference' => 'NAC-0042']);
        $machine = Machine::factory()->create(['reference' => 'NAC-0043']);

        $this->expectException(DuplicateMachineReferenceException::class);

        app(UpdateMachine::class)->handle($machine, $this->attributes('Nac-0042'));
    }

    public function test_a_machine_keeps_its_own_reference_when_updated(): void
    {
        $machine = Machine::factory()->create(['reference' => 'NAC-0042']);

        app(UpdateMachine::class)->handle($machine, $this->attributes('NAC-0042'));

        $this->assertSame('NAC-0042', $machine->fresh()?->reference);
    }

    public function test_a_machine_of_a_vgp_category_is_always_subject_to_vgp(): void
    {
        $category = MachineCategory::factory()->requiringVgp()->create();

        $machine = app(CreateMachine::class)->handle($this->attributes('NAC-0042', $category, isSubjectToVgp: false));
        $this->assertTrue($machine->is_subject_to_vgp);

        $otherMachine = Machine::factory()->create();
        app(UpdateMachine::class)->handle($otherMachine, $this->attributes($otherMachine->reference, $category, isSubjectToVgp: false));
        $this->assertTrue($otherMachine->fresh()?->is_subject_to_vgp);
    }

    public function test_retiring_a_machine_is_refused_when_the_retirement_guard_refuses(): void
    {
        $this->app->instance(MachineRetirementGuard::class, new RefusingRetirementGuard);
        $machine = Machine::factory()->create();

        $refusal = rescue(fn () => app(RetireMachine::class)->handle($machine), fn ($exception) => $exception, report: false);

        $this->assertNotNull($refusal);
        $this->assertSame(MachineStatus::Available, $machine->fresh()?->status);
    }

    public function test_a_machine_without_active_reservation_can_be_retired(): void
    {
        $machine = Machine::factory()->withStatus(MachineStatus::OutOfOrder)->create();

        app(RetireMachine::class)->handle($machine);

        $this->assertSame(MachineStatus::Retired, $machine->fresh()?->status);
    }

    public function test_a_status_change_from_the_fleet_screen_is_visible_to_every_agency(): void
    {
        $this->seed(PermissionSeeder::class);
        $machine = Machine::factory()->create(['reference' => 'NAC-0042']);

        Livewire::actingAs($this->employee())
            ->test(MachineIndex::class)
            ->call('applyTransition', $machine->id, MachineTransition::MarkOutOfOrder->value)
            ->assertHasNoErrors();

        $this->actingAs($this->employee())
            ->get(route('machines.index'))
            ->assertOk()
            ->assertSee('NAC-0042')
            ->assertSee(MachineStatus::OutOfOrder->label());
    }

    public function test_a_machine_is_retired_from_the_fleet_screen_after_confirmation(): void
    {
        $this->seed(PermissionSeeder::class);
        $machine = Machine::factory()->withStatus(MachineStatus::OutOfOrder)->create();

        Livewire::actingAs($this->employee())
            ->test(MachineIndex::class)
            ->call('confirmRetirement', $machine->id)
            ->assertSet('machineToRetireId', $machine->id)
            ->call('retire')
            ->assertHasNoErrors();

        $this->assertSame(MachineStatus::Retired, $machine->fresh()?->status);
    }

    public function test_a_refused_retirement_is_displayed_on_the_fleet_screen(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->app->instance(MachineRetirementGuard::class, new RefusingRetirementGuard);
        $machine = Machine::factory()->create();

        Livewire::actingAs($this->employee())
            ->test(MachineIndex::class)
            ->call('confirmRetirement', $machine->id)
            ->call('retire')
            ->assertHasErrors('refusal');

        $this->assertSame(MachineStatus::Available, $machine->fresh()?->status);
    }

    public function test_the_fleet_screen_only_offers_manual_legal_transitions(): void
    {
        $this->assertSame(
            [MachineTransition::SendToWorkshop, MachineTransition::MarkOutOfOrder, MachineTransition::Retire],
            MachineTransition::manualFrom(MachineStatus::Available),
        );
        $this->assertSame([], MachineTransition::manualFrom(MachineStatus::RentedOut));
        $this->assertSame([], MachineTransition::manualFrom(MachineStatus::Retired));
    }

    public function test_the_fleet_screen_requires_the_machine_permission(): void
    {
        $this->actingAs($this->userWithoutPermission())
            ->get(route('machines.index'))
            ->assertForbidden();
    }

    private function attributes(string $reference, ?MachineCategory $category = null, bool $isSubjectToVgp = false, ?CarbonImmutable $vgpDueDate = null): MachineAttributes
    {
        return new MachineAttributes(
            reference: $reference,
            categoryId: ($category ?? MachineCategory::factory()->create())->id,
            agencyId: Agency::factory()->create()->id,
            isSubjectToVgp: $isSubjectToVgp,
            vgpDueDate: $vgpDueDate,
        );
    }
}

<?php

namespace Functional\Fleet\Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Functional\Fleet\Livewire\MachineForm;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class MachineFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_a_machine_is_created_from_the_form(): void
    {
        $this->fillForm('NAC-0042')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('machines.index'));

        $this->assertTrue(Machine::query()->where('reference', 'NAC-0042')->exists());
    }

    public function test_a_duplicate_reference_is_refused_on_screen(): void
    {
        Machine::factory()->create(['reference' => 'NAC-0042']);

        $this->fillForm(' nac-0042 ')
            ->call('save')
            ->assertHasErrors('refusal')
            ->assertSee('NAC-0042 existe déjà');

        $this->assertSame(1, Machine::query()->count());
    }

    public function test_the_edit_form_is_prefilled(): void
    {
        $machine = Machine::factory()->create(['reference' => 'NAC-0042']);

        $this->actingAs(User::factory()->employee()->create())
            ->get(route('machines.edit', $machine))
            ->assertOk()
            ->assertSee('NAC-0042');
    }

    private function fillForm(string $reference): Testable
    {
        return Livewire::actingAs(User::factory()->employee()->create())
            ->test(MachineForm::class)
            ->set('reference', $reference)
            ->set('categoryId', MachineCategory::factory()->create()->id)
            ->set('agencyId', Agency::factory()->create()->id);
    }
}

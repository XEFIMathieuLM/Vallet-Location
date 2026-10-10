<?php

namespace Functional\Inspection\Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Actions\MissingViews;
use Functional\Inspection\Database\Seeders\InspectionPermissionSeeder;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Livewire\CategoryViews;
use Functional\Inspection\Models\CategoryView;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryViewsTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    private User $employee;

    private MachineCategory $miniExcavator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, InspectionPermissionSeeder::class]);
        $this->employee = User::factory()->create()->assignRole(PermissionSeeder::EMPLOYEE_ROLE);
        $this->miniExcavator = MachineCategory::factory()->create(['name' => 'Mini-pelle']);
    }

    public function test_a_category_without_settings_uses_the_default_views(): void
    {
        $reservation = $this->miniExcavatorReservation();

        $this->openSession($reservation);

        $this->assertSame(
            ['Avant', 'Arrière', 'Gauche', 'Droite', 'Compteur d\'heures'],
            app(MissingViews::class)->for($reservation, InspectionStep::Departure)->pluck('label')->all(),
        );
    }

    public function test_a_view_added_to_a_category_is_required_at_the_next_departure(): void
    {
        $this->actingAs($this->employee);

        Livewire::test(CategoryViews::class, ['category' => $this->miniExcavator])
            ->set('newLabel', 'Godet')
            ->call('add')
            ->assertHasNoErrors()
            ->assertSee('Godet');

        $reservation = $this->miniExcavatorReservation();
        $this->openSession($reservation);
        $missingLabels = app(MissingViews::class)->for($reservation, InspectionStep::Departure)->pluck('label');
        $this->assertCount(6, $missingLabels);
        $this->assertSame('Godet', $missingLabels->last());
    }

    public function test_removing_the_last_view_is_refused(): void
    {
        $this->actingAs($this->employee);
        CategoryView::factory()->for($this->miniExcavator, 'category')->create(['label' => 'Godet', 'position' => 1]);

        Livewire::test(CategoryViews::class, ['category' => $this->miniExcavator])
            ->call('remove', 1)
            ->assertHasErrors('refusal')
            ->assertSee('Au moins une vue est requise.');

        $this->assertSame(1, CategoryView::query()->where('machine_category_id', $this->miniExcavator->id)->count());
    }

    public function test_a_duplicate_label_in_the_category_is_refused(): void
    {
        $this->actingAs($this->employee);

        Livewire::test(CategoryViews::class, ['category' => $this->miniExcavator])
            ->set('newLabel', 'gauche')
            ->call('add')
            ->assertHasErrors('refusal');

        Livewire::test(CategoryViews::class, ['category' => $this->miniExcavator])
            ->call('edit', 1, 'Avant')
            ->set('editedLabel', 'Arrière')
            ->call('rename')
            ->assertHasErrors('refusal');

        $this->assertSame(0, CategoryView::query()->where('machine_category_id', $this->miniExcavator->id)->count());
    }

    public function test_views_can_be_renamed_reordered_and_removed(): void
    {
        $this->actingAs($this->employee);

        Livewire::test(CategoryViews::class, ['category' => $this->miniExcavator])
            ->call('edit', 5, 'Compteur d\'heures')
            ->set('editedLabel', 'Horamètre')
            ->call('rename')
            ->call('move', 5, -1)
            ->call('remove', 1)
            ->assertHasNoErrors();

        $this->assertSame(
            ['Arrière', 'Gauche', 'Horamètre', 'Droite'],
            CategoryView::query()->where('machine_category_id', $this->miniExcavator->id)->orderBy('position')->pluck('label')->all(),
        );
    }

    public function test_the_category_can_go_back_to_the_default_list(): void
    {
        $this->actingAs($this->employee);
        CategoryView::factory()->for($this->miniExcavator, 'category')->create(['label' => 'Godet', 'position' => 1]);

        Livewire::test(CategoryViews::class, ['category' => $this->miniExcavator])->call('resetToDefault');

        $reservation = $this->miniExcavatorReservation();
        $this->openSession($reservation);
        $this->assertCount(5, app(MissingViews::class)->for($reservation, InspectionStep::Departure));
    }

    public function test_the_screens_require_the_inspection_views_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('inspection.category-views.index'))->assertForbidden();
        $this->get(route('inspection.category-views.edit', $this->miniExcavator))->assertForbidden();
    }

    public function test_the_screens_list_the_categories_and_their_views(): void
    {
        $this->actingAs($this->employee);
        CategoryView::factory()->for($this->miniExcavator, 'category')->create(['label' => 'Godet', 'position' => 1]);
        MachineCategory::factory()->create(['name' => 'Nacelle']);

        $this->get(route('inspection.category-views.index'))
            ->assertOk()
            ->assertSeeInOrder(['Mini-pelle', '1', 'Personnalisée', 'Nacelle', '5', 'Par défaut']);
        $this->get(route('inspection.category-views.edit', $this->miniExcavator))
            ->assertOk()
            ->assertSee('Godet');
    }

    private function miniExcavatorReservation(): Reservation
    {
        $reservation = $this->reservationStartingToday();
        $reservation->machine()->associate(Machine::factory()->for($this->miniExcavator, 'category')->create())->save();

        return $reservation;
    }
}

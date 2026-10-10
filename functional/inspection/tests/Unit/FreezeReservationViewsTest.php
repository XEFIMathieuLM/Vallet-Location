<?php

namespace Functional\Inspection\Tests\Unit;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Actions\FreezeReservationViews;
use Functional\Inspection\Models\CategoryView;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreezeReservationViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_without_views_freezes_the_default_views(): void
    {
        $reservation = Reservation::factory()->create();

        app(FreezeReservationViews::class)->handle($reservation);

        $this->assertSame(
            ['Avant', 'Arrière', 'Gauche', 'Droite', 'Compteur d\'heures'],
            $this->frozenLabels($reservation),
        );
    }

    public function test_a_category_with_views_freezes_its_own_views_in_order(): void
    {
        $category = MachineCategory::factory()->create();
        CategoryView::factory()->for($category, 'category')->create(['label' => 'Godet', 'position' => 2]);
        CategoryView::factory()->for($category, 'category')->create(['label' => 'Bras', 'position' => 1]);
        $reservation = Reservation::factory()
            ->for(Machine::factory()->for($category, 'category'))
            ->create();

        app(FreezeReservationViews::class)->handle($reservation);

        $this->assertSame(['Bras', 'Godet'], $this->frozenLabels($reservation));
    }

    public function test_freezing_twice_keeps_the_first_list(): void
    {
        $category = MachineCategory::factory()->create();
        $reservation = Reservation::factory()
            ->for(Machine::factory()->for($category, 'category'))
            ->create();
        app(FreezeReservationViews::class)->handle($reservation);

        CategoryView::factory()->for($category, 'category')->create(['label' => 'Godet', 'position' => 1]);
        app(FreezeReservationViews::class)->handle($reservation);

        $this->assertCount(5, $this->frozenLabels($reservation));
        $this->assertNotContains('Godet', $this->frozenLabels($reservation));
    }

    /**
     * @return list<string>
     */
    private function frozenLabels(Reservation $reservation): array
    {
        /** @var list<string> $labels */
        $labels = ReservationView::query()
            ->where('reservation_id', $reservation->id)
            ->orderBy('position')
            ->pluck('label')
            ->all();

        return $labels;
    }
}

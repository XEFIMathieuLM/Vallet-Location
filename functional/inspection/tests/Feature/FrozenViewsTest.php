<?php

namespace Functional\Inspection\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Inspection\Actions\CategoryViews\AddCategoryView;
use Functional\Inspection\Actions\CategoryViews\RemoveCategoryView;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\MissingPhotosException;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrozenViewsTest extends TestCase
{
    use AssertsRefusals, BuildsPhotoSessions, RefreshDatabase;

    public function test_the_return_requires_the_views_photographed_at_departure_not_the_new_category_list(): void
    {
        $this->setUpPhotoStorage();
        $category = MachineCategory::factory()->create();
        $reservation = Reservation::factory()
            ->for(Machine::factory()->for($category, 'category'))
            ->between(CarbonImmutable::today(), CarbonImmutable::today()->addDays(3))
            ->create();
        $this->openSession($reservation);
        $this->photographEveryView($reservation, InspectionStep::Departure);
        app(DepartReservation::class)->handle($reservation);

        app(AddCategoryView::class)->handle($category, 'Godet');
        app(RemoveCategoryView::class)->handle($category, 1);

        $this->openSession($reservation, InspectionStep::Return);
        $this->assertRefused(
            MissingPhotosException::class,
            'Photos manquantes : Avant, Arrière, Gauche, Droite, Compteur d\'heures',
            fn () => app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState),
        );

        $this->photographEveryView($reservation, InspectionStep::Return);
        app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState);
        $this->assertSame(ReservationStatus::Closed, $reservation->fresh()?->status);
    }
}

<?php

namespace Functional\Inspection\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\ReturnReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Enums\ReturnCondition;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Actions\MissingViews;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\MissingPhotosException;
use Functional\Inspection\Models\CategoryView;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyReservationReturnTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    private MachineCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->category = MachineCategory::factory()->create();
        CategoryView::factory()->for($this->category, 'category')->create(['label' => 'Godet', 'position' => 1]);
        CategoryView::factory()->for($this->category, 'category')->create(['label' => 'Bras', 'position' => 2]);
    }

    public function test_opening_the_return_session_freezes_the_current_category_views(): void
    {
        $reservation = $this->reservationLeftBeforeGoLive();

        $this->openSession($reservation, InspectionStep::Return);

        $this->assertSame(['Godet', 'Bras'], app(MissingViews::class)->for($reservation, InspectionStep::Return)->pluck('label')->all());
    }

    public function test_the_return_requires_return_photos_only(): void
    {
        $reservation = $this->reservationLeftBeforeGoLive();
        $this->openSession($reservation, InspectionStep::Return);
        $this->photographEveryView($reservation, InspectionStep::Return);

        app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState);

        $this->assertSame(ReservationStatus::Closed, $reservation->fresh()?->status);
    }

    public function test_a_return_without_any_qr_code_is_refused_listing_every_category_view(): void
    {
        $reservation = $this->reservationLeftBeforeGoLive();

        $this->assertThrows(
            fn () => app(ReturnReservation::class)->handle($reservation, ReturnCondition::GoodState),
            MissingPhotosException::class,
            'Photos manquantes : Godet, Bras',
        );

        $this->assertSame(ReservationStatus::InProgress, $reservation->fresh()?->status);
        $this->assertSame(0, ReservationView::query()->where('reservation_id', $reservation->id)->count());
    }

    private function reservationLeftBeforeGoLive(): Reservation
    {
        $machine = Machine::factory()->for($this->category, 'category')->withStatus(MachineStatus::RentedOut)->create();

        return Reservation::factory()
            ->for($machine)
            ->between(CarbonImmutable::today()->subDays(5), CarbonImmutable::today()->addDays(2))
            ->withStatus(ReservationStatus::InProgress)
            ->create(['departed_at' => CarbonImmutable::now()->subDays(5)]);
    }
}

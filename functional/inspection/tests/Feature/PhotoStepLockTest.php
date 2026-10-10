<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Actions\DeletePhoto;
use Functional\Inspection\Actions\FindActivePhotoSession;
use Functional\Inspection\Actions\StorePhoto;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Exceptions\PhotoSessionUnavailableException;
use Functional\Inspection\Exceptions\StepAlreadyValidatedException;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PhotoStepLockTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
    }

    public function test_a_photo_cannot_be_deleted_once_the_departure_was_recorded_after_it_was_loaded(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->photographEveryView($reservation, InspectionStep::Departure);
        $photo = Photo::query()->with('reservation')->firstOrFail();

        $this->recordDepartureBehindTheScenes($reservation);

        $this->assertThrows(fn () => app(DeletePhoto::class)->handle($photo, null), StepAlreadyValidatedException::class);
        $this->assertModelExists($photo);
    }

    public function test_a_photo_cannot_be_added_once_the_departure_was_recorded_after_the_link_was_checked(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $checkedSession = PhotoSession::query()->with(['reservation', 'author'])->where('token_hash', PhotoSession::hashToken($token))->firstOrFail();
        $this->partialMock(FindActivePhotoSession::class, fn (MockInterface $mock) => $mock->shouldReceive('handle')->andReturn($checkedSession));

        $this->recordDepartureBehindTheScenes($reservation);

        $this->assertThrows(
            fn () => app(StorePhoto::class)->handle($token, $this->firstView($reservation)->id, $this->jpeg()),
            PhotoSessionUnavailableException::class,
        );
        $this->assertSame(0, Photo::query()->count());
    }

    private function recordDepartureBehindTheScenes(Reservation $reservation): void
    {
        Reservation::query()->whereKey($reservation->id)->update(['status' => ReservationStatus::InProgress]);
    }
}

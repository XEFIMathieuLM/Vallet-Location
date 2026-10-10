<?php

namespace Functional\Inspection\Tests\Concerns;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Functional\Inspection\Actions\FreezeReservationViews;
use Functional\Inspection\Actions\OpenPhotoSession;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait BuildsPhotoSessions
{
    use CreatesUsers;

    protected function setUpPhotoStorage(): void
    {
        Storage::fake('photos');
    }

    protected function reservationStartingToday(ReservationStatus $status = ReservationStatus::Confirmed): Reservation
    {
        return Reservation::factory()
            ->between(CarbonImmutable::today(), CarbonImmutable::today()->addDays(3))
            ->withStatus($status)
            ->create();
    }

    protected function openSession(Reservation $reservation, InspectionStep $step = InspectionStep::Departure): string
    {
        return app(OpenPhotoSession::class)->handle($reservation, $step, $this->userWithoutPermission());
    }

    protected function firstView(Reservation $reservation): ReservationView
    {
        return ReservationView::query()->where('reservation_id', $reservation->id)->orderBy('position')->firstOrFail();
    }

    protected function photographEveryView(Reservation $reservation, InspectionStep $step): void
    {
        app(FreezeReservationViews::class)->handle($reservation);

        ReservationView::query()->where('reservation_id', $reservation->id)->get()
            ->each(fn (ReservationView $view) => Photo::factory()->forView($view, $step)->create());
    }

    protected function seededEmployee(): Model&Authenticatable&AgencyMember
    {
        $this->seedPermissions();

        return $this->employee();
    }

    protected function jpeg(): UploadedFile
    {
        return UploadedFile::fake()->image('photo.jpg', 1200, 900);
    }
}

<?php

namespace Functional\Inspection\Tests\Concerns;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Actions\FreezeReservationViews;
use Functional\Inspection\Actions\OpenPhotoSession;
use Functional\Inspection\Database\Seeders\InspectionPermissionSeeder;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait BuildsPhotoSessions
{
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
        return app(OpenPhotoSession::class)->handle($reservation, $step, User::factory()->create());
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

    protected function employee(): User
    {
        $this->seed([PermissionSeeder::class, InspectionPermissionSeeder::class]);

        return User::factory()->create()->assignRole(PermissionSeeder::EMPLOYEE_ROLE);
    }

    protected function jpeg(): UploadedFile
    {
        return UploadedFile::fake()->image('photo.jpg', 1200, 900);
    }
}

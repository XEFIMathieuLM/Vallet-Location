<?php

namespace Functional\Inspection\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Inspection\Actions\FindActivePhotoSession;
use Functional\Inspection\Actions\MissingViews;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\Events\PhotoSessionChanged;
use Functional\Inspection\Exceptions\StepNotOpenException;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OpenPhotoSessionTest extends TestCase
{
    use AssertsRefusals, BuildsPhotoSessions, RefreshDatabase;

    public function test_opening_a_session_freezes_the_views_and_lists_them_all_as_missing(): void
    {
        Event::fake([PhotoSessionChanged::class]);
        $reservation = $this->reservationStartingToday();

        $this->openSession($reservation);

        $this->assertCount(5, app(MissingViews::class)->for($reservation, InspectionStep::Departure));
        Event::assertDispatched(PhotoSessionChanged::class, fn (PhotoSessionChanged $event): bool => $event->reservationId === $reservation->id && $event->isActive);
    }

    public function test_the_session_expires_after_thirty_minutes(): void
    {
        CarbonImmutable::setTestNow('2026-10-10 09:00:00');
        $reservation = $this->reservationStartingToday();

        $token = $this->openSession($reservation);

        $session = PhotoSession::query()->where('token_hash', PhotoSession::hashToken($token))->firstOrFail();
        $this->assertTrue($session->expires_at->equalTo(CarbonImmutable::parse('2026-10-10 09:30:00')));
    }

    public function test_a_new_session_revokes_the_previous_one_of_the_same_step(): void
    {
        $reservation = $this->reservationStartingToday();
        $firstToken = $this->openSession($reservation);

        $this->openSession($reservation);

        $firstSession = PhotoSession::query()->where('token_hash', PhotoSession::hashToken($firstToken))->firstOrFail();
        $this->assertSame(RevocationReason::Replaced, $firstSession->revoked_reason);
        $this->assertFalse(app(FindActivePhotoSession::class)->isActive($firstSession));
    }

    public function test_the_departure_session_cannot_be_opened_before_the_start_date(): void
    {
        $reservation = Reservation::factory()
            ->between(CarbonImmutable::tomorrow(), CarbonImmutable::tomorrow()->addDays(2))
            ->create();

        $this->assertRefused(StepNotOpenException::class, 'La prise de photos de départ n\'est pas ouverte', fn () => $this->openSession($reservation));
    }

    public function test_the_return_session_cannot_be_opened_on_a_confirmed_reservation(): void
    {
        $this->assertRefused(StepNotOpenException::class, 'La prise de photos de retour n\'est pas ouverte', fn () => $this->openSession($this->reservationStartingToday(), InspectionStep::Return));
    }

    public function test_the_departure_session_cannot_be_opened_once_the_machine_has_left(): void
    {
        $this->assertRefused(StepNotOpenException::class, 'La prise de photos de départ n\'est pas ouverte', fn () => $this->openSession($this->reservationStartingToday(ReservationStatus::InProgress)));
    }
}

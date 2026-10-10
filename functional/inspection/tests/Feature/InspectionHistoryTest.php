<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Inspection\Actions\DeletePhoto;
use Functional\Inspection\Actions\FindActivePhotoSession;
use Functional\Inspection\Actions\OpenPhotoSession;
use Functional\Inspection\Actions\ReportDamage;
use Functional\Inspection\Actions\ResolveDamage;
use Functional\Inspection\Actions\StorePhoto;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\History\InspectionHistoryEvent;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class InspectionHistoryTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->employee = $this->seededEmployee();
        $this->actingAs($this->employee);
    }

    public function test_qr_codes_and_their_revocation_are_kept_in_the_reservation_history(): void
    {
        $reservation = $this->reservationStartingToday();

        app(OpenPhotoSession::class)->handle($reservation, InspectionStep::Departure, $this->employee);
        app(OpenPhotoSession::class)->handle($reservation, InspectionStep::Departure, $this->employee);

        $this->assertSame(
            [InspectionHistoryEvent::PhotoSessionOpened->value, InspectionHistoryEvent::PhotoSessionRevoked->value, InspectionHistoryEvent::PhotoSessionOpened->value],
            $this->historyOf($reservation)->pluck('event')->all(),
        );
        $revocation = $this->historyOf($reservation)->firstWhere('event', InspectionHistoryEvent::PhotoSessionRevoked->value);
        $this->assertSame('replaced', $revocation?->properties['reason']);
        $this->assertSame($this->employee->id, $this->historyOf($reservation)->first()?->causer_id);
    }

    public function test_received_and_deleted_photos_are_kept_in_the_reservation_history(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = app(OpenPhotoSession::class)->handle($reservation, InspectionStep::Departure, $this->employee);
        $view = $this->firstView($reservation);

        $photo = app(StorePhoto::class)->handle($token, $view->id, $this->jpeg());
        app(DeletePhoto::class)->fromSession(app(FindActivePhotoSession::class)->handle($token), $photo->id);

        $received = $this->historyOf($reservation)->firstWhere('event', InspectionHistoryEvent::PhotoReceived->value);
        $deleted = $this->historyOf($reservation)->firstWhere('event', InspectionHistoryEvent::PhotoDeleted->value);
        $this->assertSame(['view' => 'Avant', 'step' => 'departure'], $received?->properties->only(['view', 'step'])->all());
        $this->assertSame($this->employee->id, $received?->causer_id);
        $this->assertNotNull($deleted);
    }

    public function test_reported_and_resolved_damages_are_kept_in_the_reservation_history(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->photographEveryView($reservation, InspectionStep::Return);

        $damage = app(ReportDamage::class)->handle($reservation, $this->firstView($reservation)->id, 'Choc', $this->employee);
        app(ResolveDamage::class)->handle($damage, $this->employee);

        $this->assertSame(
            [InspectionHistoryEvent::DamageReported->value, InspectionHistoryEvent::DamageResolved->value],
            $this->historyOf($reservation)->whereIn('event', [InspectionHistoryEvent::DamageReported->value, InspectionHistoryEvent::DamageResolved->value])->pluck('event')->values()->all(),
        );
        $this->assertSame([$this->employee->id, $this->employee->id], $this->historyOf($reservation)->whereIn('event', [InspectionHistoryEvent::DamageReported->value, InspectionHistoryEvent::DamageResolved->value])->pluck('causer_id')->values()->all());
    }

    public function test_every_entry_written_by_an_employee_records_the_author_agency(): void
    {
        $reservation = $this->reservationStartingToday();

        app(OpenPhotoSession::class)->handle($reservation, InspectionStep::Departure, $this->employee);

        $this->assertSame($this->employee->agencyId(), $this->historyOf($reservation)->sole()->properties['author_agency_id']);
    }

    public function test_the_history_never_contains_the_token(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = app(OpenPhotoSession::class)->handle($reservation, InspectionStep::Departure, $this->employee);
        app(StorePhoto::class)->handle($token, $this->firstView($reservation)->id, $this->jpeg());

        $this->assertStringNotContainsString($token, Activity::query()->get()->toJson());
    }

    /**
     * @return Collection<int, Activity>
     */
    private function historyOf(Reservation $reservation): Collection
    {
        return Activity::query()
            ->where('log_name', 'inspection')
            ->whereMorphedTo('subject', $reservation)
            ->oldest('id')
            ->get();
    }
}

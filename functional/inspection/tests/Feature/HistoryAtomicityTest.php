<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Models\Reservation;
use Functional\Inspection\Actions\ReportDamage;
use Functional\Inspection\Actions\RevokePhotoSessions;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Functional\Inspection\History\InspectionHistory;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class HistoryAtomicityTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    public function test_a_damage_is_not_kept_when_its_history_entry_cannot_be_written(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->photographEveryView($reservation, InspectionStep::Return);
        $this->historyFails();

        $this->assertThrows(
            fn () => app(ReportDamage::class)->handle($reservation, $this->firstView($reservation)->id, 'Choc', $this->userWithoutPermission()),
            RuntimeException::class,
        );

        $this->assertSame(0, Damage::query()->count());
    }

    public function test_a_revocation_is_not_kept_when_its_history_entry_cannot_be_written(): void
    {
        $reservation = Reservation::factory()->create();
        PhotoSession::factory()->for($reservation)->create();
        $this->historyFails();

        $this->assertThrows(
            fn () => app(RevokePhotoSessions::class)->handle($reservation, InspectionStep::cases(), RevocationReason::ReservationCancelled),
            RuntimeException::class,
        );

        $this->assertSame(0, PhotoSession::query()->whereNotNull('revoked_at')->count());
    }

    private function historyFails(): void
    {
        $this->mock(InspectionHistory::class, fn (MockInterface $mock) => $mock->shouldReceive('record')->andThrow(new RuntimeException('history unavailable')));
    }
}

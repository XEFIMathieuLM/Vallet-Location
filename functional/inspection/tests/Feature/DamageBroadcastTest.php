<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Inspection\Actions\ReportDamage;
use Functional\Inspection\Actions\ResolveDamage;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Events\DamageChanged;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DamageBroadcastTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    public function test_reporting_and_resolving_broadcast_only_the_reservation_and_its_unresolved_count(): void
    {
        Event::fake([DamageChanged::class]);
        $employee = $this->seededEmployee();
        $reservation = $this->reservationStartingToday(ReservationStatus::InProgress);
        $this->photographEveryView($reservation, InspectionStep::Return);
        $view = ReservationView::query()->where('reservation_id', $reservation->id)->firstOrFail();

        $damage = app(ReportDamage::class)->handle($reservation, $view->id, 'Choc', $employee);
        app(ReportDamage::class)->handle($reservation, $view->id, 'Rayure', $employee);
        app(ResolveDamage::class)->handle($damage, $employee);

        $payloads = [];
        Event::assertDispatched(DamageChanged::class, function (DamageChanged $event) use (&$payloads): bool {
            $this->assertEquals(new PrivateChannel('fleet'), $event->broadcastOn());
            $this->assertSame('damage.changed', $event->broadcastAs());
            $payloads[] = $event->broadcastWith();

            return true;
        });
        $this->assertSame([
            ['reservation_id' => $reservation->id, 'unresolved_count' => 1],
            ['reservation_id' => $reservation->id, 'unresolved_count' => 2],
            ['reservation_id' => $reservation->id, 'unresolved_count' => 1],
        ], $payloads);
    }
}

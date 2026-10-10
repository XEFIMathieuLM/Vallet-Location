<?php

namespace Functional\Booking\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Functional\Booking\Enums\ConflictReason;
use Functional\Booking\Events\ReservationChanged;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Events\FleetImported;
use Functional\Fleet\Events\MachineChanged;
use Functional\Fleet\Models\Machine;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class BroadcastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reservation_change_is_broadcast_on_the_fleet_channel_with_its_minimal_payload(): void
    {
        $reservation = Reservation::factory()
            ->between(CarbonImmutable::parse('2030-11-10'), CarbonImmutable::parse('2030-11-14'))
            ->create(['conflict_reason' => ConflictReason::VgpExpired]);
        $event = new ReservationChanged($reservation);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertEquals([new PrivateChannel('fleet')], $event->broadcastOn());
        $this->assertSame('reservation.changed', $event->broadcastAs());
        $this->assertSame([
            'id' => $reservation->id,
            'machine_id' => $reservation->machine_id,
            'status' => 'confirmed',
            'start_date' => '2030-11-10',
            'end_date' => '2030-11-14',
            'conflict_reason' => 'vgp_expired',
        ], $event->broadcastWith());
    }

    public function test_a_machine_change_is_broadcast_on_the_fleet_channel_with_its_minimal_payload(): void
    {
        $machine = Machine::factory()->subjectToVgpUntil(CarbonImmutable::parse('2030-12-31'))->create();
        $event = new MachineChanged($machine);

        $this->assertEquals([new PrivateChannel('fleet')], $event->broadcastOn());
        $this->assertSame('machine.changed', $event->broadcastAs());
        $this->assertSame([
            'id' => $machine->id,
            'status' => 'available',
            'agency_id' => $machine->agency_id,
            'vgp_due_date' => '2030-12-31',
        ], $event->broadcastWith());
    }

    public function test_a_fleet_import_is_broadcast_with_the_created_count(): void
    {
        $event = new FleetImported(12);

        $this->assertEquals([new PrivateChannel('fleet')], $event->broadcastOn());
        $this->assertSame('fleet.imported', $event->broadcastAs());
        $this->assertSame(['created_count' => 12], $event->broadcastWith());
    }

    public function test_the_fleet_channel_is_open_to_employees_only(): void
    {
        $this->seed(PermissionSeeder::class);
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => 'test-app',
        ]);
        Broadcast::forgetDrivers();
        require base_path('functional/fleet/routes/channels.php');
        $authorization = ['channel_name' => 'private-fleet', 'socket_id' => '1234.5678'];

        $this->actingAs(User::factory()->employee()->create())
            ->post('/broadcasting/auth', $authorization)
            ->assertOk();

        $this->actingAs(User::factory()->create())
            ->post('/broadcasting/auth', $authorization)
            ->assertForbidden();
    }
}

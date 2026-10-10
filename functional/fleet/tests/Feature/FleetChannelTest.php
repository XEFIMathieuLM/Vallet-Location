<?php

namespace Functional\Fleet\Tests\Feature;

use Functional\Fleet\Access\FleetPermission;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FleetChannelTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => 'test-app',
        ]);
        Broadcast::forgetDrivers();
        require base_path('functional/fleet/routes/channels.php');
    }

    public function test_the_fleet_channel_is_open_with_the_fleet_view_permission(): void
    {
        $this->authorizeFleetChannel($this->userWithPermissions(FleetPermission::ViewFleet))->assertOk();
        $this->authorizeFleetChannel($this->employee())->assertOk();
    }

    public function test_the_fleet_channel_is_closed_without_the_fleet_view_permission(): void
    {
        $this->authorizeFleetChannel($this->userWithPermissions(FleetPermission::ManageMachines))->assertForbidden();
        $this->authorizeFleetChannel($this->userWithoutPermission())->assertForbidden();
    }

    private function authorizeFleetChannel(Authenticatable $user): TestResponse
    {
        return $this->actingAs($user)->post('/broadcasting/auth', ['channel_name' => 'private-fleet', 'socket_id' => '1234.5678']);
    }
}

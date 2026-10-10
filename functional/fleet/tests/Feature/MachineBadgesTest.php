<?php

namespace Functional\Fleet\Tests\Feature;

use Functional\Fleet\Extensions\MachineBadges;
use Functional\Fleet\Livewire\MachineIndex;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Functional\Fleet\Tests\Doubles\StubMachineBadgeProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MachineBadgesTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private StubMachineBadgeProvider $badgeProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->badgeProvider = new StubMachineBadgeProvider;
        $this->app->instance(StubMachineBadgeProvider::class, $this->badgeProvider);
        app(MachineBadges::class)->register(StubMachineBadgeProvider::class);
    }

    public function test_the_fleet_screen_shows_the_registered_badges_with_their_link(): void
    {
        Machine::factory()->count(3)->create();

        Livewire::actingAs($this->employee())
            ->test(MachineIndex::class)
            ->assertSee('Badge de test')
            ->assertSeeHtml('https://example.test/badge');
    }

    public function test_the_provider_is_asked_once_for_every_displayed_machine(): void
    {
        $machines = Machine::factory()->count(5)->create();

        Livewire::actingAs($this->employee())->test(MachineIndex::class);

        $this->assertCount(1, $this->badgeProvider->requestedMachineIds);
        $this->assertEqualsCanonicalizing($machines->modelKeys(), $this->badgeProvider->requestedMachineIds[0]);
    }

    public function test_the_fleet_screen_listens_to_the_events_that_refresh_the_badges(): void
    {
        Machine::factory()->create();
        $component = Livewire::actingAs($this->employee())->test(MachineIndex::class);

        $component->dispatch(StubMachineBadgeProvider::LISTENER)->assertOk();
        $this->assertCount(2, $this->badgeProvider->requestedMachineIds);
    }

    public function test_without_any_provider_no_badge_is_requested(): void
    {
        $this->assertSame([], (new MachineBadges)->forMachines([1, 2]));
        $this->assertSame([], (new MachineBadges)->refreshListeners());
    }
}

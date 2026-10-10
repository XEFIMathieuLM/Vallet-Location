<?php

namespace Tests\Feature\Design;

use Carbon\CarbonImmutable;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopBarTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_the_top_bar_shows_the_page_title_and_the_paris_date_and_receives_teleported_actions(): void
    {
        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00', 'Europe/Paris'));

        $response = $this->actingAs($this->employee())->get(route('reservations.index'));

        $response->assertOk()
            ->assertSee('data-top-bar', escape: false)
            ->assertSeeInOrder([__('booking::reservations.list.title'), 'samedi 10 octobre 2026'])
            ->assertSee('id="top-bar-actions"', escape: false);
    }
}

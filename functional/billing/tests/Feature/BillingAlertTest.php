<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Livewire\BillingAlert;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingAlertTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->employee());
        CarbonImmutable::setTestNow('2026-11-15 10:00:00');
    }

    public function test_a_transmission_pending_for_more_than_a_day_shows_the_alert_with_the_count(): void
    {
        Transmission::factory()->count(2)->create(['created_at' => '2026-11-14 09:00:00']);

        Livewire::test(BillingAlert::class)
            ->assertSee('2 transmissions à traiter')
            ->assertSee(route('billing.transmissions'));
        $this->get(route('dashboard'))->assertSee('2 transmissions à traiter');
    }

    public function test_a_transmission_pending_for_less_than_a_day_shows_no_alert(): void
    {
        Transmission::factory()->create(['created_at' => '2026-11-14 11:00:00']);

        Livewire::test(BillingAlert::class)->assertDontSee('à traiter');
    }

    public function test_a_failed_transmission_shows_the_alert_at_once(): void
    {
        Transmission::factory()->failed()->create(['created_at' => '2026-11-15 09:59:00']);

        Livewire::test(BillingAlert::class)->assertSee('1 transmission à traiter');
    }
}

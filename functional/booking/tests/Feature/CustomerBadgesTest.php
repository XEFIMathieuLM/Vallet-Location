<?php

namespace Functional\Booking\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Extensions\CustomerBadges;
use Functional\Booking\Livewire\CreateReservationForm;
use Functional\Booking\Models\Customer;
use Functional\Booking\Tests\Doubles\TestCustomerBadgeProvider;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerBadgesTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-11-01'));
        $this->app->instance(CustomerBadges::class, new CustomerBadges);
        $this->actingAs($this->employee());
    }

    public function test_without_provider_the_form_shows_no_badge(): void
    {
        $customer = Customer::factory()->create(['name' => 'Bâti-Ouest']);

        Livewire::test(CreateReservationForm::class, ['machineId' => Machine::factory()->create()->id])
            ->assertSee('Bâti-Ouest')
            ->assertDontSee('Bâti-Ouest — ')
            ->set('customerId', $customer->id)
            ->assertDontSee('Précision du badge');
    }

    public function test_the_badges_of_every_provider_are_shown_for_the_listed_and_selected_customers(): void
    {
        $customer = Customer::factory()->create(['name' => 'Bâti-Ouest']);
        app(CustomerBadges::class)->register(TestCustomerBadgeProvider::class);

        Livewire::test(CreateReservationForm::class, ['machineId' => Machine::factory()->create()->id])
            ->assertSee('Bâti-Ouest — Badge de test')
            ->set('customerId', $customer->id)
            ->assertSee('Précision du badge');
    }

    public function test_the_registry_merges_the_refresh_listeners_of_its_providers(): void
    {
        app(CustomerBadges::class)->register(TestCustomerBadgeProvider::class);
        app(CustomerBadges::class)->register(TestCustomerBadgeProvider::class);

        $this->assertSame(['echo-private:test,.changed'], app(CustomerBadges::class)->refreshListeners());
        $this->assertSame([], app(CustomerBadges::class)->forCustomers([]));
    }
}

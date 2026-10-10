<?php

namespace Functional\Billing\Tests\Feature;

use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillingScreensHeadingsTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function billingScreens(): iterable
    {
        yield 'transmissions' => ['billing.transmissions', 'Transmissions à traiter'];
        yield 'exports' => ['billing.exports', 'Exports de secours'];
        yield 'statement' => ['billing.statement', 'Relevé de facturation'];
    }

    #[DataProvider('billingScreens')]
    public function test_each_billing_screen_uses_the_shared_page_heading(string $routeName, string $title): void
    {
        $this->actingAs($this->employee());

        $this->get(route($routeName))
            ->assertOk()
            ->assertSee("data-flux-heading>{$title}</h1>", false);
    }

    public function test_the_reservation_billing_section_uses_the_shared_section_heading(): void
    {
        $this->actingAs($this->employee());
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');

        $this->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('data-flux-heading>Facturation</h2>', false);
    }
}

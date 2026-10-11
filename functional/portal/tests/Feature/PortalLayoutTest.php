<?php

namespace Functional\Portal\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InspectsPrimaryActions;
use Tests\TestCase;

class PortalLayoutTest extends TestCase
{
    use BuildsPortalFixtures, InspectsPrimaryActions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
    }

    public function test_the_customer_space_has_its_navigation_its_black_band_and_its_black_footer(): void
    {
        $response = $this->actingAs($this->customerAccount(), 'customer')->get(route('portal.search'));

        $response->assertOk()
            ->assertSee('data-app-logo', escape: false)
            ->assertSeeInOrder([__('portal::navigation.search'), __('portal::navigation.requests'), __('portal::navigation.reservations')])
            ->assertSee('data-hero', escape: false)
            ->assertSee('data-portal-footer', escape: false);
    }

    public function test_the_customer_space_always_stays_in_the_light_theme(): void
    {
        $html = (string) $this->actingAs($this->customerAccount(), 'customer')->get(route('portal.search'))->getContent();

        $this->assertDoesNotMatchRegularExpression('/<html[^>]*class="[^"]*dark/', $html);
        $this->assertStringNotContainsString('flux.appearance', $html);
    }

    public function test_each_available_machine_is_a_card_with_its_pictogram_and_a_black_request_button(): void
    {
        $category = MachineCategory::factory()->create(['name' => 'Nacelle 12 m']);
        $agency = Agency::factory()->create(['name' => 'Rouen']);
        $this->reservableMachine($category, $agency, ['reference' => 'NAC-0001']);
        $this->reservableMachine($category, $agency, ['reference' => 'NAC-0002']);

        $html = (string) $this->actingAs($this->customerAccount(), 'customer')->get(route('portal.search', [
            'categorie' => $category->id,
            'agence' => $agency->id,
            'du' => '2026-11-12',
            'au' => '2026-11-16',
        ]))->assertOk()->assertSee('NAC-0001')->assertSee('NAC-0002')->getContent();

        $this->assertSame(2, substr_count($html, 'data-machine-card'));
        $this->assertSame(2, substr_count($html, 'data-variant="filled"'));
        $this->assertAtMostOnePrimaryAction($html);
    }

    public function test_the_login_page_carries_the_logotype_and_names_the_customer_space(): void
    {
        $this->get(route('portal.login'))
            ->assertOk()
            ->assertSee('data-app-logo', escape: false)
            ->assertSee(__('portal::navigation.hero_eyebrow'));
    }
}

<?php

namespace Tests\Feature\Design;

use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InspectsPrimaryActions;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use CreatesUsers, InspectsPrimaryActions, RefreshDatabase;

    public function test_a_visitor_chooses_between_the_staff_and_the_customer_spaces(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('data-app-logo', escape: false)
            ->assertSee('href="'.route('login').'"', escape: false)
            ->assertSee('href="'.route('portal.login').'"', escape: false);
        $this->assertStringNotContainsStringIgnoringCase('laravel', (string) $response->getContent());
    }

    public function test_a_signed_in_employee_goes_straight_to_the_dashboard(): void
    {
        $this->seedPermissions();

        $this->actingAs($this->employee())->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('dashboard').'"', escape: false);
    }

    public function test_the_staff_login_page_carries_the_logotype_and_a_single_yellow_action(): void
    {
        $html = (string) $this->get(route('login'))->assertOk()->assertSee('data-app-logo', escape: false)->getContent();

        $this->assertAtMostOnePrimaryAction($html);
    }
}

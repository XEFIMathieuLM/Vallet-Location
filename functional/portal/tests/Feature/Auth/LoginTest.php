<?php

namespace Functional\Portal\Tests\Feature\Auth;

use Functional\Portal\Livewire\Auth\Login;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    public function test_a_customer_logs_in_and_lands_on_the_search(): void
    {
        $account = $this->customerAccount(['email' => 'chantier@exemple.fr']);

        $this->get(route('portal.login'))->assertOk();
        Livewire::test(Login::class)
            ->set('email', 'Chantier@exemple.fr')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('portal.search'));

        $this->assertTrue(Auth::guard('customer')->user()?->is($account));
        $this->assertNull(Auth::guard('web')->user());
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $this->customerAccount(['email' => 'chantier@exemple.fr']);

        Livewire::test(Login::class)
            ->set('email', 'chantier@exemple.fr')
            ->set('password', 'mauvais')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertNull(Auth::guard('customer')->user());
    }

    public function test_the_sixth_attempt_within_a_minute_is_blocked(): void
    {
        $this->customerAccount(['email' => 'chantier@exemple.fr']);

        foreach (range(1, 5) as $attempt) {
            Livewire::test(Login::class)->set('email', 'chantier@exemple.fr')->set('password', "mauvais{$attempt}")->call('login');
        }

        Livewire::test(Login::class)
            ->set('email', 'chantier@exemple.fr')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertNull(Auth::guard('customer')->user());
    }

    public function test_a_customer_logs_out(): void
    {
        $this->actingAs($this->customerAccount(), 'customer')
            ->post(route('portal.logout'))
            ->assertRedirect(route('portal.login'));

        $this->assertNull(Auth::guard('customer')->user());
    }
}

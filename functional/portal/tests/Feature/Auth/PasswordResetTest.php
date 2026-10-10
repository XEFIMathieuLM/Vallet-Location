<?php

namespace Functional\Portal\Tests\Feature\Auth;

use Functional\Portal\Livewire\Auth\ForgotPassword;
use Functional\Portal\Livewire\Auth\ResetPassword;
use Functional\Portal\Notifications\ResetCustomerPassword;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Auth\Notifications\ResetPassword as StaffResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_a_customer_receives_a_reset_link_of_the_customer_space(): void
    {
        $account = $this->customerAccount(['email' => 'chantier@exemple.fr']);

        $this->get(route('portal.password.request'))->assertOk();
        Livewire::test(ForgotPassword::class)->set('email', 'chantier@exemple.fr')->call('sendResetLink')->assertSee(__('portal::auth.forgot.sent'));

        Notification::assertSentTo($account, ResetCustomerPassword::class, function (ResetCustomerPassword $notification) use ($account): bool {
            return str_contains($notification->toMail($account)->render(), route('portal.password.reset', ['token' => $notification->token], false));
        });
    }

    public function test_an_unknown_address_gets_the_same_answer(): void
    {
        Livewire::test(ForgotPassword::class)->set('email', 'inconnu@exemple.fr')->call('sendResetLink')->assertHasNoErrors()->assertSee(__('portal::auth.forgot.sent'));

        Notification::assertNothingSent();
    }

    public function test_the_reset_link_changes_the_password(): void
    {
        $account = $this->customerAccount(['email' => 'chantier@exemple.fr']);
        Livewire::test(ForgotPassword::class)->set('email', 'chantier@exemple.fr')->call('sendResetLink');
        $token = '';
        Notification::assertSentTo($account, ResetCustomerPassword::class, function (ResetCustomerPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->get(route('portal.password.reset', ['token' => $token, 'email' => 'chantier@exemple.fr']))->assertOk();
        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'chantier@exemple.fr')
            ->set('password', 'NouveauMotDePasse!2026')
            ->set('password_confirmation', 'NouveauMotDePasse!2026')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('portal.login'));

        $this->assertTrue(Hash::check('NouveauMotDePasse!2026', (string) $account->fresh()?->password));
    }

    public function test_the_employee_reset_flow_is_unchanged(): void
    {
        $this->seedPermissions();
        $employee = $this->employee();

        $this->post(route('password.email'), ['email' => $employee->getAttribute('email')]);

        Notification::assertSentTo($employee, StaffResetPassword::class);
    }
}

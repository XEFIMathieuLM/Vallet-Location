<?php

namespace Functional\Portal\Livewire\Auth;

use Functional\Portal\Actions\RegisterCustomerAccount;
use Functional\Portal\Models\CustomerAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('portal::layouts.guest')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ]);

        $resetStatus = Password::broker('customer_accounts')->reset(
            ['email' => RegisterCustomerAccount::normalizedEmail($this->email), 'password' => $this->password, 'token' => $this->token],
            function (CustomerAccount $account, string $password): void {
                $account->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            },
        );

        if ($resetStatus !== Password::PASSWORD_RESET) {
            $this->addError('email', __('portal::auth.reset.failed'));

            return;
        }

        session()->flash('status', __('portal::auth.reset.done'));
        $this->redirectRoute('portal.login', navigate: true);
    }

    public function render(): View
    {
        return view('portal::livewire.auth.reset-password')->title(__('portal::auth.reset.title'));
    }
}

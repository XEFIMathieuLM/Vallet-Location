<?php

namespace Functional\Portal\Livewire\Auth;

use Functional\Portal\Actions\RegisterCustomerAccount;
use Functional\Portal\Auth\CustomerLoginThrottle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('portal::layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $is_remembered = false;

    public function login(CustomerLoginThrottle $loginThrottle): void
    {
        $this->validate(['email' => ['required', 'string', 'email'], 'password' => ['required', 'string']]);
        $email = RegisterCustomerAccount::normalizedEmail($this->email);
        $ipAddress = (string) request()->ip();

        if ($loginThrottle->isLocked($email, $ipAddress)) {
            $this->addError('email', __('portal::auth.login.throttled', ['seconds' => $loginThrottle->secondsBeforeRetry($email, $ipAddress)]));

            return;
        }

        if (! Auth::guard('customer')->attempt(['email' => $email, 'password' => $this->password], $this->is_remembered)) {
            $loginThrottle->recordFailure($email, $ipAddress);
            $this->addError('email', __('portal::auth.login.failed'));

            return;
        }

        $loginThrottle->clear($email, $ipAddress);
        session()->regenerate();
        $this->redirectRoute('portal.search', navigate: true);
    }

    public function render(): View
    {
        return view('portal::livewire.auth.login')->title(__('portal::auth.login.title'));
    }
}

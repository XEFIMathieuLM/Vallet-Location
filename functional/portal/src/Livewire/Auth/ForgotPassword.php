<?php

namespace Functional\Portal\Livewire\Auth;

use Functional\Portal\Actions\RegisterCustomerAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('portal::layouts.guest')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $is_sent = false;

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'string', 'email']]);

        Password::broker('customer_accounts')->sendResetLink(['email' => RegisterCustomerAccount::normalizedEmail($this->email)]);

        $this->is_sent = true;
    }

    public function render(): View
    {
        return view('portal::livewire.auth.forgot-password')->title(__('portal::auth.forgot.title'));
    }
}

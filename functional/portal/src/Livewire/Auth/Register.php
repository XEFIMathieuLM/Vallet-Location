<?php

namespace Functional\Portal\Livewire\Auth;

use Functional\Booking\Enums\CustomerType;
use Functional\Portal\Actions\RegisterCustomerAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('portal::layouts.guest')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $declaredType = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(RegisterCustomerAccount $registerCustomerAccount): void
    {
        $this->email = RegisterCustomerAccount::normalizedEmail($this->email);
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('customer_accounts', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'declaredType' => ['required', Rule::enum(CustomerType::class)],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $account = $registerCustomerAccount->handle($this->name, $this->email, $this->phone, CustomerType::from($this->declaredType), $this->password);

        Auth::guard('customer')->login($account);
        session()->regenerate();

        $this->redirectRoute('portal.verification.notice', navigate: true);
    }

    public function render(): View
    {
        return view('portal::livewire.auth.register', ['customerTypes' => CustomerType::cases()])->title(__('portal::auth.register.title'));
    }
}

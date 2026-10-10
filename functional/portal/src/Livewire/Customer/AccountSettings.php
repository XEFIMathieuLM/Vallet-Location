<?php

namespace Functional\Portal\Livewire\Customer;

use Flux\Flux;
use Functional\Portal\Livewire\Concerns\ActsAsCustomerAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('portal::layouts.portal')]
class AccountSettings extends Component
{
    use ActsAsCustomerAccount;

    public string $name = '';

    public string $phone = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = $this->customerAccount()->name;
        $this->phone = $this->customerAccount()->phone;
    }

    public function updateProfile(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:30']]);

        $this->customerAccount()->update(['name' => trim($this->name), 'phone' => trim($this->phone)]);
        Flux::toast(text: __('portal::auth.account.saved'), variant: 'success');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'string', 'current_password:customer'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $this->customerAccount()->update(['password' => $this->password]);
        $this->reset('currentPassword', 'password', 'password_confirmation');
        Flux::toast(text: __('portal::auth.account.password_saved'), variant: 'success');
    }

    public function render(): View
    {
        return view('portal::livewire.customer.account-settings', ['account' => $this->customerAccount()])->title(__('portal::auth.account.title'));
    }
}

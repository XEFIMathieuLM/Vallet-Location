<?php

namespace Functional\Portal\Livewire\Auth;

use Flux\Flux;
use Functional\Portal\Livewire\Concerns\ActsAsCustomerAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('portal::layouts.guest')]
class VerifyEmailNotice extends Component
{
    use ActsAsCustomerAccount;

    private const MAX_RESENDS_PER_MINUTE = 6;

    public function mount(): void
    {
        if ($this->customerAccount()->hasVerifiedEmail()) {
            $this->redirectRoute('portal.search', navigate: true);
        }
    }

    public function resend(): void
    {
        $account = $this->customerAccount();
        $throttleKey = "portal-verification|{$account->id}";

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_RESENDS_PER_MINUTE)) {
            $this->addError('resend', __('portal::auth.verify.throttled'));

            return;
        }

        RateLimiter::hit($throttleKey);
        $account->sendEmailVerificationNotification();
        Flux::toast(text: __('portal::auth.verify.sent'), variant: 'success');
    }

    public function render(): View
    {
        return view('portal::livewire.auth.verify-email-notice', ['email' => $this->customerAccount()->email])->title(__('portal::auth.verify.title'));
    }
}

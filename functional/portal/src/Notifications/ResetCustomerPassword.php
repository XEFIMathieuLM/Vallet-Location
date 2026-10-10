<?php

namespace Functional\Portal\Notifications;

use Functional\Portal\Mail\ResetCustomerPasswordMail;
use Functional\Portal\Models\CustomerAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class ResetCustomerPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CustomerAccount $notifiable): Mailable
    {
        $resetUrl = route('portal.password.reset', ['token' => $this->token, 'email' => $notifiable->email]);

        return (new ResetCustomerPasswordMail($notifiable->name, $resetUrl))->to($notifiable->email);
    }
}

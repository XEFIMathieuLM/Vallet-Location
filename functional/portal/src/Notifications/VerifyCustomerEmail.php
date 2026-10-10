<?php

namespace Functional\Portal\Notifications;

use Functional\Portal\Mail\VerifyCustomerEmailMail;
use Functional\Portal\Models\CustomerAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyCustomerEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public const LINK_LIFETIME_MINUTES = 60;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CustomerAccount $notifiable): Mailable
    {
        $verificationUrl = URL::temporarySignedRoute(
            'portal.verification.verify',
            now()->addMinutes(self::LINK_LIFETIME_MINUTES),
            ['id' => $notifiable->id, 'hash' => sha1($notifiable->email)],
        );

        return (new VerifyCustomerEmailMail($notifiable->name, $verificationUrl))->to($notifiable->email);
    }
}

<?php

namespace Tests\Feature\Design;

use Functional\Booking\Models\Reservation;
use Functional\Certification\Mail\VgpCertificateMail;
use Functional\Certification\Models\VgpReport;
use Functional\Portal\Mail\ReservationRequestConfirmedMail;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_email_carries_the_wordmark_and_the_yellow_action_without_changing_its_text(): void
    {
        $html = (new ReservationRequestConfirmedMail(ReservationRequest::factory()->confirmed()->create()))->render();

        $this->assertStringContainsString('VALLET', $html);
        $this->assertStringContainsString('#ffed00', $html);
        $this->assertStringNotContainsStringIgnoringCase('laravel', $html);
        $this->assertStringContainsString(e(__('portal::mail.decision.confirmed.intro')), $html);
    }

    public function test_the_vgp_certificate_email_uses_the_same_theme(): void
    {
        $reservation = Reservation::factory()->create();
        $html = (new VgpCertificateMail($reservation, VgpReport::factory()->for($reservation->machine)->create()))->render();

        $this->assertStringContainsString('VALLET', $html);
        $this->assertStringNotContainsStringIgnoringCase('laravel', $html);
        $this->assertStringContainsString(e(__('certification::mail.intro')), $html);
    }
}

<?php

namespace Tests\Feature\Design;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Functional\Portal\Mail\ReservationRequestConfirmedMail;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private const FORBIDDEN_MARKS = ['renault', 'nouvelr', 'laravel'];

    public function test_no_screen_carries_another_brand_and_every_screen_carries_the_logotype(): void
    {
        $this->seedPermissions();
        Machine::factory()->create();
        $reservation = Reservation::factory()->withStatus(ReservationStatus::Confirmed)->create();
        $employee = $this->employee();

        $pages = [
            (string) $this->get(route('home'))->getContent(),
            (string) $this->get(route('login'))->getContent(),
            (string) $this->get(route('portal.login'))->getContent(),
            (string) $this->actingAs($employee)->get(route('dashboard'))->getContent(),
            (string) $this->actingAs($employee)->get(route('planning.index'))->getContent(),
            (string) $this->actingAs($employee)->get(route('reservations.show', $reservation))->getContent(),
            (string) $this->actingAs(CustomerAccount::factory()->create(), 'customer')->get(route('portal.search'))->getContent(),
        ];

        foreach ($pages as $html) {
            $this->assertStringContainsString('data-app-logo', $html);
            $this->assertNoForeignBrand($html);
        }
    }

    public function test_customer_emails_carry_no_other_brand(): void
    {
        $html = (new ReservationRequestConfirmedMail(ReservationRequest::factory()->confirmed()->create()))->render();

        $this->assertStringContainsString('VALLET', $html);
        $this->assertNoForeignBrand($html);
    }

    private function assertNoForeignBrand(string $html): void
    {
        foreach (self::FORBIDDEN_MARKS as $forbiddenMark) {
            $this->assertStringNotContainsStringIgnoringCase($forbiddenMark, $html);
        }
    }
}

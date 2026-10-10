<?php

namespace Functional\Inspection\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Livewire\PhoneCapture;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class PhoneLinkTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    private const EXPIRED_MESSAGE = 'Ce lien n&#039;est plus valable, générez un nouveau QR code depuis le poste.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
    }

    public function test_a_valid_link_shows_the_machine_the_customer_name_and_the_step_without_login(): void
    {
        $customer = Customer::factory()->create(['name' => 'BTP Savoie', 'phone' => '0601020304', 'email' => 'contact@btp-savoie.test']);
        $reservation = Reservation::factory()
            ->for($customer)
            ->between(CarbonImmutable::today(), CarbonImmutable::parse('2026-12-24'))
            ->create();
        $token = $this->openSession($reservation);

        $response = $this->get(route('inspection.phone', $token));

        $response->assertOk()
            ->assertSee($reservation->machine->reference)
            ->assertSee('BTP Savoie')
            ->assertSee('Photos de départ')
            ->assertSee('Compteur d&#039;heures', false)
            ->assertDontSee('0601020304')
            ->assertDontSee('contact@btp-savoie.test')
            ->assertDontSee('24/12/2026')
            ->assertDontSee('2026-12-24')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_an_unknown_token_shows_the_expired_page(): void
    {
        $this->get(route('inspection.phone', str_repeat('a', 40)))
            ->assertOk()
            ->assertSee(self::EXPIRED_MESSAGE, false);
    }

    public function test_a_link_older_than_thirty_minutes_shows_the_expired_page(): void
    {
        $token = $this->openSession($this->reservationStartingToday());

        $this->travel(31)->minutes();

        $this->get(route('inspection.phone', $token))->assertOk()->assertSee(self::EXPIRED_MESSAGE, false);
    }

    public function test_a_link_replaced_by_a_new_qr_code_shows_the_expired_page(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $this->openSession($reservation);

        $this->get(route('inspection.phone', $token))->assertOk()->assertSee(self::EXPIRED_MESSAGE, false);
    }

    public function test_a_link_whose_step_is_validated_shows_the_expired_page(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $reservation->update(['status' => ReservationStatus::InProgress]);

        $this->get(route('inspection.phone', $token))->assertOk()->assertSee(self::EXPIRED_MESSAGE, false);
    }

    public function test_a_link_of_a_cancelled_reservation_shows_the_expired_page(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $reservation->update(['status' => ReservationStatus::Cancelled]);

        $this->get(route('inspection.phone', $token))->assertOk()->assertSee(self::EXPIRED_MESSAGE, false);
    }

    public function test_the_link_is_rate_limited_to_sixty_requests_per_minute(): void
    {
        $url = route('inspection.phone', str_repeat('b', 40));

        foreach (range(1, 60) as $attempt) {
            $this->get($url)->assertOk();
        }

        $this->get($url)->assertTooManyRequests();
    }

    public function test_the_token_is_never_stored_in_clear(): void
    {
        $token = $this->openSession($this->reservationStartingToday());

        $this->assertSame(1, PhotoSession::query()->where('token_hash', hash('sha256', $token))->count());
        $this->assertSame(0, DB::table('photo_sessions')->where('token_hash', $token)->count());
    }

    public function test_deleting_a_photo_of_another_reservation_is_refused(): void
    {
        $token = $this->openSession($this->reservationStartingToday());
        $foreignPhoto = Photo::factory()->withFile()->create();

        Livewire::test(PhoneCapture::class, ['token' => $token])
            ->call('deletePhoto', $foreignPhoto->id)
            ->assertNotFound();

        $this->assertModelExists($foreignPhoto);
    }

    public function test_deleting_a_photo_of_the_other_step_is_refused(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $returnPhoto = Photo::factory()->forView($this->firstView($reservation), InspectionStep::Return)->create();

        Livewire::test(PhoneCapture::class, ['token' => $token])
            ->call('deletePhoto', $returnPhoto->id)
            ->assertNotFound();

        $this->assertModelExists($returnPhoto);
    }

    public function test_sending_a_photo_on_a_view_of_another_reservation_is_refused(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $foreignView = ReservationView::factory()->create();

        Livewire::test(PhoneCapture::class, ['token' => $token])
            ->set("uploads.{$foreignView->id}", $this->jpeg())
            ->assertNotFound();

        $this->assertSame(0, Photo::query()->count());
    }

    public function test_the_token_cannot_be_changed_from_the_browser(): void
    {
        $token = $this->openSession($this->reservationStartingToday());

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(PhoneCapture::class, ['token' => $token])->set('token', str_repeat('c', 40));
    }
}

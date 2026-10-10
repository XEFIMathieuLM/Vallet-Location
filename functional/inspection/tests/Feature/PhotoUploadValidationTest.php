<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Events\PhotoChanged;
use Functional\Inspection\Livewire\PhoneCapture;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhotoUploadValidationTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function acceptedFormats(): array
    {
        return ['jpeg' => ['photo.jpg'], 'png' => ['photo.png'], 'webp' => ['photo.webp']];
    }

    #[DataProvider('acceptedFormats')]
    public function test_jpeg_png_and_webp_photos_are_accepted(string $fileName): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $view = $this->firstView($reservation);

        Livewire::test(PhoneCapture::class, ['token' => $token])
            ->set("uploads.{$view->id}", UploadedFile::fake()->image($fileName, 800, 600))
            ->assertHasNoErrors();

        $photo = Photo::query()->sole();
        $this->assertSame(InspectionStep::Departure, $photo->step);
        $this->assertSame($view->id, $photo->reservation_view_id);
        $this->assertTrue($photo->getFirstMedia(Photo::COLLECTION)?->hasGeneratedConversion(Photo::THUMB));
    }

    public function test_a_received_photo_is_broadcast_with_the_remaining_missing_views(): void
    {
        Event::fake([PhotoChanged::class]);
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $view = $this->firstView($reservation);

        Livewire::test(PhoneCapture::class, ['token' => $token])->set("uploads.{$view->id}", $this->jpeg());

        Event::assertDispatched(PhotoChanged::class, fn (PhotoChanged $event): bool => $event->broadcastWith() === [
            'reservation_id' => $reservation->id,
            'step' => 'departure',
            'reservation_view_id' => $view->id,
            'missing_views_count' => 4,
        ]);
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $view = $this->firstView($reservation);

        Livewire::test(PhoneCapture::class, ['token' => $token])
            ->set("uploads.{$view->id}", UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'))
            ->assertHasErrors("uploads.{$view->id}");

        $this->assertSame(0, Photo::query()->count());
    }

    public function test_a_photo_over_fifteen_megabytes_is_refused(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $view = $this->firstView($reservation);

        Livewire::test(PhoneCapture::class, ['token' => $token])
            ->set("uploads.{$view->id}", UploadedFile::fake()->image('photo.jpg')->size(15361))
            ->assertHasErrors("uploads.{$view->id}");

        $this->assertSame(0, Photo::query()->count());
    }

    public function test_two_phones_on_the_same_link_feed_the_same_reservation_and_a_view_accepts_several_photos(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $view = $this->firstView($reservation);

        Livewire::test(PhoneCapture::class, ['token' => $token])->set("uploads.{$view->id}", $this->jpeg());
        Livewire::test(PhoneCapture::class, ['token' => $token])->set("uploads.{$view->id}", $this->jpeg());

        $this->assertSame(2, Photo::query()->where('reservation_id', $reservation->id)->where('reservation_view_id', $view->id)->count());
    }

    public function test_a_photo_sent_after_the_link_expired_is_refused(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $view = $this->firstView($reservation);
        $phone = Livewire::test(PhoneCapture::class, ['token' => $token]);

        $this->travel(31)->minutes();
        $phone->set("uploads.{$view->id}", $this->jpeg())->assertHasErrors('refusal');

        $this->assertSame(0, Photo::query()->count());
    }

    public function test_a_photo_can_be_deleted_from_the_phone_while_the_step_is_open(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $view = $this->firstView($reservation);
        $phone = Livewire::test(PhoneCapture::class, ['token' => $token])->set("uploads.{$view->id}", $this->jpeg());

        $phone->call('deletePhoto', Photo::query()->sole()->id)->assertHasNoErrors();

        $this->assertSame(0, Photo::query()->count());
    }
}

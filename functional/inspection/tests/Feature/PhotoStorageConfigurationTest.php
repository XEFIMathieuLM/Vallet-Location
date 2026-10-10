<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Inspection\Livewire\PhoneCapture;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class PhotoStorageConfigurationTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    public function test_one_photo_size_limit_drives_the_upload_and_the_media_library(): void
    {
        $maxPhotoKilobytes = config()->integer('inspection.max_photo_kilobytes');

        $this->assertContains("max:{$maxPhotoKilobytes}", config()->array('livewire.temporary_file_upload.rules'));
        $this->assertSame($maxPhotoKilobytes * 1024, config('media-library.max_file_size'));
    }

    public function test_a_photo_between_ten_and_fifteen_megabytes_is_stored(): void
    {
        $this->setUpPhotoStorage();
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $view = $this->firstView($reservation);

        Livewire::test(PhoneCapture::class, ['token' => $token])
            ->set("uploads.{$view->id}", $this->jpegOfAtLeast(12 * 1024 * 1024))
            ->assertHasNoErrors();

        $this->assertSame(1, Photo::query()->count());
    }

    private function jpegOfAtLeast(int $bytes): UploadedFile
    {
        $image = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $content = (string) file_get_contents($image->getPathname());

        return UploadedFile::fake()->createWithContent('photo.jpg', $content.str_repeat("\0", $bytes));
    }

    public function test_the_photos_disk_is_declared_by_the_inspection_layer(): void
    {
        $this->assertSame('local', config('filesystems.disks.photos.driver'));
        $this->assertSame('private', config('filesystems.disks.photos.visibility'));
        $this->assertSame(storage_path('app/private/photos'), config('filesystems.disks.photos.root'));
    }
}

<?php

namespace Functional\Inspection\Database\Factories;

use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Models\PhotoSession;
use Functional\Inspection\Models\ReservationView;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'reservation_view_id' => ReservationView::factory(),
            'reservation_id' => fn (array $attributes): int => ReservationView::query()->whereKey($attributes['reservation_view_id'])->value('reservation_id'),
            'step' => InspectionStep::Departure,
            'photo_session_id' => fn (array $attributes): int => PhotoSession::factory()->create([
                'reservation_id' => $attributes['reservation_id'],
                'step' => $attributes['step'],
            ])->id,
        ];
    }

    public function forView(ReservationView $view, InspectionStep $step): static
    {
        return $this->state(fn (): array => [
            'reservation_view_id' => $view->id,
            'reservation_id' => $view->reservation_id,
            'step' => $step,
        ]);
    }

    public function withFile(): static
    {
        return $this->afterCreating(function (Photo $photo): void {
            $photo->addMedia(UploadedFile::fake()->image('photo.jpg', 800, 600))->toMediaCollection(Photo::COLLECTION);
        });
    }
}

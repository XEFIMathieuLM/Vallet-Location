<?php

namespace Functional\Inspection\Models;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Database\Factories\PhotoFactory;
use Functional\Inspection\Enums\InspectionStep;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property int $reservation_id
 * @property int $reservation_view_id
 * @property InspectionStep $step
 * @property int $photo_session_id
 * @property Carbon $created_at
 * @property-read Reservation $reservation
 * @property-read ReservationView $view
 * @property-read PhotoSession $session
 */
#[Fillable(['reservation_id', 'reservation_view_id', 'step', 'photo_session_id'])]
#[UseFactory(PhotoFactory::class)]
class Photo extends Model implements HasMedia
{
    /** @use HasFactory<PhotoFactory> */
    use HasFactory, InteractsWithMedia, Prunable;

    public const COLLECTION = 'photo';

    public const THUMB = 'thumb';

    public const DISPLAY = 'display';

    protected function casts(): array
    {
        return [
            'step' => InspectionStep::class,
        ];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return HasMany<Damage, $this>
     */
    public function reservationDamages(): HasMany
    {
        return $this->hasMany(Damage::class, 'reservation_id', 'reservation_id');
    }

    /**
     * @return BelongsTo<ReservationView, $this>
     */
    public function view(): BelongsTo
    {
        return $this->belongsTo(ReservationView::class, 'reservation_view_id');
    }

    /**
     * @return BelongsTo<PhotoSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PhotoSession::class, 'photo_session_id');
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $retentionStart = CarbonImmutable::now()->subDays(config()->integer('inspection.photo_retention_days'));

        return self::query()
            ->where(fn (Builder $photos): Builder => $photos
                ->whereHas('reservation', fn (Builder $reservations): Builder => $reservations
                    ->where('status', ReservationStatus::Closed)
                    ->where('returned_at', '<', $retentionStart))
                ->whereDoesntHave('reservationDamages', fn (Builder $damages): Builder => $damages
                    ->where(fn (Builder $recentDamages): Builder => $recentDamages->whereNull('resolved_at')->orWhere('resolved_at', '>=', $retentionStart))))
            ->orWhere(fn (Builder $photos): Builder => $photos
                ->whereHas('reservation', fn (Builder $reservations): Builder => $reservations->where('status', ReservationStatus::Cancelled))
                ->where('created_at', '<', $retentionStart));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION)->singleFile()->useDisk('photos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion(self::THUMB)->nonQueued()->width(400);
        $this->addMediaConversion(self::DISPLAY)->queued()->width(1600);
    }
}

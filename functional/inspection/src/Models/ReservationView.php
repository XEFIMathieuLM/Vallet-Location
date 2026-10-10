<?php

namespace Functional\Inspection\Models;

use Functional\Booking\Models\Reservation;
use Functional\Inspection\Database\Factories\ReservationViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $reservation_id
 * @property string $label
 * @property int $position
 * @property-read Reservation $reservation
 */
#[Fillable(['reservation_id', 'label', 'position'])]
#[UseFactory(ReservationViewFactory::class)]
class ReservationView extends Model
{
    /** @use HasFactory<ReservationViewFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return HasMany<Photo, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}

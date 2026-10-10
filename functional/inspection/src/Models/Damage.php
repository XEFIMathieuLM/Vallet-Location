<?php

namespace Functional\Inspection\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Database\Factories\DamageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $reservation_id
 * @property int $reservation_view_id
 * @property string $comment
 * @property int $reported_by
 * @property CarbonImmutable $reported_at
 * @property int|null $resolved_by
 * @property CarbonImmutable|null $resolved_at
 * @property-read Reservation $reservation
 * @property-read ReservationView $view
 * @property-read User $reporter
 * @property-read User|null $resolver
 */
#[Fillable(['reservation_id', 'reservation_view_id', 'comment', 'reported_by', 'reported_at', 'resolved_by', 'resolved_at'])]
#[UseFactory(DamageFactory::class)]
class Damage extends Model
{
    /** @use HasFactory<DamageFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return [
            'reported_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
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
     * @return BelongsTo<ReservationView, $this>
     */
    public function view(): BelongsTo
    {
        return $this->belongsTo(ReservationView::class, 'reservation_view_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}

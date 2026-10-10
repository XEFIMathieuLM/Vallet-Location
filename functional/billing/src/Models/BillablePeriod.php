<?php

namespace Functional\Billing\Models;

use Carbon\CarbonImmutable;
use Functional\Billing\Database\Factories\BillablePeriodFactory;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Booking\Models\Reservation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $reservation_id
 * @property BillablePeriodKind $kind
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property int $days
 * @property-read Reservation $reservation
 * @property-read Transmission|null $transmission
 */
#[Fillable(['reservation_id', 'kind', 'start_date', 'end_date', 'days'])]
#[UseFactory(BillablePeriodFactory::class)]
class BillablePeriod extends Model
{
    /** @use HasFactory<BillablePeriodFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => BillablePeriodKind::class,
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
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
     * @return HasOne<Transmission, $this>
     */
    public function transmission(): HasOne
    {
        return $this->hasOne(Transmission::class);
    }
}

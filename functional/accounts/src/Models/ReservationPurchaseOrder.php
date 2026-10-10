<?php

namespace Functional\Accounts\Models;

use Carbon\CarbonImmutable;
use Functional\Accounts\Database\Factories\ReservationPurchaseOrderFactory;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\Agency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $reservation_id
 * @property string $number
 * @property int $entered_by
 * @property int $agency_id
 * @property CarbonImmutable $entered_at
 * @property-read Reservation $reservation
 * @property-read Model&AgencyMember $author
 * @property-read Agency $agency
 */
#[Fillable(['reservation_id', 'number', 'entered_by', 'agency_id', 'entered_at'])]
#[UseFactory(ReservationPurchaseOrderFactory::class)]
class ReservationPurchaseOrder extends Model
{
    /** @use HasFactory<ReservationPurchaseOrderFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return ['entered_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'entered_by');
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}

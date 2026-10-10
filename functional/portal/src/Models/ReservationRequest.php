<?php

namespace Functional\Portal\Models;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Portal\Database\Factories\ReservationRequestFactory;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\States\ReservationRequestState;
use Functional\Portal\States\ReservationRequestStateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $customer_account_id
 * @property int $machine_id
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property string|null $comment
 * @property int|null $indicative_daily_price_cents
 * @property ReservationRequestStatus $status
 * @property int|null $reservation_id
 * @property string|null $refusal_reason
 * @property int|null $decided_by
 * @property int|null $decided_agency_id
 * @property CarbonImmutable|null $decided_at
 * @property CarbonImmutable|null $customer_notified_at
 * @property CarbonImmutable $created_at
 * @property-read CustomerAccount $account
 * @property-read Machine $machine
 * @property-read Reservation|null $reservation
 * @property-read Agency|null $decidedAgency
 */
#[Fillable([
    'customer_account_id', 'machine_id', 'start_date', 'end_date', 'comment', 'indicative_daily_price_cents', 'status',
    'reservation_id', 'refusal_reason', 'decided_by', 'decided_agency_id', 'decided_at', 'customer_notified_at',
])]
#[UseFactory(ReservationRequestFactory::class)]
class ReservationRequest extends Model
{
    /** @use HasFactory<ReservationRequestFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'status' => ReservationRequestStatus::class,
            'decided_at' => 'immutable_datetime',
            'customer_notified_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function state(): ReservationRequestState
    {
        return ReservationRequestStateFactory::fromStatus($this->status);
    }

    /**
     * @return BelongsTo<CustomerAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    /**
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function decidedAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'decided_agency_id');
    }
}

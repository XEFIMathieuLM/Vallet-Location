<?php

namespace Functional\Billing\Models;

use Carbon\CarbonImmutable;
use Functional\Billing\Database\Factories\TransmissionFactory;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\States\TransmissionState;
use Functional\Billing\States\TransmissionStateFactory;
use Functional\Booking\Models\Reservation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $billable_period_id
 * @property int|null $damage_settlement_id
 * @property int $reservation_id
 * @property TransmissionStatus $status
 * @property int $attempts
 * @property CarbonImmutable|null $next_attempt_at
 * @property CarbonImmutable|null $last_attempt_at
 * @property CarbonImmutable|null $sent_at
 * @property TransmissionFailureReason|null $failure_reason
 * @property string|null $last_error
 * @property string|null $external_ref
 * @property int|null $billing_export_id
 * @property CarbonImmutable $created_at
 * @property-read BillablePeriod|null $billablePeriod
 * @property-read DamageSettlement|null $damageSettlement
 * @property-read Reservation $reservation
 * @property-read BillingExport|null $export
 */
#[Fillable([
    'billable_period_id', 'damage_settlement_id', 'reservation_id', 'status', 'attempts', 'next_attempt_at',
    'last_attempt_at', 'sent_at', 'failure_reason', 'last_error', 'external_ref', 'billing_export_id',
])]
#[UseFactory(TransmissionFactory::class)]
class Transmission extends Model
{
    /** @use HasFactory<TransmissionFactory> */
    use HasControl, HasFactory, HasUuids;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->attributes['status'] ??= TransmissionStatus::Pending->value;
        $this->attributes['attempts'] ??= 0;
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return [
            'status' => TransmissionStatus::class,
            'failure_reason' => TransmissionFailureReason::class,
            'next_attempt_at' => 'immutable_datetime',
            'last_attempt_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<BillablePeriod, $this>
     */
    public function billablePeriod(): BelongsTo
    {
        return $this->belongsTo(BillablePeriod::class);
    }

    /**
     * @return BelongsTo<DamageSettlement, $this>
     */
    public function damageSettlement(): BelongsTo
    {
        return $this->belongsTo(DamageSettlement::class);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<BillingExport, $this>
     */
    public function export(): BelongsTo
    {
        return $this->belongsTo(BillingExport::class, 'billing_export_id');
    }

    public function state(): TransmissionState
    {
        return TransmissionStateFactory::fromStatus($this->status);
    }
}

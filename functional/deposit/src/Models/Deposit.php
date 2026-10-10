<?php

namespace Functional\Deposit\Models;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Billing\Money\MoneyCast;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Database\Factories\DepositFactory;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Deposit\States\DepositState;
use Functional\Deposit\States\DepositStateFactory;
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
 * @property Money $amount
 * @property PaymentMethod $payment_method
 * @property string|null $payment_reference
 * @property DepositStatus $status
 * @property int $collected_by
 * @property int $collected_agency_id
 * @property CarbonImmutable $collected_at
 * @property CarbonImmutable|null $awaiting_since
 * @property Money|null $retained
 * @property Money|null $refunded
 * @property bool $is_no_damage_confirmed
 * @property int|null $closed_by
 * @property int|null $closed_agency_id
 * @property CarbonImmutable|null $closed_at
 * @property-read Reservation $reservation
 * @property-read Model&AgencyMember $collector
 * @property-read (Model&AgencyMember)|null $closer
 * @property-read Agency $collectedAgency
 * @property-read Agency|null $closedAgency
 */
#[Fillable([
    'reservation_id', 'amount', 'payment_method', 'payment_reference', 'status', 'collected_by', 'collected_agency_id',
    'collected_at', 'awaiting_since', 'retained', 'refunded', 'is_no_damage_confirmed', 'closed_by', 'closed_agency_id', 'closed_at',
])]
#[UseFactory(DepositFactory::class)]
class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class.':amount_cents',
            'retained' => MoneyCast::class.':retained_cents',
            'refunded' => MoneyCast::class.':refunded_cents',
            'payment_method' => PaymentMethod::class,
            'status' => DepositStatus::class,
            'collected_at' => 'immutable_datetime',
            'awaiting_since' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'is_no_damage_confirmed' => 'boolean',
        ];
    }

    public function state(): DepositState
    {
        return DepositStateFactory::fromStatus($this->status);
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
    public function collector(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'collected_by');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'closed_by');
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function collectedAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'collected_agency_id');
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function closedAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'closed_agency_id');
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}

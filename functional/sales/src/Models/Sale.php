<?php

namespace Functional\Sales\Models;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Billing\Money\MoneyCast;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Sales\Database\Factories\SaleFactory;
use Functional\Sales\Enums\SaleStatus;
use Functional\Sales\States\SaleState;
use Functional\Sales\States\SaleStateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $machine_id
 * @property SaleStatus $status
 * @property Money $asking_price
 * @property int|null $year_of_manufacture
 * @property int|null $operating_hours
 * @property string $condition
 * @property string|null $comment
 * @property int $agency_id
 * @property int $listed_by
 * @property int|null $buyer_id
 * @property int|null $accepted_offer_id
 * @property Money|null $final_price
 * @property CarbonImmutable|null $planned_handover_date
 * @property CarbonImmutable|null $handed_over_on
 * @property int|null $handed_over_by
 * @property string|null $cancellation_reason
 * @property int|null $cancelled_by
 * @property CarbonImmutable $created_at
 * @property-read Machine $machine
 * @property-read Agency $agency
 * @property-read Customer|null $buyer
 * @property-read SaleOffer|null $acceptedOffer
 * @property-read Model $lister
 */
#[Fillable([
    'machine_id', 'status', 'asking_price', 'year_of_manufacture', 'operating_hours', 'condition', 'comment',
    'agency_id', 'listed_by', 'buyer_id', 'accepted_offer_id', 'final_price', 'planned_handover_date',
    'handed_over_on', 'handed_over_by', 'cancellation_reason', 'cancelled_by',
])]
#[UseFactory(SaleFactory::class)]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'asking_price' => MoneyCast::class.':asking_price_cents',
            'final_price' => MoneyCast::class.':final_price_cents',
            'planned_handover_date' => 'immutable_date',
            'handed_over_on' => 'immutable_date',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function state(): SaleState
    {
        return SaleStateFactory::fromStatus($this->status);
    }

    /**
     * @return BelongsTo<Machine, $this>
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'buyer_id');
    }

    /**
     * @return BelongsTo<SaleOffer, $this>
     */
    public function acceptedOffer(): BelongsTo
    {
        return $this->belongsTo(SaleOffer::class, 'accepted_offer_id');
    }

    /**
     * @return HasMany<SaleOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(SaleOffer::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function lister(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'listed_by');
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}

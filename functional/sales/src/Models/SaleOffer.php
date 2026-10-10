<?php

namespace Functional\Sales\Models;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Billing\Money\MoneyCast;
use Functional\Booking\Models\Customer;
use Functional\Sales\Database\Factories\SaleOfferFactory;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\States\OfferState;
use Functional\Sales\States\OfferStateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $customer_id
 * @property Money $amount
 * @property CarbonImmutable $offered_on
 * @property OfferStatus $status
 * @property int $recorded_by
 * @property int|null $decided_by
 * @property CarbonImmutable|null $decided_at
 * @property-read Sale $sale
 * @property-read Customer $customer
 * @property-read Model $recorder
 */
#[Fillable(['sale_id', 'customer_id', 'amount', 'offered_on', 'status', 'recorded_by', 'decided_by', 'decided_at'])]
#[UseFactory(SaleOfferFactory::class)]
class SaleOffer extends Model
{
    /** @use HasFactory<SaleOfferFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'amount' => MoneyCast::class.':amount_cents',
            'offered_on' => 'immutable_date',
            'decided_at' => 'immutable_datetime',
        ];
    }

    public function state(): OfferState
    {
        return OfferStateFactory::fromStatus($this->status);
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'recorded_by');
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}

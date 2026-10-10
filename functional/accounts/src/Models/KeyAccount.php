<?php

namespace Functional\Accounts\Models;

use Carbon\CarbonImmutable;
use Functional\Accounts\Database\Factories\KeyAccountFactory;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $designated_by
 * @property CarbonImmutable $designated_at
 * @property-read Customer $customer
 * @property-read Model&AgencyMember $designator
 */
#[Fillable(['customer_id', 'designated_by', 'designated_at'])]
#[UseFactory(KeyAccountFactory::class)]
class KeyAccount extends Model
{
    /** @use HasFactory<KeyAccountFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return ['designated_at' => 'immutable_datetime'];
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
    public function designator(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'designated_by');
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}

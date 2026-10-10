<?php

namespace Functional\Billing\Models;

use Functional\Billing\Database\Factories\CustomerBillingAccountFactory;
use Functional\Booking\Models\Customer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $external_ref
 * @property-read Customer $customer
 */
#[Fillable(['customer_id', 'external_ref'])]
#[UseFactory(CustomerBillingAccountFactory::class)]
class CustomerBillingAccount extends Model
{
    /** @use HasFactory<CustomerBillingAccountFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

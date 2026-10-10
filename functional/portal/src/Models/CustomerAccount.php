<?php

namespace Functional\Portal\Models;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;
use Functional\Portal\Database\Factories\CustomerAccountFactory;
use Functional\Portal\Notifications\ResetCustomerPassword;
use Functional\Portal\Notifications\VerifyCustomerEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $phone
 * @property CustomerType $declared_type
 * @property string $password
 * @property int|null $customer_id
 * @property string|null $remember_token
 * @property CarbonImmutable $created_at
 * @property-read Customer|null $customer
 */
#[Fillable(['name', 'email', 'phone', 'declared_type', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(CustomerAccountFactory::class)]
class CustomerAccount extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<CustomerAccountFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'declared_type' => CustomerType::class,
            'email_verified_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'password' => 'hashed',
        ];
    }

    public function isAttached(): bool
    {
        return $this->customer_id !== null;
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<ReservationRequest, $this>
     */
    public function reservationRequests(): HasMany
    {
        return $this->hasMany(ReservationRequest::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyCustomerEmail);
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetCustomerPassword($token));
    }
}

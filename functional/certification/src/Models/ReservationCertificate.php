<?php

namespace Functional\Certification\Models;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Database\Factories\ReservationCertificateFactory;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchFailureReason;
use Functional\Certification\States\CertificateState;
use Functional\Certification\States\CertificateStateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $reservation_id
 * @property CertificateStatus $status
 * @property int $attempts
 * @property CarbonImmutable|null $next_attempt_at
 * @property DispatchFailureReason|null $last_failure_reason
 * @property CarbonImmutable $status_changed_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable $created_at
 * @property-read Reservation $reservation
 * @property-read CertificateDispatch|null $lastDispatch
 */
#[Fillable(['reservation_id', 'status', 'attempts', 'next_attempt_at', 'last_failure_reason', 'status_changed_at', 'delivered_at'])]
#[UseFactory(ReservationCertificateFactory::class)]
class ReservationCertificate extends Model
{
    /** @use HasFactory<ReservationCertificateFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => CertificateStatus::class,
            'last_failure_reason' => DispatchFailureReason::class,
            'next_attempt_at' => 'immutable_datetime',
            'status_changed_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
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
     * @return HasMany<CertificateDispatch, $this>
     */
    public function dispatches(): HasMany
    {
        return $this->hasMany(CertificateDispatch::class);
    }

    /**
     * @return HasOne<CertificateDispatch, $this>
     */
    public function lastDispatch(): HasOne
    {
        return $this->hasOne(CertificateDispatch::class)->latestOfMany();
    }

    public function state(): CertificateState
    {
        return CertificateStateFactory::fromStatus($this->status);
    }
}

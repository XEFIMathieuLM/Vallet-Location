<?php

namespace Functional\Inspection\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Booking\Models\Reservation;
use Functional\Inspection\Database\Factories\PhotoSessionFactory;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Enums\RevocationReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $reservation_id
 * @property InspectionStep $step
 * @property string $token_hash
 * @property int $created_by
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $revoked_at
 * @property RevocationReason|null $revoked_reason
 * @property-read Reservation $reservation
 * @property-read User $author
 */
#[Fillable(['reservation_id', 'step', 'token_hash', 'created_by', 'expires_at', 'revoked_at', 'revoked_reason'])]
#[UseFactory(PhotoSessionFactory::class)]
class PhotoSession extends Model
{
    /** @use HasFactory<PhotoSessionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'step' => InspectionStep::class,
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'revoked_reason' => RevocationReason::class,
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
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRevokedOrExpired(): bool
    {
        return $this->revoked_at !== null || ! $this->expires_at->isFuture();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}

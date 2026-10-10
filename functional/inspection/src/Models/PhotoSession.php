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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    use HasFactory, Prunable;

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

    /**
     * @return HasMany<Photo, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $retentionStart = CarbonImmutable::now()->subDays(config()->integer('inspection.photo_session_retention_days'));

        return self::query()
            ->whereDoesntHave('photos')
            ->where(fn (Builder $sessions): Builder => $sessions
                ->where('expires_at', '<', $retentionStart)
                ->orWhere('revoked_at', '<', $retentionStart));
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

<?php

namespace Functional\Billing\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Billing\Database\Factories\DamageSettlementFactory;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $damage_id
 * @property DamageOutcome $outcome
 * @property int|null $amount_cents
 * @property string|null $label
 * @property string|null $waiver_reason
 * @property int $settled_by
 * @property CarbonImmutable $settled_at
 * @property-read Damage $damage
 * @property-read User $settler
 * @property-read Transmission|null $transmission
 */
#[Fillable(['damage_id', 'outcome', 'amount_cents', 'label', 'waiver_reason', 'settled_by', 'settled_at'])]
#[UseFactory(DamageSettlementFactory::class)]
class DamageSettlement extends Model
{
    /** @use HasFactory<DamageSettlementFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return [
            'outcome' => DamageOutcome::class,
            'settled_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Damage, $this>
     */
    public function damage(): BelongsTo
    {
        return $this->belongsTo(Damage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    /**
     * @return HasOne<Transmission, $this>
     */
    public function transmission(): HasOne
    {
        return $this->hasOne(Transmission::class);
    }
}

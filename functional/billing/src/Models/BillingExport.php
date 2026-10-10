<?php

namespace Functional\Billing\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Billing\Database\Factories\BillingExportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $created_by
 * @property int $line_count
 * @property string $file_path
 * @property CarbonImmutable $created_at
 * @property-read User $creator
 */
#[Fillable(['created_by', 'line_count', 'file_path'])]
#[UseFactory(BillingExportFactory::class)]
class BillingExport extends Model
{
    /** @use HasFactory<BillingExportFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Transmission, $this>
     */
    public function transmissions(): HasMany
    {
        return $this->hasMany(Transmission::class);
    }
}

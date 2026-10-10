<?php

namespace Functional\Billing\Models;

use Carbon\CarbonImmutable;
use Functional\Billing\Database\Factories\BillingExportFactory;
use Functional\Fleet\Contracts\AgencyMember;
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
 * @property-read Model&AgencyMember $creator
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
     * @return BelongsTo<Model, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'created_by');
    }

    /**
     * @return HasMany<Transmission, $this>
     */
    public function transmissions(): HasMany
    {
        return $this->hasMany(Transmission::class);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}

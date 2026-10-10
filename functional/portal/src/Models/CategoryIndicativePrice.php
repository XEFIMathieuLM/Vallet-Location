<?php

namespace Functional\Portal\Models;

use Carbon\CarbonImmutable;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Database\Factories\CategoryIndicativePriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int $machine_category_id
 * @property int $daily_price_cents
 * @property int $updated_by
 * @property int $updated_agency_id
 * @property CarbonImmutable $updated_at
 * @property-read MachineCategory $category
 * @property-read Model|null $author
 */
#[Fillable(['machine_category_id', 'daily_price_cents', 'updated_by', 'updated_agency_id'])]
#[UseFactory(CategoryIndicativePriceFactory::class)]
class CategoryIndicativePrice extends Model
{
    /** @use HasFactory<CategoryIndicativePriceFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return [
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<MachineCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MachineCategory::class, 'machine_category_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'updated_by');
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}

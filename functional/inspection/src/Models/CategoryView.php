<?php

namespace Functional\Inspection\Models;

use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Database\Factories\CategoryViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $machine_category_id
 * @property string $label
 * @property int $position
 * @property-read MachineCategory $category
 */
#[Fillable(['machine_category_id', 'label', 'position'])]
#[UseFactory(CategoryViewFactory::class)]
class CategoryView extends Model
{
    /** @use HasFactory<CategoryViewFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<MachineCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MachineCategory::class, 'machine_category_id');
    }
}

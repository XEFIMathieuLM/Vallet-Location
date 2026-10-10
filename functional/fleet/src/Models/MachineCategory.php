<?php

namespace Functional\Fleet\Models;

use Functional\Fleet\Database\Factories\MachineCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_vgp_required
 */
#[Fillable(['name', 'is_vgp_required'])]
#[UseFactory(MachineCategoryFactory::class)]
class MachineCategory extends Model
{
    /** @use HasFactory<MachineCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_vgp_required' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Machine, $this>
     */
    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }
}

<?php

namespace Functional\Deposit\Models;

use Functional\Billing\Money\Money;
use Functional\Billing\Money\MoneyCast;
use Functional\Deposit\Database\Factories\DepositRateFactory;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int $id
 * @property int|null $machine_category_id
 * @property Money $amount
 * @property int|null $updated_by
 * @property-read MachineCategory|null $category
 */
#[Fillable(['machine_category_id', 'amount', 'updated_by'])]
#[UseFactory(DepositRateFactory::class)]
class DepositRate extends Model
{
    /** @use HasFactory<DepositRateFactory> */
    use HasControl, HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class.':amount_cents',
        ];
    }

    /**
     * @return BelongsTo<MachineCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MachineCategory::class, 'machine_category_id');
    }
}

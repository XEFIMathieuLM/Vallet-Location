<?php

namespace Functional\Portal\Actions;

use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Exceptions\InvalidIndicativePriceException;
use Functional\Portal\History\PortalHistory;
use Functional\Portal\Models\CategoryIndicativePrice;
use Functional\Portal\Pricing\IndicativePriceFormatter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class SetIndicativePrice
{
    public function __construct(
        private readonly IndicativePriceFormatter $priceFormatter,
        private readonly PortalHistory $portalHistory,
    ) {}

    public function handle(MachineCategory $category, string $typedAmount, Authenticatable&AgencyMember $author): CategoryIndicativePrice
    {
        $dailyPriceCents = $this->priceFormatter->centsFromEuros($typedAmount) ?? throw InvalidIndicativePriceException::of($typedAmount);

        return DB::transaction(function () use ($category, $dailyPriceCents, $author): CategoryIndicativePrice {
            MachineCategory::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();
            $previousPriceCents = CategoryIndicativePrice::query()->where('machine_category_id', $category->id)->value('daily_price_cents');

            $indicativePrice = CategoryIndicativePrice::query()->updateOrCreate(
                ['machine_category_id' => $category->id],
                ['daily_price_cents' => $dailyPriceCents, 'updated_by' => $author->getAuthIdentifier(), 'updated_agency_id' => $author->agencyId()],
            );

            $this->portalHistory->record($category, PortalHistoryEvent::IndicativePriceSet, $author, [
                'previous_price_cents' => is_int($previousPriceCents) ? $previousPriceCents : null,
                'new_price_cents' => $dailyPriceCents,
                'new_price' => $this->priceFormatter->amount($dailyPriceCents),
            ]);

            return $indicativePrice;
        });
    }
}

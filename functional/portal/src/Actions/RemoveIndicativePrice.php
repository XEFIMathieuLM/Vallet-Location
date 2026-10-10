<?php

namespace Functional\Portal\Actions;

use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\History\PortalHistory;
use Functional\Portal\Models\CategoryIndicativePrice;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class RemoveIndicativePrice
{
    public function __construct(private readonly PortalHistory $portalHistory) {}

    public function handle(MachineCategory $category, Authenticatable&AgencyMember $author): void
    {
        DB::transaction(function () use ($category, $author): void {
            $previousPriceCents = CategoryIndicativePrice::query()->where('machine_category_id', $category->id)->lockForUpdate()->value('daily_price_cents');

            if (! is_int($previousPriceCents)) {
                return;
            }

            CategoryIndicativePrice::query()->where('machine_category_id', $category->id)->delete();
            $this->portalHistory->record($category, PortalHistoryEvent::IndicativePriceRemoved, $author, ['previous_price_cents' => $previousPriceCents]);
        });
    }
}

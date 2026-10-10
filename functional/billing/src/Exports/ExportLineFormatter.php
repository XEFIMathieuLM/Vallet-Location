<?php

namespace Functional\Billing\Exports;

use Functional\Billing\Lines\BillableLine;

final class ExportLineFormatter
{
    public const COLUMNS = [
        'idempotency_key', 'type', 'customer_ref', 'reservation_ref', 'machine_reference', 'machine_category',
        'home_agency', 'booking_agency', 'period_start', 'period_end', 'period_kind', 'days',
        'damage_view', 'damage_comment', 'label', 'amount_excl_tax', 'source_ref', 'sale_date', 'purchase_order_number',
    ];

    /**
     * @return array<string, string|int|null>
     */
    public function format(BillableLine $billableLine): array
    {
        $line = [...$billableLine->toArray(), 'amount_excl_tax' => $billableLine->amountExclTax()?->format()];

        return array_map(fn (string $column): string|int|null => $line[$column] ?? null, array_combine(self::COLUMNS, self::COLUMNS));
    }
}

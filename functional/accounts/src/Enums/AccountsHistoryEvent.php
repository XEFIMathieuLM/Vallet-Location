<?php

namespace Functional\Accounts\Enums;

enum AccountsHistoryEvent: string
{
    case KeyAccountDesignated = 'key_account_designated';
    case KeyAccountRevoked = 'key_account_revoked';
    case PurchaseOrderEntered = 'purchase_order_entered';
    case PurchaseOrderCorrected = 'purchase_order_corrected';

    /**
     * @param  array<string, string|int|null>  $details
     */
    public function description(array $details): string
    {
        return __("accounts::history.{$this->value}", array_map(fn (string|int|null $detail): string => (string) $detail, $details));
    }
}

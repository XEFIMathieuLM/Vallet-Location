<?php

namespace Functional\Accounts\Enums;

use Functional\Fleet\Contracts\HasLabel;

enum PurchaseOrderSectionStatus: string implements HasLabel
{
    case Hidden = 'hidden';
    case Optional = 'optional';
    case Required = 'required';
    case Entered = 'entered';
    case Frozen = 'frozen';

    public function label(): string
    {
        return __("accounts::purchase_orders.statuses.{$this->value}");
    }

    public function isReadyForDeparture(): bool
    {
        return $this !== self::Required;
    }

    public function canBeEdited(): bool
    {
        return $this !== self::Hidden && $this !== self::Frozen;
    }

    public function color(): string
    {
        return match ($this) {
            self::Required => 'amber',
            self::Entered, self::Frozen => 'green',
            self::Hidden, self::Optional => 'zinc',
        };
    }
}

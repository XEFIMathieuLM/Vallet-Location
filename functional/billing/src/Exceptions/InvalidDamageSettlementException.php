<?php

namespace Functional\Billing\Exceptions;

final class InvalidDamageSettlementException extends BillingRefusalException
{
    public static function amountNotPositive(): self
    {
        return new self('A billed damage needs a strictly positive amount.', 'billing::damages.refusals.amount_required');
    }

    public static function labelMissing(): self
    {
        return new self('A billed damage needs a repair label.', 'billing::damages.refusals.label_required');
    }

    public static function waiverReasonMissing(): self
    {
        return new self('A waived damage needs a reason.', 'billing::damages.refusals.waiver_reason_required');
    }
}

<?php

namespace Functional\Deposit\Exceptions;

use Functional\Billing\Money\Money;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Fleet\Exceptions\RefusalException;

final class DepositRefusedException extends RefusalException
{
    public static function customerTypeMissing(): self
    {
        return new self('The customer type must be set before departure.', 'deposit::refusals.customer_type_missing');
    }

    public static function notCollected(Money $expected): self
    {
        return new self("The deposit of {$expected->minorUnits} cents must be collected before departure.", 'deposit::refusals.not_collected', ['amount' => $expected->format()]);
    }

    public static function reservationNotConfirmed(): self
    {
        return new self('A deposit can only be collected on a confirmed reservation.', 'deposit::refusals.reservation_not_confirmed');
    }

    public static function customerNotIndividual(): self
    {
        return new self('Only an individual customer pays a deposit.', 'deposit::refusals.customer_not_individual');
    }

    public static function alreadyCollected(): self
    {
        return new self('The deposit of this reservation is already collected.', 'deposit::refusals.already_collected');
    }

    public static function referenceRequired(PaymentMethod $method): self
    {
        return new self("A reference is required for payment method [{$method->value}].", 'deposit::refusals.reference_required', ['method' => $method]);
    }

    public static function reasonRequired(): self
    {
        return new self('A reason is required to correct a payment.', 'deposit::refusals.reason_required');
    }

    public static function noDamageNotConfirmed(): self
    {
        return new self('The absence of damage must be confirmed before refunding.', 'deposit::refusals.no_damage_not_confirmed');
    }

    public static function damagesToSettle(int $unresolvedCount): self
    {
        return new self("{$unresolvedCount} damages must be settled before refunding.", 'deposit::refusals.damages_to_settle', ['count' => $unresolvedCount], $unresolvedCount);
    }

    public static function notRefundable(DepositStatus $status): self
    {
        return new self("A deposit in status [{$status->value}] cannot be refunded.", 'deposit::refusals.not_refundable', ['status' => $status]);
    }

    public static function notSettleable(DepositStatus $status): self
    {
        return new self("A deposit in status [{$status->value}] cannot be settled.", 'deposit::refusals.not_settleable', ['status' => $status]);
    }

    public static function alreadyClosed(): self
    {
        return new self('The deposit is already refunded or settled.', 'deposit::refusals.already_closed');
    }

    public static function invalidAmount(): self
    {
        return new self('A deposit amount must be strictly positive.', 'deposit::refusals.invalid_amount');
    }
}

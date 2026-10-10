<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\OfferStatus;

final class OfferStateFactory
{
    public static function fromStatus(OfferStatus $status): OfferState
    {
        return match ($status) {
            OfferStatus::Pending => new PendingOfferState,
            OfferStatus::Accepted => new AcceptedOfferState,
            OfferStatus::Rejected => new RejectedOfferState,
            OfferStatus::Withdrawn => new WithdrawnOfferState,
        };
    }
}

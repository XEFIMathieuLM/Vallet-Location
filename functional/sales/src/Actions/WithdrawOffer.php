<?php

namespace Functional\Sales\Actions;

use Functional\Fleet\Contracts\AgencyMember;
use Functional\Sales\Enums\OfferTransition;
use Functional\Sales\Models\SaleOffer;
use Functional\Sales\Offers\OfferDecision;
use Illuminate\Database\Eloquent\Model;

final class WithdrawOffer
{
    public function __construct(private readonly OfferDecision $offerDecision) {}

    public function handle(Model&AgencyMember $author, SaleOffer $offer): SaleOffer
    {
        return $this->offerDecision->apply($author, $offer, OfferTransition::Withdraw);
    }
}

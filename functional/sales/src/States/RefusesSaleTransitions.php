<?php

namespace Functional\Sales\States;

use Functional\Sales\Enums\SaleTransition;
use Functional\Sales\Exceptions\IllegalSaleTransitionException;

trait RefusesSaleTransitions
{
    public function reserve(): SaleState
    {
        throw IllegalSaleTransitionException::for($this, SaleTransition::Reserve);
    }

    public function release(): SaleState
    {
        throw IllegalSaleTransitionException::for($this, SaleTransition::Release);
    }

    public function sell(): SaleState
    {
        throw IllegalSaleTransitionException::for($this, SaleTransition::Sell);
    }

    public function cancel(): SaleState
    {
        throw IllegalSaleTransitionException::for($this, SaleTransition::Cancel);
    }
}

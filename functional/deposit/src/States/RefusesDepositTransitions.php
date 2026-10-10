<?php

namespace Functional\Deposit\States;

use Functional\Deposit\Exceptions\IllegalDepositTransitionException;

trait RefusesDepositTransitions
{
    public function toRefund(): DepositState
    {
        throw IllegalDepositTransitionException::for($this, 'to_refund');
    }

    public function blockByDamage(): DepositState
    {
        throw IllegalDepositTransitionException::for($this, 'block_by_damage');
    }

    public function toSettle(): DepositState
    {
        throw IllegalDepositTransitionException::for($this, 'to_settle');
    }

    public function refund(): DepositState
    {
        throw IllegalDepositTransitionException::for($this, 'refund');
    }

    public function settle(): DepositState
    {
        throw IllegalDepositTransitionException::for($this, 'settle');
    }
}

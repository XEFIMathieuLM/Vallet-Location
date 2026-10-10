<?php

namespace Functional\Deposit\States;

use Functional\Deposit\Enums\DepositStatus;

interface DepositState
{
    public function status(): DepositStatus;

    public function toRefund(): DepositState;

    public function blockByDamage(): DepositState;

    public function toSettle(): DepositState;

    public function refund(): DepositState;

    public function settle(): DepositState;
}

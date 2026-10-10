<?php

namespace Functional\Deposit\Access;

enum DepositPermission: string
{
    case ManageDeposits = 'deposits.manage';
    case ManageDepositRates = 'deposit_rates.manage';
}

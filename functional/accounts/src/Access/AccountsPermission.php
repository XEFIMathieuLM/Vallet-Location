<?php

namespace Functional\Accounts\Access;

enum AccountsPermission: string
{
    case ManageKeyAccounts = 'key_accounts.manage';
    case ManagePurchaseOrders = 'purchase_orders.manage';
}

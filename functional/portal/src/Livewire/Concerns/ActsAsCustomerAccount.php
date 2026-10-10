<?php

namespace Functional\Portal\Livewire\Concerns;

use Functional\Portal\Models\CustomerAccount;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait ActsAsCustomerAccount
{
    protected function customerAccount(): CustomerAccount
    {
        $account = Auth::guard('customer')->user();

        abort_unless($account instanceof CustomerAccount, Response::HTTP_FORBIDDEN);

        return $account;
    }
}

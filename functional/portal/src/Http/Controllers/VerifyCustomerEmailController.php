<?php

namespace Functional\Portal\Http\Controllers;

use Functional\Portal\Models\CustomerAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyCustomerEmailController
{
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        $account = $request->user('customer');

        abort_unless($account instanceof CustomerAccount && $account->id === $id && hash_equals(sha1($account->email), $hash), Response::HTTP_FORBIDDEN);

        $account->markEmailAsVerified();

        return redirect()->route('portal.search');
    }
}

<?php

namespace Functional\Accounts\Livewire\Concerns;

use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait ActsAsAuthor
{
    protected function author(): Authenticatable&AgencyMember
    {
        $user = Auth::user();

        abort_unless($user instanceof AgencyMember, Response::HTTP_FORBIDDEN);

        return $user;
    }
}

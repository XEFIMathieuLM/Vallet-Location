<?php

namespace Functional\Certification\Livewire\Concerns;

use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait ActsAsAgencyMember
{
    protected function agencyMember(): Authenticatable&AgencyMember
    {
        $user = Auth::user();

        abort_unless($user instanceof AgencyMember, Response::HTTP_FORBIDDEN);

        return $user;
    }
}

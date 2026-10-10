<?php

namespace Functional\Inspection\Livewire\Concerns;

use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait ActsAsAgencyMember
{
    protected function agencyMember(): Model&AgencyMember
    {
        $user = Auth::user();

        abort_unless($user instanceof AgencyMember, Response::HTTP_FORBIDDEN);

        return $user;
    }
}

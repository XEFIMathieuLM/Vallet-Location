<?php

namespace Functional\Inspection\Actions;

use Functional\Inspection\Exceptions\PhotoSessionUnavailableException;
use Functional\Inspection\Models\PhotoSession;

class FindActivePhotoSession
{
    public function handle(string $token): PhotoSession
    {
        return $this->find($token) ?? throw PhotoSessionUnavailableException::make();
    }

    public function find(string $token): ?PhotoSession
    {
        $session = PhotoSession::query()
            ->with('reservation')
            ->where('token_hash', PhotoSession::hashToken($token))
            ->first();

        return $session?->isActive() === true ? $session : null;
    }
}

<?php

namespace Functional\Portal\Auth;

use Illuminate\Support\Facades\RateLimiter;

final class CustomerLoginThrottle
{
    private const MAX_ATTEMPTS = 5;

    public function isLocked(string $email, string $ipAddress): bool
    {
        return RateLimiter::tooManyAttempts($this->key($email, $ipAddress), self::MAX_ATTEMPTS);
    }

    public function secondsBeforeRetry(string $email, string $ipAddress): int
    {
        return RateLimiter::availableIn($this->key($email, $ipAddress));
    }

    public function recordFailure(string $email, string $ipAddress): void
    {
        RateLimiter::hit($this->key($email, $ipAddress));
    }

    public function clear(string $email, string $ipAddress): void
    {
        RateLimiter::clear($this->key($email, $ipAddress));
    }

    private function key(string $email, string $ipAddress): string
    {
        return 'portal-login|'.mb_strtolower(trim($email)).'|'.$ipAddress;
    }
}

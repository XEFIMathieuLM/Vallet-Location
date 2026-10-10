<?php

namespace Functional\Booking\Extensions;

use Functional\Booking\Contracts\CustomerChangeGuard;

final class CustomerChangeGuards
{
    /**
     * @var list<class-string<CustomerChangeGuard>>
     */
    private array $guardClasses = [];

    /**
     * @param  class-string<CustomerChangeGuard>  $guardClass
     */
    public function register(string $guardClass): void
    {
        $this->guardClasses[] = $guardClass;
    }

    /**
     * @return list<CustomerChangeGuard>
     */
    public function all(): array
    {
        return array_map(fn (string $guardClass): CustomerChangeGuard => app($guardClass), $this->guardClasses);
    }
}

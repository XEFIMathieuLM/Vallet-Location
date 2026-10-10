<?php

namespace Functional\Inspection\Queries;

use Functional\Inspection\Models\Damage;
use Illuminate\Database\Eloquent\Builder;

final class DamagesToHandle
{
    /**
     * @return Builder<Damage>
     */
    public function query(): Builder
    {
        return Damage::query()->whereNull('resolved_at');
    }
}

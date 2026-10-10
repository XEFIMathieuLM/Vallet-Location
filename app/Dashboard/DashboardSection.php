<?php

namespace App\Dashboard;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final readonly class DashboardSection
{
    /**
     * @param  Collection<int, Model>  $items
     */
    public function __construct(
        public Collection $items,
        public int $total,
    ) {}

    /**
     * @param  Builder<covariant Model>  $query
     */
    public static function fromQuery(Builder $query, int $limit): self
    {
        return new self((clone $query)->limit($limit)->get(), $query->toBase()->getCountForPagination());
    }

    public function hasMore(): bool
    {
        return $this->total > $this->items->count();
    }
}

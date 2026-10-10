<?php

namespace Functional\Billing\Extensions;

use Functional\Billing\Contracts\BillableSource;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Exceptions\UnknownBillableSourceException;

final class BillableSources
{
    /**
     * @var array<string, class-string<BillableSource>>
     */
    private array $sourceClasses = [];

    /**
     * @param  class-string<BillableSource>  $sourceClass
     */
    public function register(string $sourceClass): void
    {
        $source = app($sourceClass);
        $this->sourceClasses[$source->type()->value] = $sourceClass;
    }

    public function for(BillableLineType $type): BillableSource
    {
        $sourceClass = $this->sourceClasses[$type->value] ?? throw UnknownBillableSourceException::forType($type);

        return app($sourceClass);
    }
}

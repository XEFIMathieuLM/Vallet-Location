<?php

namespace Functional\Fleet\Tests\Concerns;

use Functional\Fleet\Exceptions\RefusalException;
use Throwable;

trait AssertsRefusals
{
    /**
     * @param  class-string<RefusalException>  $refusalClass
     */
    protected function assertRefused(string $refusalClass, string $expectedUserText, callable $action): RefusalException
    {
        $refusal = rescue($action, fn (Throwable $exception): Throwable => $exception, report: false);

        $this->assertInstanceOf($refusalClass, $refusal);
        $this->assertStringContainsString($expectedUserText, $refusal->userMessage());
        $this->assertMatchesRegularExpression('/^[\x20-\x7E]+$/', $refusal->getMessage(), 'the technical message is plain English');

        return $refusal;
    }
}

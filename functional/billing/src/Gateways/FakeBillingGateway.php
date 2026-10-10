<?php

namespace Functional\Billing\Gateways;

use Functional\Billing\Contracts\BillingGateway;
use Functional\Billing\Enums\FakeGatewayMode;
use Functional\Billing\Exceptions\BillingSoftwareRejectedException;
use Functional\Billing\Exceptions\BillingSoftwareUnreachableException;
use Functional\Billing\ValueObjects\BillableLine;
use Illuminate\Support\Facades\Cache;

final class FakeBillingGateway implements BillingGateway
{
    private const MODE_KEY = 'billing.fake_gateway.mode';

    private const REJECTIONS_KEY = 'billing.fake_gateway.rejections';

    private const RECEIVED_KEY = 'billing.fake_gateway.received';

    private const REFERENCES_KEY = 'billing.fake_gateway.references';

    public function send(BillableLine $line): string
    {
        if ($this->mode() === FakeGatewayMode::Unreachable) {
            throw BillingSoftwareUnreachableException::make();
        }

        $rejectionReason = $this->rejections()[$line->idempotencyKey] ?? null;

        if ($rejectionReason !== null) {
            throw BillingSoftwareRejectedException::because($rejectionReason);
        }

        $references = $this->references();
        $references[$line->idempotencyKey] ??= 'FAKE-'.(count($references) + 1);
        Cache::forever(self::REFERENCES_KEY, $references);
        Cache::forever(self::RECEIVED_KEY, array_replace($this->received(), [$line->idempotencyKey => $line->toArray()]));

        return $references[$line->idempotencyKey];
    }

    public function switchTo(FakeGatewayMode $mode): void
    {
        Cache::forever(self::MODE_KEY, $mode->value);
    }

    public function mode(): FakeGatewayMode
    {
        return FakeGatewayMode::from(Cache::get(self::MODE_KEY, FakeGatewayMode::Accept->value));
    }

    public function reject(string $idempotencyKey, string $reason): void
    {
        Cache::forever(self::REJECTIONS_KEY, array_replace($this->rejections(), [$idempotencyKey => $reason]));
    }

    /**
     * @return array<string, array<string, string|int|null>>
     */
    public function received(): array
    {
        return Cache::get(self::RECEIVED_KEY, []);
    }

    public function reset(): void
    {
        Cache::forget(self::MODE_KEY);
        Cache::forget(self::REJECTIONS_KEY);
        Cache::forget(self::RECEIVED_KEY);
        Cache::forget(self::REFERENCES_KEY);
    }

    /**
     * @return array<string, string>
     */
    private function rejections(): array
    {
        return Cache::get(self::REJECTIONS_KEY, []);
    }

    /**
     * @return array<string, string>
     */
    private function references(): array
    {
        return Cache::get(self::REFERENCES_KEY, []);
    }
}

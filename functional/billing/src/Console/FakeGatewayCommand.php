<?php

namespace Functional\Billing\Console;

use Functional\Billing\Enums\FakeGatewayMode;
use Functional\Billing\Gateways\FakeBillingGateway;
use Illuminate\Console\Command;

final class FakeGatewayCommand extends Command
{
    protected $signature = 'billing:fake-gateway {mode? : accept or unreachable} {--received : list the idempotency keys received}';

    protected $description = 'Switch the fake billing software mode or list the lines it received (local and testing only)';

    public function handle(FakeBillingGateway $fakeBillingGateway): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('The fake billing software is only available in the local and testing environments.');

            return self::FAILURE;
        }

        $requestedMode = $this->argument('mode');

        if (is_string($requestedMode)) {
            $fakeBillingGateway->switchTo(FakeGatewayMode::from($requestedMode));
        }

        $this->info("Fake billing software mode: {$fakeBillingGateway->mode()->value}.");

        if ($this->option('received')) {
            $this->table(['idempotency_key', 'type', 'reservation_ref'], array_map(
                fn (array $receivedLine): array => [$receivedLine['idempotency_key'], $receivedLine['type'], $receivedLine['reservation_ref']],
                array_values($fakeBillingGateway->received()),
            ));
        }

        return self::SUCCESS;
    }
}

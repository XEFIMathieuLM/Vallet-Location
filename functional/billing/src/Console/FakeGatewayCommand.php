<?php

namespace Functional\Billing\Console;

use Functional\Billing\Enums\FakeGatewayMode;
use Functional\Billing\Gateways\FakeBillingGateway;
use Illuminate\Console\Command;

final class FakeGatewayCommand extends Command
{
    protected $signature = 'billing:fake-gateway {mode? : accept ou unreachable} {--received : liste les clés reçues}';

    protected $description = 'Switch the fake billing software mode or list the lines it received (local and testing only)';

    public function handle(FakeBillingGateway $fakeBillingGateway): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error(__('billing::fake_gateway.refused'));

            return self::FAILURE;
        }

        $requestedMode = $this->argument('mode');

        if (is_string($requestedMode)) {
            $fakeBillingGateway->switchTo(FakeGatewayMode::from($requestedMode));
        }

        $this->info(__('billing::fake_gateway.mode', ['mode' => $fakeBillingGateway->mode()->value]));

        if ($this->option('received')) {
            $this->table(['idempotency_key', 'type', 'reservation_ref'], array_map(
                fn (array $receivedLine): array => [$receivedLine['idempotency_key'], $receivedLine['type'], $receivedLine['reservation_ref']],
                array_values($fakeBillingGateway->received()),
            ));
        }

        return self::SUCCESS;
    }
}

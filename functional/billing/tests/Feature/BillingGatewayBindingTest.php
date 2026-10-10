<?php

namespace Functional\Billing\Tests\Feature;

use Functional\Billing\Actions\SendTransmission;
use Functional\Billing\Contracts\BillingGateway;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Exceptions\FakeBillingGatewayNotAllowedException;
use Functional\Billing\Exceptions\UnknownBillingGatewayException;
use Functional\Billing\Gateways\FakeBillingGateway;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillingGatewayBindingTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    /**
     * @return iterable<string, array{string}>
     */
    public static function deployedEnvironments(): iterable
    {
        yield 'production' => ['production'];
        yield 'staging' => ['staging'];
    }

    #[DataProvider('deployedEnvironments')]
    public function test_the_fake_gateway_cannot_be_bound_outside_local_and_testing(string $environment): void
    {
        $reservation = $this->closedReservation('2026-11-10 08:00:00', '2026-11-14 17:00:00');
        $period = BillablePeriod::factory()->create(['reservation_id' => $reservation->id, 'kind' => BillablePeriodKind::Final]);
        $transmission = Transmission::factory()->create(['billable_period_id' => $period->id, 'reservation_id' => $reservation->id]);
        $this->app['env'] = $environment;

        $this->assertThrows(fn () => app(BillingGateway::class), FakeBillingGatewayNotAllowedException::class);
        $this->assertThrows(fn () => app(SendTransmission::class)->handle($transmission), FakeBillingGatewayNotAllowedException::class);
        $this->assertSame(TransmissionStatus::Pending, $transmission->refresh()->status);
        $this->assertSame([], $this->fakeGateway()->received());
    }

    public function test_the_fake_gateway_is_bound_in_local_and_testing(): void
    {
        foreach (['local', 'testing'] as $environment) {
            $this->app['env'] = $environment;

            $this->assertInstanceOf(FakeBillingGateway::class, app(BillingGateway::class));
        }
    }

    public function test_a_missing_gateway_configuration_is_refused(): void
    {
        config(['billing.gateway' => null]);

        $this->assertThrows(fn () => app(BillingGateway::class), UnknownBillingGatewayException::class);
    }
}

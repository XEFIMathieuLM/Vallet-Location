<?php

namespace Functional\Billing\Tests\Feature;

use Functional\Billing\Enums\FakeGatewayMode;
use Functional\Billing\Gateways\FakeBillingGateway;
use Functional\Billing\Lines\RentalContext;
use Functional\Billing\Lines\RentalPeriodLine;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Tests\Concerns\BuildsBillingFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeGatewayCommandTest extends TestCase
{
    use BuildsBillingFixtures, RefreshDatabase;

    public function test_the_command_switches_the_fake_software_mode(): void
    {
        $this->artisan('billing:fake-gateway', ['mode' => 'unreachable'])
            ->expectsOutputToContain('Fake billing software mode: unreachable.')
            ->assertSuccessful();

        $this->assertSame(FakeGatewayMode::Unreachable, app(FakeBillingGateway::class)->mode());
    }

    public function test_the_command_lists_the_received_idempotency_keys(): void
    {
        $transmission = Transmission::factory()->create();
        $transmission->load('billablePeriod');
        app(FakeBillingGateway::class)->send(RentalPeriodLine::fromPeriod(RentalContext::fromTransmission($transmission, 'CLI-1'), $transmission->billablePeriod));

        $this->artisan('billing:fake-gateway', ['--received' => true])
            ->expectsOutputToContain($transmission->uuid)
            ->assertSuccessful();
    }

    public function test_the_command_refuses_to_run_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('billing:fake-gateway', ['mode' => 'unreachable'])
            ->expectsOutputToContain('only available in the local and testing environments')
            ->assertFailed();

        $this->assertSame(FakeGatewayMode::Accept, app(FakeBillingGateway::class)->mode());
    }
}

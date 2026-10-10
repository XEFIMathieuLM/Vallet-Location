<?php

namespace Functional\Billing\Tests\Feature;

use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Functional\Billing\Enums\BillablePeriodKind;
use Functional\Billing\Enums\DamageOutcome;
use Functional\Billing\Enums\TransmissionFailureReason;
use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Models\BillablePeriod;
use Functional\Billing\Models\BillingExport;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Billing\Models\DamageSettlement;
use Functional\Billing\Models\Transmission;
use Functional\Booking\Models\Customer;
use Functional\Inspection\Models\Damage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_every_transmission_status_and_failure_reason_is_seeded(): void
    {
        foreach (TransmissionStatus::cases() as $status) {
            $this->assertTrue(Transmission::query()->where('status', $status)->exists(), "Missing seeded transmission in status {$status->value}.");
        }

        foreach (TransmissionFailureReason::cases() as $failureReason) {
            $this->assertTrue(Transmission::query()->where('failure_reason', $failureReason)->exists(), "Missing seeded failure {$failureReason->value}.");
        }

        $this->assertTrue(BillingExport::query()->exists());
    }

    public function test_every_period_kind_and_damage_outcome_is_seeded(): void
    {
        foreach (BillablePeriodKind::cases() as $kind) {
            $this->assertTrue(BillablePeriod::query()->where('kind', $kind)->exists(), "Missing seeded period of kind {$kind->value}.");
        }

        foreach (DamageOutcome::cases() as $outcome) {
            $this->assertTrue(DamageSettlement::query()->where('outcome', $outcome)->exists(), "Missing seeded settlement {$outcome->value}.");
        }
    }

    public function test_customers_with_and_without_billing_reference_and_overdue_damages_are_seeded(): void
    {
        $this->assertTrue(CustomerBillingAccount::query()->exists());
        $this->assertTrue(Customer::query()->whereNotIn('id', CustomerBillingAccount::query()->select('customer_id'))->exists());
        $this->assertTrue(Damage::query()->whereNull('resolved_at')->where('reported_at', '<', CarbonImmutable::now()->subDays(config()->integer('billing.damage_overdue_days')))->exists());
        $this->assertTrue(Transmission::query()->where('status', TransmissionStatus::Pending)->where('created_at', '<', CarbonImmutable::now()->subDay())->exists());
    }
}

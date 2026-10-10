<?php

namespace Functional\Certification\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Events\VgpReportDeposited;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReadinessQueryCountTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
        Queue::fake();
    }

    public function test_a_customer_email_change_reads_the_reports_in_force_once_whatever_the_number_of_certificates(): void
    {
        $customer = Customer::factory()->create(['email' => 'chantier@exemple.fr']);
        foreach (range(1, 4) as $position) {
            $machine = $this->machineWithReport();
            ReservationCertificate::factory()->for(Reservation::factory()->for($machine)->for($customer))->withStatus(CertificateStatus::AwaitingEmail)->create();
        }

        $reportReads = $this->reportReadsDuring(fn () => event(new CustomerChanged($customer, ['email'])));

        $this->assertSame(1, $reportReads);
        $this->assertSame(4, ReservationCertificate::query()->where('status', CertificateStatus::Pending)->count());
    }

    public function test_a_deposited_report_resolves_the_waiting_certificates_without_reading_reports_again(): void
    {
        $machine = Machine::factory()->vgpValid()->create();
        foreach (range(1, 3) as $weeks) {
            $startDate = CarbonImmutable::today()->addWeeks($weeks);
            ReservationCertificate::factory()->for(Reservation::factory()->for($machine)->for($this->customerWithEmail())->between($startDate, $startDate->addDays(2)))->withStatus(CertificateStatus::AwaitingReport)->create();
        }
        $report = VgpReport::factory()->for($machine)->create();

        $reportReads = $this->reportReadsDuring(fn () => event(new VgpReportDeposited($report)));

        $this->assertSame(0, $reportReads);
        $this->assertSame(3, ReservationCertificate::query()->where('status', CertificateStatus::Pending)->count());
    }

    public function test_the_catch_up_reads_the_reports_in_force_once_per_batch(): void
    {
        foreach (range(1, 5) as $position) {
            Reservation::factory()->for($this->machineWithReport())->for($this->customerWithEmail())->create();
        }

        $reportReads = $this->reportReadsDuring(fn () => $this->artisan('certification:reconcile')->assertSuccessful());

        $this->assertSame(1, $reportReads);
        $this->assertSame(5, ReservationCertificate::query()->where('status', CertificateStatus::Pending)->count());
    }

    private function reportReadsDuring(callable $action): int
    {
        $reportReads = 0;
        DB::listen(function (QueryExecuted $query) use (&$reportReads): void {
            if (str_starts_with(ltrim($query->sql), 'select') && str_contains($query->sql, '"vgp_reports"')) {
                $reportReads++;
            }
        });

        $action();

        return $reportReads;
    }
}

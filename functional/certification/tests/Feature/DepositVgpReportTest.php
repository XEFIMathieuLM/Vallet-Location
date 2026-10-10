<?php

namespace Functional\Certification\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Customer;
use Functional\Certification\Actions\DepositVgpReport;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Exceptions\InvalidVgpReportException;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Notifications\VgpCertificateNotification;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Events\MachineChanged;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DepositVgpReportTest extends TestCase
{
    use AssertsRefusals, BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
    }

    public function test_the_deposited_report_is_in_force_and_carries_the_machine_due_date(): void
    {
        Event::fake([MachineChanged::class]);
        $machine = Machine::factory()->vgpValid()->create();

        $report = $this->deposit($machine, UploadedFile::fake()->create('rapport-apave.pdf', 300, 'application/pdf'), '2026-10-02', '2027-04-01');

        Storage::disk('vgp-reports')->assertExists($report->file_path);
        $this->assertSame('rapport-apave.pdf', $report->original_name);
        $this->assertSame('2027-04-01', $machine->fresh()?->vgp_due_date?->toDateString());
        Event::assertDispatched(MachineChanged::class);
        $activity = Activity::query()->inLog('certification')->whereMorphedTo('subject', $machine)->sole();
        $this->assertSame('vgp_report_deposited', $activity->event);
        $this->assertStringContainsString('01/04/2027', $activity->description);
    }

    public function test_a_reservation_without_report_waits_for_it(): void
    {
        Notification::fake();

        $reservation = $this->reserve(Machine::factory()->vgpValid()->create(), $this->customerWithEmail());

        $this->assertSame(CertificateStatus::AwaitingReport, $this->certificateOf($reservation)->status);
        Notification::assertNothingSent();
    }

    public function test_depositing_the_report_sends_the_waiting_certificates(): void
    {
        Notification::fake();
        $machine = Machine::factory()->vgpValid()->create();
        $reservation = $this->reserve($machine, $this->customerWithEmail());
        $withoutEmail = $this->reserve($machine, Customer::factory()->create(['email' => null]), startInDays: 12);

        $this->deposit($machine, UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf'), '2026-10-02', '2027-04-01');

        $this->assertSame(CertificateStatus::Sent, $this->certificateOf($reservation)->status);
        $this->assertSame(CertificateStatus::AwaitingEmail, $this->certificateOf($withoutEmail)->status);
        Notification::assertSentOnDemandTimes(VgpCertificateNotification::class, 1);
    }

    public function test_invalid_deposits_are_refused(): void
    {
        $machine = Machine::factory()->vgpValid()->create();
        $pdf = UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf');

        $this->assertRefused(InvalidVgpReportException::class, 'format', fn () => $this->deposit($machine, UploadedFile::fake()->create('rapport.docx', 100), '2026-10-02', '2027-04-01'));
        $this->assertRefused(InvalidVgpReportException::class, '10 Mo', fn () => $this->deposit($machine, UploadedFile::fake()->create('rapport.pdf', 10241, 'application/pdf'), '2026-10-02', '2027-04-01'));
        $this->assertRefused(InvalidVgpReportException::class, 'échéance', fn () => $this->deposit($machine, $pdf, '2026-10-02', '2026-10-02'));
        $this->assertRefused(InvalidVgpReportException::class, 'pas soumise', fn () => $this->deposit(Machine::factory()->create(['is_subject_to_vgp' => false]), $pdf, '2026-10-02', '2027-04-01'));
        $this->assertSame(0, VgpReport::query()->count());
    }

    public function test_a_new_report_takes_over_and_the_previous_one_is_kept(): void
    {
        $machine = Machine::factory()->vgpValid()->create();
        $previousReport = $this->deposit($machine, UploadedFile::fake()->create('ancien.pdf', 100, 'application/pdf'), '2026-04-02', '2026-10-01');

        $newReport = $this->deposit($machine, UploadedFile::fake()->create('nouveau.jpg', 100, 'image/jpeg'), '2026-10-02', '2027-04-01');

        $this->assertNotNull($previousReport->fresh());
        $this->assertSame([$newReport->id, $previousReport->id], VgpReport::query()->whereBelongsTo($machine)->latest('id')->pluck('id')->all());
        $this->assertSame('2027-04-01', $machine->fresh()?->vgp_due_date?->toDateString());
    }

    private function deposit(Machine $machine, UploadedFile $file, string $verifiedOn, string $dueOn): VgpReport
    {
        return app(DepositVgpReport::class)->handle($this->employee, $machine, $file, CarbonImmutable::parse($verifiedOn), CarbonImmutable::parse($dueOn));
    }
}

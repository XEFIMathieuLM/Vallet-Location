<?php

namespace Functional\Certification\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Certification\Enums\CertificationPermission;
use Functional\Certification\Livewire\MachineVgpReports;
use Functional\Certification\Livewire\VgpMachines;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class VgpReportScreensTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
    }

    public function test_the_list_shows_each_vgp_machine_with_its_report_in_force_or_its_absence(): void
    {
        $rouen = Agency::factory()->create(['name' => 'Agence de Rouen']);
        $withReport = Machine::factory()->for($rouen)->vgpValid()->create(['reference' => 'NAC-0001']);
        VgpReport::factory()->for($withReport)->create(['verified_on' => CarbonImmutable::parse('2026-10-02'), 'due_on' => CarbonImmutable::parse('2027-04-01')]);
        Machine::factory()->vgpValid()->create(['reference' => 'NAC-0002']);
        Machine::factory()->create(['reference' => 'MP-0003', 'is_subject_to_vgp' => false]);

        $this->get(route('certification.machines'))->assertOk()->assertSee('NAC-0001')->assertSee('01/04/2027')->assertSee('NAC-0002')->assertSee('Aucun rapport')->assertDontSee('MP-0003');

        Livewire::test(VgpMachines::class)->set('isWithoutReportOnly', true)->assertSee('NAC-0002')->assertDontSee('NAC-0001');
        Livewire::test(VgpMachines::class)->set('agencyId', $rouen->id)->assertSee('NAC-0001')->assertDontSee('NAC-0002');
    }

    public function test_the_list_runs_a_constant_number_of_queries(): void
    {
        Machine::factory()->vgpValid()->count(5)->create()->each(fn (Machine $machine) => VgpReport::factory()->for($machine)->create());
        $fewMachinesQueries = $this->queriesToRender();

        Machine::factory()->vgpValid()->count(45)->create()->each(fn (Machine $machine) => VgpReport::factory()->for($machine)->create());

        $this->assertSame($fewMachinesQueries, $this->queriesToRender());
    }

    public function test_the_machine_page_deposits_a_report_and_lists_the_previous_ones(): void
    {
        $machine = Machine::factory()->vgpValid()->create(['reference' => 'NAC-0042']);
        VgpReport::factory()->for($machine)->create(['original_name' => 'ancien-rapport.pdf']);

        Livewire::test(MachineVgpReports::class, ['machine' => $machine])
            ->assertSee('ancien-rapport.pdf')
            ->set('reportFile', UploadedFile::fake()->create('nouveau-rapport.pdf', 200, 'application/pdf'))
            ->set('verifiedOn', '2026-10-02')
            ->set('dueOn', '2027-04-01')
            ->call('deposit')
            ->assertHasNoErrors()
            ->assertSee('nouveau-rapport.pdf')
            ->assertSee('ancien-rapport.pdf');

        $this->assertSame('2027-04-01', $machine->fresh()?->vgp_due_date?->toDateString());
    }

    public function test_the_machine_page_shows_refusals(): void
    {
        $machine = Machine::factory()->vgpValid()->create();

        Livewire::test(MachineVgpReports::class, ['machine' => $machine])
            ->set('reportFile', UploadedFile::fake()->create('rapport.pdf', 200, 'application/pdf'))
            ->set('verifiedOn', '2026-10-02')
            ->set('dueOn', '2026-09-01')
            ->call('deposit')
            ->assertHasErrors('refusal');

        $this->assertSame(0, VgpReport::query()->count());
    }

    public function test_reports_are_downloaded_only_with_the_permission(): void
    {
        $report = VgpReport::factory()->create();

        $this->get(route('certification.reports.file', $report))->assertOk()->assertDownload();

        $this->actingAs($this->userWithoutPermission());
        $this->get(route('certification.reports.file', $report))->assertForbidden();
        $this->get(route('certification.machines'))->assertForbidden();

        $this->actingAs($this->userWithPermissions(CertificationPermission::Manage));
        $this->get(route('certification.machines'))->assertOk();
    }

    private function queriesToRender(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(VgpMachines::class);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }
}

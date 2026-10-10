<?php

namespace Functional\Fleet\Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Functional\Fleet\Actions\ImportFleet;
use Functional\Fleet\Data\FleetImportReport;
use Functional\Fleet\Events\FleetImported;
use Functional\Fleet\Livewire\ImportFleetForm;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class ImportFleetTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE = __DIR__.'/../Fixtures/fleet-with-duplicates.csv';

    private Machine $existingMachine;

    protected function setUp(): void
    {
        parent::setUp();

        $nacelle = MachineCategory::factory()->requiringVgp()->create(['name' => 'Nacelle']);
        MachineCategory::factory()->create(['name' => 'Mini-pelle']);
        Agency::factory()->create(['name' => 'Annecy']);
        Agency::factory()->create(['name' => 'Lyon']);
        $this->existingMachine = Machine::factory()->for($nacelle, 'category')->create(['reference' => 'NAC-0001', 'is_subject_to_vgp' => true]);
    }

    public function test_valid_lines_are_created_and_every_rejection_is_reported_with_its_reason(): void
    {
        Event::fake([FleetImported::class]);

        $report = app(ImportFleet::class)->handle(self::FIXTURE);

        $this->assertSame(3, $report->createdCount);
        $this->assertSame([
            ['line' => 4, 'reference' => 'NAC-0101', 'reason' => __('fleet::machines.import.rejections.duplicate_in_file')],
            ['line' => 5, 'reference' => '', 'reason' => __('fleet::machines.import.rejections.missing_reference')],
            ['line' => 6, 'reference' => 'NAC-0001', 'reason' => __('fleet::machines.import.rejections.existing_reference')],
            ['line' => 7, 'reference' => 'CMP-0101', 'reason' => __('fleet::machines.import.rejections.unknown_category', ['category' => 'Bulldozer'])],
            ['line' => 8, 'reference' => 'CMP-0102', 'reason' => __('fleet::machines.import.rejections.unknown_agency', ['agency' => 'Paris'])],
            ['line' => 9, 'reference' => 'CMP-0103', 'reason' => __('fleet::machines.import.rejections.unreadable_date', ['date' => '31/02/2027'])],
        ], $report->rejections);
        Event::assertDispatched(FleetImported::class, fn (FleetImported $event): bool => $event->createdCount === 3);
    }

    public function test_imported_machines_follow_the_category_vgp_rule_and_the_file_values(): void
    {
        app(ImportFleet::class)->handle(self::FIXTURE);

        $nacelle = Machine::query()->where('reference', 'NAC-0101')->firstOrFail();
        $this->assertTrue($nacelle->is_subject_to_vgp);
        $this->assertSame('2027-11-30', $nacelle->vgp_due_date?->toDateString());
        $this->assertSame('Annecy', $nacelle->agency->name);

        $this->assertFalse(Machine::query()->where('reference', 'MPE-0101')->firstOrFail()->is_subject_to_vgp);

        $trimmedMachine = Machine::query()->where('reference', 'MPE-0102')->firstOrFail();
        $this->assertTrue($trimmedMachine->is_subject_to_vgp);
        $this->assertSame('2028-01-15', $trimmedMachine->vgp_due_date?->toDateString());
    }

    public function test_an_import_never_modifies_an_existing_machine(): void
    {
        $before = $this->existingMachine->fresh()?->toArray();

        app(ImportFleet::class)->handle(self::FIXTURE);

        $this->assertSame($before, $this->existingMachine->fresh()?->toArray());
    }

    public function test_the_import_screen_shows_the_report(): void
    {
        $this->seed(PermissionSeeder::class);
        $file = UploadedFile::fake()->createWithContent('parc.csv', (string) file_get_contents(self::FIXTURE));

        Livewire::actingAs(User::factory()->employee()->create())
            ->test(ImportFleetForm::class)
            ->set('fleetFile', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSee(trans_choice('fleet::machines.import.created', 3))
            ->assertSee(__('fleet::machines.import.rejections.duplicate_in_file'));
    }

    public function test_the_report_is_a_plain_value(): void
    {
        $report = new FleetImportReport(0, []);

        $this->assertFalse($report->hasRejections());
    }
}

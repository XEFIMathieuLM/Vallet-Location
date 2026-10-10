<?php

namespace Functional\Portal\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Models\CertificateDispatch;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Models\CategoryIndicativePrice;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalQueryCountTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    private MachineCategory $category;

    private Agency $agency;

    private CustomerAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        Storage::fake('vgp-reports');
        $this->seedPermissions();
        $this->category = MachineCategory::factory()->create();
        $this->agency = Agency::factory()->create();
        CategoryIndicativePrice::factory()->for($this->category, 'category')->create();
        $this->account = $this->attachedCustomerAccount(Customer::factory()->create());
    }

    public function test_the_number_of_queries_of_each_screen_does_not_grow_with_the_number_of_items(): void
    {
        $this->addItems(2);
        $fewItemsQueries = $this->queriesOfEveryScreen();

        $this->addItems(18);

        $this->assertSame($fewItemsQueries, $this->queriesOfEveryScreen());
    }

    private function addItems(int $count): void
    {
        foreach (range(1, $count) as $item) {
            $machine = Machine::factory()->vgpValid()->for($this->category, 'category')->for($this->agency)->create();
            ReservationRequest::factory()->for($this->account, 'account')->for($machine)->create();
            $reservation = Reservation::factory()->for($this->account->customer)->for($machine)->create();
            $certificate = ReservationCertificate::factory()->for($reservation)->withStatus(CertificateStatus::Sent)->create();
            CertificateDispatch::factory()->for($certificate, 'certificate')->for(VgpReport::factory()->for($machine), 'report')->create();
        }
    }

    /**
     * @return array<string, int>
     */
    private function queriesOfEveryScreen(): array
    {
        $handler = $this->requestHandler();

        return [
            'recherche' => $this->countQueries(fn () => $this->actingAs($this->account, 'customer')->get(route('portal.search', ['categorie' => $this->category->id, 'agence' => $this->agency->id, 'du' => '2026-12-01', 'au' => '2026-12-02']))->assertOk()),
            'demandes' => $this->countQueries(fn () => $this->actingAs($this->account, 'customer')->get(route('portal.requests'))->assertOk()),
            'réservations' => $this->countQueries(fn () => $this->actingAs($this->account, 'customer')->get(route('portal.reservations'))->assertOk()),
            'demandes en ligne' => $this->countQueries(fn () => $this->actingAs($handler)->get(route('portal.staff.requests'))->assertOk()),
        ];
    }

    private function countQueries(callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }
}

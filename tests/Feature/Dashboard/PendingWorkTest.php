<?php

namespace Tests\Feature\Dashboard;

use App\Dashboard\PendingWorkCounter;
use App\Livewire\Dashboard\PendingWork;
use App\Models\User;
use Carbon\CarbonImmutable;
use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Queries\MissingPurchaseOrders;
use Functional\Billing\Enums\BillingPermission;
use Functional\Billing\Models\Transmission;
use Functional\Billing\Queries\TransmissionsToHandle;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Queries\CertificatesToHandle;
use Functional\Deposit\Access\DepositPermission;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Queries\PendingDeposits;
use Functional\Fleet\Models\Agency;
use Functional\Inspection\Models\Damage;
use Functional\Inspection\Queries\DamagesToHandle;
use Functional\Sales\Models\Sale;
use Functional\Sales\Queries\OverdueSales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Feature\Dashboard\Concerns\BuildsDashboardFixtures;
use Tests\TestCase;

class PendingWorkTest extends TestCase
{
    use BuildsDashboardFixtures, RefreshDatabase;

    private Agency $rouen;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00'));
        $this->rouen = $this->agencyNamed('Rouen');
        $this->employee = $this->employeeOf($this->rouen);
    }

    public function test_each_counter_shows_the_number_of_items_of_its_list(): void
    {
        $this->seedOneItemPerList();

        $this->assertSame([
            'transmissions' => 1,
            'certificates' => 2,
            'deposits' => 1,
            'purchase_orders' => 1,
            'damages' => 1,
            'overdue_sales' => 1,
        ], $this->counts($this->pendingWork()));
        $this->assertSame([
            'transmissions' => app(TransmissionsToHandle::class)->query()->count(),
            'certificates' => app(CertificatesToHandle::class)->query()->count(),
            'deposits' => app(PendingDeposits::class)->query()->count(),
            'purchase_orders' => app(MissingPurchaseOrders::class)->query()->count(),
            'damages' => app(DamagesToHandle::class)->query()->count(),
            'overdue_sales' => app(OverdueSales::class)->query(CarbonImmutable::today())->count(),
        ], $this->counts($this->pendingWork()));
    }

    public function test_each_counter_links_to_its_list(): void
    {
        $urls = collect($this->pendingWork()->viewData('counters'))->mapWithKeys(fn (PendingWorkCounter $counter): array => [$counter->key => $counter->url])->all();

        $this->assertSame([
            'transmissions' => route('billing.transmissions'),
            'certificates' => route('certification.certificates'),
            'deposits' => route('deposit.pending.index'),
            'purchase_orders' => route('accounts.missing-purchase-orders'),
            'damages' => route('inspection.damages'),
            'overdue_sales' => route('sales.index', ['statut' => 'reserved']),
        ], $urls);
        $this->pendingWork()->assertSeeHtml('href="'.route('deposit.pending.index').'"');
    }

    public function test_a_zero_counter_is_neutral_and_a_non_zero_counter_stands_out(): void
    {
        Deposit::factory()->withStatus(DepositStatus::ToRefund)->create();

        $this->pendingWork()
            ->assertSeeHtml('data-counter="deposits" data-highlighted="true"')
            ->assertSeeHtml('data-counter="damages" data-highlighted="false"');
    }

    public function test_the_certificates_counter_drops_on_refresh_once_the_certificate_is_sent(): void
    {
        $certificate = ReservationCertificate::factory()->withStatus(CertificateStatus::AwaitingEmail)->create();
        $pendingWork = $this->pendingWork();
        $this->assertSame(1, $this->counts($pendingWork)['certificates']);

        $certificate->update(['status' => CertificateStatus::Sent, 'delivered_at' => CarbonImmutable::now()]);
        $pendingWork->dispatch('echo-private:fleet,.certificate.changed');

        $this->assertSame(0, $this->counts($pendingWork)['certificates']);
    }

    public function test_a_counter_is_hidden_and_not_computed_without_the_permission_of_its_list(): void
    {
        Transmission::factory()->failed()->create();
        $member = $this->memberOf($this->rouen, DepositPermission::ManageDeposits);

        DB::enableQueryLog();
        $pendingWork = Livewire::actingAs($member)->test(PendingWork::class);

        $this->assertSame(['deposits'], array_keys($this->counts($pendingWork)));
        $this->assertEmpty(array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], '"transmissions"')));
        $pendingWork->assertDontSee(__('dashboard.pending.transmissions'));
    }

    public function test_counters_cover_the_whole_network(): void
    {
        $evreuxMachine = $this->machineIn($this->agencyNamed('Évreux'));
        Deposit::factory()->for(Reservation::factory()->for($evreuxMachine))->withStatus(DepositStatus::ToRefund)->create();

        $this->assertSame(1, $this->counts($this->pendingWork())['deposits']);
    }

    public function test_sale_changes_are_listened_to_only_with_the_sales_permission(): void
    {
        $this->assertContains('echo-private:sales,.sale.changed', array_keys($this->pendingWork()->instance()->getListeners()));

        $member = $this->memberOf($this->rouen, BillingPermission::Manage);
        $listeners = array_keys(Livewire::actingAs($member)->test(PendingWork::class)->instance()->getListeners());

        $this->assertNotContains('echo-private:sales,.sale.changed', $listeners);
        $this->assertContains('echo-private:fleet,.certificate.changed', $listeners);
    }

    private function seedOneItemPerList(): void
    {
        Transmission::factory()->failed()->create();
        ReservationCertificate::factory()->withStatus(CertificateStatus::AwaitingEmail)->count(2)->create();
        Deposit::factory()->withStatus(DepositStatus::ToRefund)->create();
        Reservation::factory()->for(KeyAccount::factory()->create()->customer)->create();
        Damage::factory()->create();
        Sale::factory()->reserved(CarbonImmutable::today()->subDay())->create();
    }

    private function pendingWork(): Testable
    {
        return Livewire::actingAs($this->employee)->test(PendingWork::class);
    }

    /**
     * @return array<string, int>
     */
    private function counts(Testable $pendingWork): array
    {
        return collect($pendingWork->viewData('counters'))->mapWithKeys(fn (PendingWorkCounter $counter): array => [$counter->key => $counter->count])->all();
    }
}

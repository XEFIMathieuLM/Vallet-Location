<?php

namespace Functional\Portal\Tests\Feature\Staff;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Models\Agency;
use Functional\Portal\Livewire\Staff\OnlineRequests;
use Functional\Portal\Livewire\Staff\PendingRequestsBadge;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineRequestsListTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        $this->seedPermissions();
    }

    public function test_the_list_shows_only_pending_requests_sorted_by_start_date_with_what_the_agency_needs(): void
    {
        $rouen = Agency::factory()->create(['name' => 'Rouen']);
        $newAccount = $this->customerAccount(['name' => 'Terrassements Martin', 'email' => 'contact@martin.fr', 'phone' => '02 35 11 22 33']);
        $attachedAccount = $this->attachedCustomerAccount(Customer::factory()->create(['name' => 'Fiche Lefebvre BTP']));
        $later = $this->pendingRequest($newAccount, $this->reservableMachine(agency: $rouen, attributes: ['reference' => 'NAC-0002']), '2026-11-20', '2026-11-22', ['comment' => 'Chantier Rouen', 'indicative_daily_price_cents' => 9500]);
        $sooner = $this->pendingRequest($attachedAccount, $this->reservableMachine(agency: $rouen, attributes: ['reference' => 'NAC-0001']), '2026-11-12', '2026-11-13');
        ReservationRequest::factory()->refused()->create();
        $this->actingAs($this->requestHandler());

        $this->get(route('portal.staff.requests'))->assertOk();
        Livewire::test(OnlineRequests::class)
            ->assertSeeInOrder(['NAC-0001', 'NAC-0002'])
            ->assertSee(['Terrassements Martin', 'contact@martin.fr', '02 35 11 22 33', __('portal::staff.requests.unattached')])
            ->assertSee(['Fiche Lefebvre BTP', 'Chantier Rouen', 'Rouen', '95,00', '12/11/2026'])
            ->assertViewHas('reservationRequests', fn ($reservationRequests): bool => $reservationRequests->pluck('id')->all() === [$sooner->id, $later->id]);
    }

    public function test_the_list_filters_on_the_home_agency_of_the_machine(): void
    {
        $rouen = Agency::factory()->create();
        $inRouen = $this->pendingRequest($this->customerAccount(), $this->reservableMachine(agency: $rouen), '2026-11-12', '2026-11-13');
        $this->pendingRequest($this->customerAccount(), $this->reservableMachine(), '2026-11-12', '2026-11-13');
        $this->actingAs($this->requestHandler());

        Livewire::test(OnlineRequests::class)
            ->set('agencyId', $rouen->id)
            ->assertViewHas('reservationRequests', fn ($reservationRequests): bool => $reservationRequests->pluck('id')->all() === [$inRouen->id]);
    }

    public function test_the_list_refreshes_on_request_changes(): void
    {
        $this->actingAs($this->requestHandler());

        $listeners = Livewire::test(OnlineRequests::class)->instance()->getListeners();

        $this->assertArrayHasKey('echo-private:portal-requests,.reservation-request.changed', $listeners);
        $this->assertArrayHasKey('echo-private:fleet,.reservation.changed', $listeners);
    }

    public function test_an_employee_without_the_permission_cannot_open_the_list(): void
    {
        $this->actingAs($this->userWithoutPermission());

        $this->get(route('portal.staff.requests'))->assertForbidden();
    }

    public function test_the_navigation_counts_the_pending_requests_for_employees_allowed_to_handle_them(): void
    {
        $this->pendingRequest($this->customerAccount(), $this->reservableMachine(), '2026-11-12', '2026-11-13');
        $this->pendingRequest($this->customerAccount(), $this->reservableMachine(), '2026-11-14', '2026-11-15');
        ReservationRequest::factory()->cancelled()->create();
        $this->actingAs($this->employee());

        $this->get(route('dashboard'))->assertSee(__('portal::navigation.staff.requests'))->assertSee(route('portal.staff.requests'));
        Livewire::test(PendingRequestsBadge::class)->assertViewHas('pendingCount', 2)->assertSeeHtml('data-flux-badge');

        ReservationRequest::query()->where('status', 'pending')->update(['status' => 'cancelled', 'decided_at' => now()]);
        Livewire::test(PendingRequestsBadge::class)->assertViewHas('pendingCount', 0)->assertDontSeeHtml('data-flux-badge');

        $this->actingAs($this->userWithoutPermission());
        $this->get(route('dashboard'))->assertDontSee(route('portal.staff.requests'));
    }
}

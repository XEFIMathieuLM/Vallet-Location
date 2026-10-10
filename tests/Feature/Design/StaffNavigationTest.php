<?php

namespace Tests\Feature\Design;

use Functional\Booking\Access\BookingPermission;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffNavigationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private const NAVIGATION_ROUTES = [
        'dashboard',
        'inspection.damages',
        'inspection.category-views.index',
        'availability.index',
        'reservations.index',
        'planning.index',
        'billing.transmissions',
        'billing.exports',
        'billing.statement',
        'deposit.pending.index',
        'deposit.rates.index',
        'sales.index',
        'certification.machines',
        'certification.certificates',
        'accounts.key-accounts',
        'accounts.missing-purchase-orders',
        'portal.staff.requests',
        'portal.staff.prices',
        'machines.index',
        'users.index',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
    }

    public function test_an_employee_with_every_permission_sees_every_entry_in_order(): void
    {
        $response = $this->actingAs($this->employee())->get(route('dashboard'));

        $response->assertOk()->assertSeeInOrder(array_map(
            fn (string $routeName): string => 'href="'.route($routeName).'"',
            self::NAVIGATION_ROUTES,
        ), escape: false);
    }

    public function test_the_current_entry_is_marked_in_the_black_column(): void
    {
        $html = $this->actingAs($this->employee())->get(route('reservations.index'))->getContent();

        $this->assertMatchesRegularExpression('/<a[^>]*href="'.preg_quote(route('reservations.index'), '/').'"[^>]*data-current/', (string) $html);
        $this->assertStringContainsString('data-flux-sidebar', (string) $html);
    }

    public function test_entries_stay_hidden_without_their_permission(): void
    {
        $response = $this->actingAs($this->userWithPermissions(BookingPermission::ManageReservations))->get(route('reservations.index'));

        $response->assertOk()
            ->assertDontSee('href="'.route('billing.transmissions').'"', escape: false)
            ->assertDontSee('href="'.route('billing.exports').'"', escape: false)
            ->assertDontSee('href="'.route('billing.statement').'"', escape: false);
    }

    public function test_the_navigation_carries_the_logotype_the_pending_requests_counter_and_the_personal_menu(): void
    {
        $response = $this->actingAs($this->employee())->get(route('dashboard'));

        $response->assertSee('aria-label="'.config('app.name').'"', escape: false)
            ->assertSee('data-app-logo', escape: false)
            ->assertSeeLivewire('portal.pending-requests-badge')
            ->assertSee('data-test="sidebar-menu-button"', escape: false);
    }
}

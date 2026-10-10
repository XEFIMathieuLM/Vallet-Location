<?php

namespace Functional\Portal\Tests\Feature\Auth;

use Functional\Portal\Livewire\Auth\Login;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SpaceSeparationTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    /**
     * @return iterable<string, array{string}>
     */
    public static function employeeRoutes(): iterable
    {
        yield 'tableau de bord' => ['dashboard'];
        yield 'réservations' => ['reservations.index'];
        yield 'planning' => ['planning.index'];
        yield 'parc' => ['machines.index'];
        yield 'demandes en ligne' => ['portal.staff.requests'];
        yield 'prix indicatifs' => ['portal.staff.prices'];
    }

    #[DataProvider('employeeRoutes')]
    public function test_a_customer_never_reaches_an_employee_screen(string $routeName): void
    {
        $this->seedPermissions();
        $this->signInAsCustomerOnly($this->customerAccount());

        $this->get(route($routeName))->assertRedirect(route('login'));
    }

    public function test_employee_credentials_do_not_open_the_customer_space(): void
    {
        $this->seedPermissions();
        $employee = $this->employee();

        Livewire::test(Login::class)
            ->set('email', (string) $employee->getAttribute('email'))
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertNull(Auth::guard('customer')->user());
    }

    public function test_a_logged_in_employee_is_sent_to_the_customer_login(): void
    {
        $this->seedPermissions();

        $this->actingAs($this->employee())
            ->get(route('portal.search'))
            ->assertRedirect(route('portal.login'));
    }
}

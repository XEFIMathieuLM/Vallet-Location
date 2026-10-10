<?php

namespace Tests\Feature;

use App\Livewire\Users\UserIndex;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Functional\Fleet\Models\Agency;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ManageUsersTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->manager = User::factory()->employee()->create();
    }

    public function test_an_employee_account_is_created_with_its_agency_and_role_and_receives_a_password_link(): void
    {
        Notification::fake();
        $agency = Agency::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(UserIndex::class)
            ->set('name', 'Camille Martin')
            ->set('email', 'Camille.Martin@vallet-location.test')
            ->set('agencyId', $agency->id)
            ->call('create')
            ->assertHasNoErrors();

        $employee = User::query()->where('email', 'camille.martin@vallet-location.test')->firstOrFail();
        $this->assertSame($agency->id, $employee->agency_id);
        $this->assertTrue($employee->can('reservations.manage'));
        Notification::assertSentTo($employee, ResetPassword::class);
    }

    public function test_a_duplicate_email_is_refused(): void
    {
        User::factory()->create(['email' => 'camille@vallet-location.test']);

        Livewire::actingAs($this->manager)
            ->test(UserIndex::class)
            ->set('name', 'Camille')
            ->set('email', 'camille@vallet-location.test')
            ->set('agencyId', Agency::factory()->create()->id)
            ->call('create')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_a_deactivated_account_can_no_longer_log_in(): void
    {
        $employee = User::factory()->employee()->create();

        Livewire::actingAs($this->manager)->test(UserIndex::class)->call('deactivate', $employee->id);

        $this->assertNotNull($employee->fresh()?->deactivated_at);
        $this->app['auth']->forgetGuards();
        $this->post(route('login.store'), ['email' => $employee->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_deactivated_user_already_logged_in_is_logged_out(): void
    {
        $employee = User::factory()->employee()->create(['deactivated_at' => now()]);

        $this->actingAs($employee)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_a_reactivated_account_can_log_in_again(): void
    {
        $employee = User::factory()->employee()->create(['deactivated_at' => now()]);

        Livewire::actingAs($this->manager)->test(UserIndex::class)->call('reactivate', $employee->id);
        $this->app['auth']->forgetGuards();

        $this->post(route('login.store'), ['email' => $employee->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($employee);
    }

    public function test_a_manager_cannot_deactivate_their_own_account(): void
    {
        Livewire::actingAs($this->manager)
            ->test(UserIndex::class)
            ->call('deactivate', $this->manager->id)
            ->assertHasErrors('refusal');

        $this->assertNull($this->manager->fresh()?->deactivated_at);
    }

    public function test_the_agency_of_an_employee_can_be_changed(): void
    {
        $employee = User::factory()->employee()->create();
        $agency = Agency::factory()->create();

        Livewire::actingAs($this->manager)->test(UserIndex::class)->call('changeAgency', $employee->id, $agency->id);

        $this->assertSame($agency->id, $employee->fresh()?->agency_id);
    }

    public function test_the_screen_requires_the_users_permission(): void
    {
        $this->actingAs(User::factory()->create())->get(route('users.index'))->assertForbidden();
        $this->actingAs($this->manager)->get(route('users.index'))->assertOk();
    }
}

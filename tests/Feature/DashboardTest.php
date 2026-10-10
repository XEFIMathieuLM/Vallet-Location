<?php

namespace Tests\Feature;

use App\Models\User;
use Functional\Billing\Models\Transmission;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Models\ReservationCertificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_the_existing_alert_banners_stay_on_the_dashboard(): void
    {
        $this->seedPermissions();
        Transmission::factory()->failed()->create();
        ReservationCertificate::factory()->withStatus(CertificateStatus::AwaitingEmail)->create();

        $this->actingAs(User::factory()->employee()->create())
            ->get(route('dashboard'))
            ->assertSee(trans_choice('billing::transmissions.alert.count', 1, ['count' => 1]))
            ->assertSee(trans_choice('certification::certificates.alert.count', 1, ['count' => 1]));
    }
}

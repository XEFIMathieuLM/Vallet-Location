<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_sees_the_booking_screens_in_the_navigation(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->actingAs(User::factory()->employee()->create())
            ->get(route('dashboard'))
            ->assertSee(route('availability.index'));
    }

    public function test_a_user_without_permission_does_not_see_the_booking_screens(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertDontSee(route('availability.index'));
    }
}

<?php

namespace Tests;

use Database\Seeders\PermissionSeeder;
use Functional\Booking\Database\Seeders\BookingPermissionSeeder;
use Functional\Fleet\Database\Seeders\FleetPermissionSeeder;
use Functional\Inspection\Database\Seeders\InspectionPermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function seedPermissions(): void
    {
        $this->seed([FleetPermissionSeeder::class, BookingPermissionSeeder::class, InspectionPermissionSeeder::class, PermissionSeeder::class]);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}

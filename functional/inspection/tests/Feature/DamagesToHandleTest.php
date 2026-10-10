<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Inspection\Models\Damage;
use Functional\Inspection\Queries\DamagesToHandle;
use Functional\Inspection\Queries\ReservationsToReinvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DamagesToHandleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_the_damages_listed_on_the_damages_to_handle_screen(): void
    {
        $firstDamage = Damage::factory()->create();
        Damage::factory()->create(['reservation_view_id' => $firstDamage->reservation_view_id]);
        Damage::factory()->create();
        Damage::factory()->resolved()->create();

        $listedDamages = app(ReservationsToReinvoice::class)->get()->sum(fn ($damages): int => $damages->count());

        $this->assertSame(3, app(DamagesToHandle::class)->query()->count());
        $this->assertSame($listedDamages, app(DamagesToHandle::class)->query()->count());
    }
}

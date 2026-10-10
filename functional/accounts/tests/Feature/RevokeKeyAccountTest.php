<?php

namespace Functional\Accounts\Tests\Feature;

use Functional\Accounts\Actions\RevokeKeyAccount;
use Functional\Accounts\Models\KeyAccount;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Booking\Events\CustomerChanged;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RevokeKeyAccountTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
    }

    public function test_revoking_removes_the_designation_and_keeps_entered_numbers(): void
    {
        Event::fake([CustomerChanged::class]);
        $keyAccount = KeyAccount::factory()->create();
        $reservation = Reservation::factory()->for($keyAccount->customer)->create();
        ReservationPurchaseOrder::factory()->for($reservation)->create(['number' => 'BC-2026-0412']);

        app(RevokeKeyAccount::class)->handle($this->employee(), $keyAccount->customer);

        $this->assertSame(0, KeyAccount::query()->count());
        $this->assertSame('BC-2026-0412', ReservationPurchaseOrder::query()->where('reservation_id', $reservation->id)->value('number'));
        $this->assertSame(1, Activity::query()->where('log_name', 'accounts')->where('event', 'key_account_revoked')->count());
        Event::assertDispatched(CustomerChanged::class, fn (CustomerChanged $event): bool => $event->changedAttributes === ['key_account']);
    }
}

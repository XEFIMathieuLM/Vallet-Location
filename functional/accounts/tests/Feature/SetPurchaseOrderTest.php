<?php

namespace Functional\Accounts\Tests\Feature;

use Functional\Accounts\Actions\DesignateKeyAccount;
use Functional\Accounts\Actions\SetPurchaseOrder;
use Functional\Accounts\Exceptions\InvalidPurchaseOrderNumberException;
use Functional\Accounts\Exceptions\MissingPurchaseOrderException;
use Functional\Accounts\Exceptions\PurchaseOrderFrozenException;
use Functional\Accounts\Exceptions\PurchaseOrderRefusedException;
use Functional\Accounts\Models\ReservationPurchaseOrder;
use Functional\Accounts\Tests\Concerns\BuildsKeyAccountReservations;
use Functional\Billing\Models\CustomerBillingAccount;
use Functional\Booking\Actions\DepartReservation;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SetPurchaseOrderTest extends TestCase
{
    use AssertsRefusals, BuildsKeyAccountReservations, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
        $this->onlyThePurchaseOrderGuard();
    }

    public function test_a_number_is_corrected_before_departure_with_both_numbers_in_the_history(): void
    {
        $reservation = $this->keyAccountReservation();
        app(SetPurchaseOrder::class)->handle($this->employee(), $reservation, 'BC-2026-0412');

        app(SetPurchaseOrder::class)->handle($this->employee(), $reservation, 'BC-2026-0413');

        $this->assertSame('BC-2026-0413', ReservationPurchaseOrder::query()->where('reservation_id', $reservation->id)->sole()->number);
        $correction = Activity::query()->where('log_name', 'accounts')->where('event', 'purchase_order_corrected')->sole();
        $this->assertSame('BC-2026-0412', $correction->getProperty('previous_number'));
        $this->assertSame('BC-2026-0413', $correction->getProperty('number'));
    }

    public function test_the_number_is_frozen_once_the_machine_has_left(): void
    {
        foreach ([Reservation::factory()->ongoing(), Reservation::factory()->closed()] as $reservationFactory) {
            $reservation = $reservationFactory->create();

            $this->assertRefused(PurchaseOrderFrozenException::class, 'figé', fn () => app(SetPurchaseOrder::class)->handle($this->employee(), $reservation, 'BC-1'));
        }
    }

    public function test_the_same_number_can_be_used_on_two_reservations_of_the_same_customer(): void
    {
        $first = $this->keyAccountReservation();
        $second = Reservation::factory()->for($first->customer)->create();

        app(SetPurchaseOrder::class)->handle($this->employee(), $first, 'BC-CHANTIER-ROUEN');
        app(SetPurchaseOrder::class)->handle($this->employee(), $second, 'BC-CHANTIER-ROUEN');

        $this->assertSame(2, ReservationPurchaseOrder::query()->where('number', 'BC-CHANTIER-ROUEN')->count());
    }

    public function test_an_ordinary_professional_leaves_without_number_and_may_enter_one(): void
    {
        $reservation = $this->reservationStartingToday(Customer::factory()->professional()->create());

        app(SetPurchaseOrder::class)->handle($this->employee(), $reservation, 'BC-77');
        $withoutNumber = $this->reservationStartingToday(Customer::factory()->professional()->create());
        app(DepartReservation::class)->handle($withoutNumber);

        $this->assertSame('BC-77', ReservationPurchaseOrder::query()->where('reservation_id', $reservation->id)->value('number'));
        $this->assertSame(ReservationStatus::InProgress, $withoutNumber->refresh()->status);
    }

    public function test_an_individual_cannot_have_a_purchase_order(): void
    {
        $reservation = $this->reservationStartingToday(Customer::factory()->individual()->create());

        $this->assertRefused(PurchaseOrderRefusedException::class, 'professionnel', fn () => app(SetPurchaseOrder::class)->handle($this->employee(), $reservation, 'BC-1'));
    }

    public function test_the_number_is_trimmed_and_an_invalid_number_is_refused(): void
    {
        $reservation = $this->keyAccountReservation();

        app(SetPurchaseOrder::class)->handle($this->employee(), $reservation, '  BC-2026-0412  ');
        $this->assertSame('BC-2026-0412', ReservationPurchaseOrder::query()->where('reservation_id', $reservation->id)->value('number'));

        $this->assertRefused(InvalidPurchaseOrderNumberException::class, 'saisi', fn () => app(SetPurchaseOrder::class)->handle($this->employee(), $reservation, '   '));
        $this->assertRefused(InvalidPurchaseOrderNumberException::class, '50', fn () => app(SetPurchaseOrder::class)->handle($this->employee(), $reservation, str_repeat('A', 51)));
        $this->assertSame('BC-2026-0412', ReservationPurchaseOrder::query()->where('reservation_id', $reservation->id)->value('number'));
    }

    public function test_a_customer_designated_after_booking_needs_a_number_to_leave(): void
    {
        $customer = Customer::factory()->professional()->create();
        CustomerBillingAccount::factory()->for($customer)->create();
        $reservation = $this->reservationStartingToday($customer);

        app(DesignateKeyAccount::class)->handle($this->employee(), $customer);

        $this->assertRefused(MissingPurchaseOrderException::class, 'bon de commande', fn () => app(DepartReservation::class)->handle($reservation));
    }
}

<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Billing\Money\Money;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Deposit\Events\DepositChanged;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Models\Deposit;
use Functional\Deposit\Models\DepositRate;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CollectDepositTest extends TestCase
{
    use AssertsRefusals, BuildsDepositFixtures, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = $this->seededEmployee();
        $this->actingAs($this->author);
    }

    public function test_the_deposit_is_collected_at_the_category_amount_with_its_author_and_agency(): void
    {
        Event::fake([DepositChanged::class]);
        $reservation = $this->individualReservation();
        DepositRate::factory()->forCategory($reservation->machine->category)->create(['amount' => Money::fromStored(300000)]);

        $deposit = $this->collect($reservation, $this->author, PaymentMethod::CardImprint, 'AUT-42');

        $this->assertSame(DepositStatus::Collected, $deposit->status);
        $this->assertTrue($deposit->amount->equals(Money::fromStored(300000)));
        $this->assertSame(PaymentMethod::CardImprint, $deposit->payment_method);
        $this->assertSame('AUT-42', $deposit->payment_reference);
        $this->assertSame($this->author->getKey(), $deposit->collected_by);
        $this->assertSame($this->author->agencyId(), $deposit->collected_agency_id);
        $this->assertNotNull($deposit->collected_at);
        $activity = Activity::query()->where('log_name', 'deposit')->where('event', 'collected')->sole();
        $this->assertTrue($activity->subject?->is($reservation));
        $this->assertSame($this->author->agencyId(), $activity->properties->get('author_agency_id'));
        Event::assertDispatched(DepositChanged::class, fn (DepositChanged $event): bool => $event->reservationId === $reservation->id);
    }

    public function test_the_collected_amount_is_frozen(): void
    {
        $reservation = $this->individualReservation();
        $deposit = $this->collect($reservation, $this->author);

        DepositRate::factory()->forCategory($reservation->machine->category)->create(['amount' => Money::fromStored(900000)]);

        $this->assertTrue($deposit->fresh()?->amount->equals(Money::fromStored(150000)));
    }

    /**
     * @return iterable<string, array{ReservationStatus}>
     */
    public static function reservationsNotConfirmed(): iterable
    {
        yield 'in progress' => [ReservationStatus::InProgress];
        yield 'closed' => [ReservationStatus::Closed];
        yield 'cancelled' => [ReservationStatus::Cancelled];
    }

    #[DataProvider('reservationsNotConfirmed')]
    public function test_a_deposit_is_only_collected_on_a_confirmed_reservation(ReservationStatus $status): void
    {
        $reservation = Reservation::factory()->for(Customer::factory()->individual())->withStatus($status)->create();

        $this->assertRefused(DepositRefusedException::class, 'réservation confirmée', fn () => $this->collect($reservation, $this->author));
    }

    public function test_professional_and_untyped_customers_pay_no_deposit(): void
    {
        $professionalReservation = $this->confirmedReservationFor(Customer::factory()->professional()->create());
        $untypedReservation = $this->confirmedReservationFor(Customer::factory()->untyped()->create());

        $this->assertRefused(DepositRefusedException::class, 'client particulier', fn () => $this->collect($professionalReservation, $this->author));
        $this->assertRefused(DepositRefusedException::class, 'client particulier', fn () => $this->collect($untypedReservation, $this->author));
    }

    public function test_a_second_collection_is_refused_and_a_single_deposit_exists(): void
    {
        $reservation = $this->individualReservation();
        $this->collect($reservation, $this->author);

        $this->assertRefused(DepositRefusedException::class, 'déjà encaissée', fn () => $this->collect($reservation, $this->author));
        $this->assertSame(1, Deposit::query()->whereBelongsTo($reservation)->count());
    }

    public function test_the_database_refuses_a_second_deposit_for_the_same_reservation(): void
    {
        $reservation = $this->individualReservation();
        Deposit::factory()->for($reservation)->create();

        $this->expectException(QueryException::class);

        Deposit::factory()->for($reservation)->create();
    }

    public function test_a_reference_is_required_except_for_cash(): void
    {
        $reservation = $this->individualReservation();

        $this->assertRefused(DepositRefusedException::class, 'référence est obligatoire', fn () => $this->collect($reservation, $this->author, PaymentMethod::Cheque, '  '));
        $this->assertRefused(DepositRefusedException::class, 'référence est obligatoire', fn () => $this->collect($reservation, $this->author, PaymentMethod::CardImprint, null));

        $deposit = $this->collect($reservation, $this->author, PaymentMethod::Cash, null);

        $this->assertNull($deposit->payment_reference);
    }

    private function individualReservation(): Reservation
    {
        return $this->confirmedReservationFor(Customer::factory()->individual()->create());
    }
}

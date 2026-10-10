<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Booking\Models\Customer;
use Functional\Deposit\Actions\CorrectDepositPayment;
use Functional\Deposit\Actions\RefundDeposit;
use Functional\Deposit\Enums\PaymentMethod;
use Functional\Deposit\Exceptions\DepositRefusedException;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CorrectDepositPaymentTest extends TestCase
{
    use AssertsRefusals, BuildsDepositFixtures, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->author = $this->seededEmployee();
        $this->actingAs($this->author);
    }

    public function test_the_payment_is_corrected_with_a_reason_and_both_values_are_logged(): void
    {
        $deposit = $this->collect($this->confirmedReservationFor(Customer::factory()->individual()->create()), $this->author);

        $corrected = app(CorrectDepositPayment::class)->handle($deposit, PaymentMethod::CardImprint, 'AUT-9', 'Erreur de saisie', $this->author);

        $this->assertSame(PaymentMethod::CardImprint, $corrected->payment_method);
        $this->assertSame('AUT-9', $corrected->payment_reference);
        $this->assertSame(150000, $corrected->amount->minorUnits);
        $properties = Activity::query()->where('log_name', 'deposit')->where('event', 'payment_corrected')->sole()->properties;
        $this->assertSame('Erreur de saisie', $properties->get('reason'));
        $this->assertSame('1234567', $properties->get('previous_reference'));
        $this->assertSame('AUT-9', $properties->get('reference'));
    }

    public function test_the_payment_can_be_corrected_after_the_departure(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);

        $corrected = app(CorrectDepositPayment::class)->handle($deposit, PaymentMethod::Cash, null, 'Payé en espèces', $this->author);

        $this->assertSame(PaymentMethod::Cash, $corrected->payment_method);
    }

    public function test_a_reason_is_required(): void
    {
        $deposit = $this->collect($this->confirmedReservationFor(Customer::factory()->individual()->create()), $this->author);

        $this->assertRefused(DepositRefusedException::class, 'motif', fn () => app(CorrectDepositPayment::class)->handle($deposit, PaymentMethod::Cheque, '42', '   ', $this->author));
    }

    public function test_a_closed_deposit_cannot_be_corrected(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        app(RefundDeposit::class)->handle($deposit, true, $this->author);

        $this->assertRefused(DepositRefusedException::class, 'déjà restituée', fn () => app(CorrectDepositPayment::class)->handle($deposit->refresh(), PaymentMethod::Cheque, '42', 'Erreur', $this->author));
    }
}

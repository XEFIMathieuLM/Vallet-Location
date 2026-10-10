<?php

namespace Functional\Deposit\Tests\Feature;

use Functional\Deposit\Actions\SyncDepositStatus;
use Functional\Deposit\Enums\DepositStatus;
use Functional\Deposit\Livewire\ReservationDepositSection;
use Functional\Deposit\Livewire\Section\CloseDepositActions;
use Functional\Deposit\Livewire\Section\CorrectPaymentForm;
use Functional\Deposit\Tests\Concerns\BuildsDepositFixtures;
use Functional\Fleet\Contracts\AgencyMember;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationDepositSectionRefundTest extends TestCase
{
    use BuildsDepositFixtures, RefreshDatabase;

    private Model&Authenticatable&AgencyMember $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->author = $this->seededEmployee();
        $this->actingAs($this->author);
    }

    public function test_a_deposit_to_refund_offers_the_refund_with_its_confirmation(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);

        Livewire::test(ReservationDepositSection::class, ['reservation' => $deposit->reservation])
            ->assertSee('À restituer')
            ->assertSeeLivewire(CloseDepositActions::class);

        Livewire::test(CloseDepositActions::class, ['reservation' => $deposit->reservation])
            ->assertSee('aucun dégât constaté')
            ->call('refund')
            ->assertSee('Confirmez')
            ->set('isNoDamageConfirmed', true)
            ->call('refund')
            ->assertHasNoErrors()
            ->assertDispatched('deposit-updated');

        $this->assertSame(DepositStatus::Refunded, $deposit->fresh()?->status);
    }

    public function test_a_blocked_deposit_lists_the_damages_to_settle(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        $damage = $this->unresolvedDamageOn($deposit->reservation);
        app(SyncDepositStatus::class)->for($deposit->reservation);

        Livewire::test(ReservationDepositSection::class, ['reservation' => $deposit->reservation])
            ->assertSee('Bloquée par un dégât')
            ->assertSee($damage->comment);
    }

    public function test_a_deposit_to_settle_shows_the_computed_retention_and_is_settled(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);
        $this->billedDamageOn($deposit->reservation, 45000);
        app(SyncDepositStatus::class)->for($deposit->reservation);

        Livewire::test(CloseDepositActions::class, ['reservation' => $deposit->reservation])
            ->assertSee('450,00 €')
            ->assertSee('1050,00 €')
            ->call('settle')
            ->assertDispatched('deposit-updated');

        $this->assertSame(DepositStatus::Settled, $deposit->fresh()?->status);
    }

    public function test_the_payment_is_corrected_from_its_form(): void
    {
        $deposit = $this->closedReservationWithDeposit($this->author);

        Livewire::test(CorrectPaymentForm::class, ['reservation' => $deposit->reservation])
            ->set('method', 'cash')
            ->set('reason', 'Payé en espèces')
            ->call('correct')
            ->assertHasNoErrors()
            ->assertDispatched('deposit-updated');

        $this->assertSame('cash', $deposit->fresh()?->payment_method->value);
    }
}

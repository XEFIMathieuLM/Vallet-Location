<?php

namespace Functional\Sales\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Billing\Money\Money;
use Functional\Booking\Data\NewCustomer;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Models\Customer;
use Functional\Sales\Actions\RecordOffer;
use Functional\Sales\Actions\RejectOffer;
use Functional\Sales\Actions\WithdrawOffer;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Exceptions\OfferRefusedException;
use Functional\Sales\Livewire\SaleOffers;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecordOfferTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
    }

    public function test_an_offer_from_an_existing_customer_is_attached_to_the_sale_with_its_author_and_date(): void
    {
        $author = $this->employee();
        $sale = $this->listedSale();
        $customer = Customer::factory()->create(['name' => 'Terrassements Martin']);

        $offer = $this->offer($author, $sale, $customer, '16500');

        $this->assertTrue($offer->sale->is($sale));
        $this->assertTrue($offer->customer->is($customer));
        $this->assertTrue(Money::fromStored(1650000)->equals($offer->amount));
        $this->assertSame(OfferStatus::Pending, $offer->status);
        $this->assertSame('2026-11-02', $offer->offered_on->toDateString());
        $this->assertSame($author->getKey(), $offer->recorded_by);
    }

    public function test_a_buyer_missing_from_the_customer_file_is_created_with_the_offer(): void
    {
        $offer = $this->offer($this->employee(), $this->listedSale(), new NewCustomer('Loc\'TP Normandie', '0235000000', null, CustomerType::Professional), '15000');

        $this->assertSame('Loc\'TP Normandie', $offer->customer->name);
        $this->assertSame(CustomerType::Professional, $offer->customer->type);
        $this->assertSame(1, Customer::query()->where('name', 'Loc\'TP Normandie')->count());
    }

    public function test_no_offer_can_be_recorded_on_a_reserved_sale(): void
    {
        $sale = $this->reservedSale('2026-11-20');

        $this->assertRefused(OfferRefusedException::class, 'Réservée', fn () => $this->offer($this->employee(), $sale, Customer::factory()->create(), '17000'));
    }

    public function test_a_zero_amount_or_a_future_offer_date_is_refused(): void
    {
        $author = $this->employee();
        $sale = $this->listedSale();

        $this->assertRefused(OfferRefusedException::class, 'montant', fn () => $this->offer($author, $sale, Customer::factory()->create(), '0'));
        $this->assertRefused(OfferRefusedException::class, 'futur', fn () => app(RecordOffer::class)->handle($author, $sale, Customer::factory()->create(), Money::fromInput('100'), CarbonImmutable::parse('2026-11-03')));
        $this->assertSame(0, SaleOffer::query()->count());
    }

    public function test_a_pending_offer_can_be_rejected_or_withdrawn_with_the_decision_author(): void
    {
        $author = $this->employee();
        $sale = $this->listedSale();
        $rejectedOffer = $this->offer($author, $sale, Customer::factory()->create(), '12000');
        $withdrawnOffer = $this->offer($author, $sale, Customer::factory()->create(), '13000');

        app(RejectOffer::class)->handle($author, $rejectedOffer);
        app(WithdrawOffer::class)->handle($author, $withdrawnOffer);

        $this->assertSame(OfferStatus::Rejected, $rejectedOffer->fresh()?->status);
        $this->assertSame(OfferStatus::Withdrawn, $withdrawnOffer->fresh()?->status);
        $this->assertSame($author->getKey(), $rejectedOffer->fresh()?->decided_by);
    }

    private function offer(mixed $author, Sale $sale, Customer|NewCustomer $buyer, string $amount): SaleOffer
    {
        return app(RecordOffer::class)->handle($author, $sale, $buyer, Money::fromInput($amount), CarbonImmutable::today());
    }

    public function test_a_new_buyer_cannot_be_recorded_from_the_screen_without_a_customer_type(): void
    {
        Livewire::actingAs($this->employee())->test(SaleOffers::class, ['sale' => $this->listedSale()])
            ->set('isNewCustomer', true)
            ->set('newCustomerName', 'Loc\'TP Normandie')
            ->set('newCustomerPhone', '0235000000')
            ->set('amount', '15000')
            ->call('record')
            ->assertHasErrors(['newCustomerType' => 'required']);

        $this->assertSame(0, Customer::query()->where('name', 'Loc\'TP Normandie')->count());
    }
}

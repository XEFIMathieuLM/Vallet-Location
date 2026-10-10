<?php

namespace Functional\Sales\Tests\Feature;

use Functional\Billing\Money\Money;
use Functional\Sales\Actions\ListMachineForSale;
use Functional\Sales\Data\SaleListing;
use Functional\Sales\Exceptions\MachineAlreadyForSaleException;
use Functional\Sales\Models\Sale;
use Functional\Sales\Tests\Concerns\BuildsSalesFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrentListingTest extends TestCase
{
    use BuildsSalesFixtures, RefreshDatabase;

    public function test_when_another_agency_lists_the_machine_in_the_same_instant_only_one_sale_is_opened(): void
    {
        $author = $this->employee();
        $machine = $this->machineForSale();
        $competingAuthor = $this->employee();
        $hasCompeted = false;
        Sale::creating(function () use ($machine, $competingAuthor, &$hasCompeted): void {
            if ($hasCompeted) {
                return;
            }

            $hasCompeted = true;
            DB::table('sales')->insert([
                'machine_id' => $machine->id, 'status' => 'listed', 'asking_price_cents' => 1500000, 'condition' => 'Concurrent',
                'agency_id' => $competingAuthor->agencyId(), 'listed_by' => $competingAuthor->getKey(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        $this->assertRefused(
            MachineAlreadyForSaleException::class,
            'au même moment',
            fn () => app(ListMachineForSale::class)->handle($author, $machine, new SaleListing(Money::fromInput('18000'), null, null, 'Bon état', null)),
        );
        $this->assertSame(0, Sale::query()->count());
    }
}

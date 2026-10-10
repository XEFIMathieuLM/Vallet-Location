<?php

namespace Functional\Sales\Database\Seeders;

use Carbon\CarbonImmutable;
use Functional\Billing\Enums\BillableLineType;
use Functional\Billing\Models\Transmission;
use Functional\Booking\Models\Customer;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Functional\Sales\Enums\OfferStatus;
use Functional\Sales\Models\Sale;
use Functional\Sales\Models\SaleOffer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class SalesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $seller = $this->userModel()::query()->orderBy('id')->firstOrFail();
        $machines = Machine::factory()->count(5)->recycle(Agency::all())->recycle(MachineCategory::all())->create();
        $sellerAttributes = ['listed_by' => $seller->getKey(), 'agency_id' => $machines[0]->agency_id];

        $listedSale = Sale::factory()->listed()->create(['machine_id' => $machines[0]->id, ...$sellerAttributes]);
        SaleOffer::factory()->count(2)->recycle(Customer::all())->create(['sale_id' => $listedSale->id, 'recorded_by' => $seller->getKey()]);
        Sale::factory()->listed()->create(['machine_id' => $machines[1]->id, ...$sellerAttributes]);

        $reservedSale = Sale::factory()->reserved(CarbonImmutable::today()->addDays(12))->create(['machine_id' => $machines[2]->id, ...$sellerAttributes]);
        SaleOffer::factory()->decided(OfferStatus::Rejected)->create(['sale_id' => $reservedSale->id, 'recorded_by' => $seller->getKey()]);

        $soldSale = Sale::factory()->sold()->create(['machine_id' => $machines[3]->id, ...$sellerAttributes]);
        $machines[3]->update(['status' => MachineStatus::Retired]);
        Transmission::factory()->forSource(BillableLineType::UsedMachineSale, $soldSale->id)->sent()->create();

        Sale::factory()->cancelled()->create(['machine_id' => $machines[4]->id, 'cancelled_by' => $seller->getKey(), ...$sellerAttributes]);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}

<?php

namespace Functional\Portal\Database\Factories;

use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\MachineCategory;
use Functional\Portal\Models\CategoryIndicativePrice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<CategoryIndicativePrice>
 */
class CategoryIndicativePriceFactory extends Factory
{
    protected $model = CategoryIndicativePrice::class;

    public function definition(): array
    {
        return [
            'machine_category_id' => MachineCategory::factory(),
            'daily_price_cents' => faker()->number(40, 400) * 100,
            'updated_by' => fn () => Factory::factoryForModel($this->userModel()),
            'updated_agency_id' => Agency::factory(),
        ];
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('auth.providers.users.model');
    }
}

<?php

namespace Functional\Inspection\Database\Factories;

use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Models\CategoryView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryView>
 */
class CategoryViewFactory extends Factory
{
    protected $model = CategoryView::class;

    public function definition(): array
    {
        return [
            'machine_category_id' => MachineCategory::factory(),
            'label' => ucfirst(faker()->words(1)).' '.faker()->unique()->number(1, 99999),
            'position' => faker()->number(1, 20),
        ];
    }
}

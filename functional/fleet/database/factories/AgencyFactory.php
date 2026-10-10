<?php

namespace Functional\Fleet\Database\Factories;

use Functional\Fleet\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agency>
 */
class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        return [
            'name' => faker()->unique()->agencyName(),
            'address' => null,
        ];
    }
}

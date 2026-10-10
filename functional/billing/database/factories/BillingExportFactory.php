<?php

namespace Functional\Billing\Database\Factories;

use App\Models\User;
use Functional\Billing\Models\BillingExport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingExport>
 */
class BillingExportFactory extends Factory
{
    protected $model = BillingExport::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'line_count' => faker()->number(1, 20),
            'file_path' => faker()->billingExportFileName(),
        ];
    }
}

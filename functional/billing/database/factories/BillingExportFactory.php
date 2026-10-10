<?php

namespace Functional\Billing\Database\Factories;

use Functional\Billing\Models\BillingExport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<BillingExport>
 */
class BillingExportFactory extends Factory
{
    protected $model = BillingExport::class;

    public function definition(): array
    {
        return [
            'created_by' => fn () => Factory::factoryForModel($this->userModel()),
            'line_count' => faker()->number(1, 20),
            'file_path' => faker()->billingExportFileName(),
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

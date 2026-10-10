<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_category_id')->nullable()->constrained();
            $table->integer('amount_cents');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE deposit_rates ADD CONSTRAINT deposit_rates_amount_positive CHECK (amount_cents > 0)');
        DB::statement('CREATE UNIQUE INDEX deposit_rates_category_unique ON deposit_rates (machine_category_id) WHERE machine_category_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX deposit_rates_default_unique ON deposit_rates ((1)) WHERE machine_category_id IS NULL');

        DB::table('deposit_rates')->insert([
            'machine_category_id' => null,
            'amount_cents' => config()->integer('deposit.initial_default_amount_cents'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_rates');
    }
};

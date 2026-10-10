<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_indicative_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_category_id')->unique()->constrained();
            $table->integer('daily_price_cents');
            $table->foreignId('updated_by')->constrained('users');
            $table->foreignId('updated_agency_id')->constrained('agencies');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE category_indicative_prices ADD CONSTRAINT category_indicative_prices_positive CHECK (daily_price_cents > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('category_indicative_prices');
    }
};

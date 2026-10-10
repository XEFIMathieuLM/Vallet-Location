<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained();
            $table->string('number', 50);
            $table->foreignId('entered_by')->constrained('users');
            $table->foreignId('agency_id')->constrained();
            $table->timestamp('entered_at');
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE reservation_purchase_orders ADD CONSTRAINT reservation_purchase_orders_number_length
            CHECK (length(btrim(number)) BETWEEN 1 AND 50)
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_purchase_orders');
    }
};

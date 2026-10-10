<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->integer('amount_cents');
            $table->date('offered_on');
            $table->string('status')->default('pending');
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX sale_offers_one_accepted_per_sale ON sale_offers (sale_id) WHERE status = 'accepted'");
        DB::statement('ALTER TABLE sale_offers ADD CONSTRAINT sale_offers_amount_positive CHECK (amount_cents > 0)');
        DB::statement("ALTER TABLE sale_offers ADD CONSTRAINT sale_offers_decision_fields CHECK ((status = 'pending') = (decided_by IS NULL AND decided_at IS NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_offers');
    }
};

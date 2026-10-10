<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained();
            $table->string('status')->default('listed');
            $table->integer('asking_price_cents');
            $table->smallInteger('year_of_manufacture')->nullable();
            $table->integer('operating_hours')->nullable();
            $table->string('condition');
            $table->text('comment')->nullable();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('listed_by')->constrained('users');
            $table->foreignId('buyer_id')->nullable()->constrained('customers');
            $table->unsignedBigInteger('accepted_offer_id')->nullable();
            $table->integer('final_price_cents')->nullable();
            $table->date('planned_handover_date')->nullable();
            $table->date('handed_over_on')->nullable();
            $table->foreignId('handed_over_by')->nullable()->constrained('users');
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['status', 'planned_handover_date']);
        });

        DB::statement("CREATE UNIQUE INDEX sales_one_live_sale_per_machine ON sales (machine_id) WHERE status <> 'cancelled'");
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_asking_price_positive CHECK (asking_price_cents > 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_final_price_positive CHECK (final_price_cents IS NULL OR final_price_cents > 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_year_of_manufacture_plausible CHECK (year_of_manufacture IS NULL OR year_of_manufacture >= 1950)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_operating_hours_positive CHECK (operating_hours IS NULL OR operating_hours >= 0)');
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_reservation_fields CHECK ((status IN ('reserved', 'sold')) = (buyer_id IS NOT NULL AND accepted_offer_id IS NOT NULL AND final_price_cents IS NOT NULL AND planned_handover_date IS NOT NULL))");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_handover_fields CHECK ((status = 'sold') = (handed_over_on IS NOT NULL AND handed_over_by IS NOT NULL))");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_cancellation_fields CHECK ((status = 'cancelled') = (cancellation_reason IS NOT NULL AND cancelled_by IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

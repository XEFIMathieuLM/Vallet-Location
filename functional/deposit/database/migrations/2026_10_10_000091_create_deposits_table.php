<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained();
            $table->integer('amount_cents');
            $table->string('payment_method');
            $table->string('payment_reference')->nullable();
            $table->string('status')->default('collected')->index();
            $table->foreignId('collected_by')->constrained('users');
            $table->foreignId('collected_agency_id')->constrained('agencies');
            $table->timestamp('collected_at');
            $table->timestamp('awaiting_since')->nullable()->index();
            $table->integer('retained_cents')->nullable();
            $table->integer('refunded_cents')->nullable();
            $table->boolean('is_no_damage_confirmed')->default(false);
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->foreignId('closed_agency_id')->nullable()->constrained('agencies');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE deposits
                ADD CONSTRAINT deposits_amount_positive CHECK (amount_cents > 0),
                ADD CONSTRAINT deposits_reference_required CHECK (payment_method = 'cash' OR (payment_reference IS NOT NULL AND payment_reference <> '')),
                ADD CONSTRAINT deposits_closing_fields CHECK (
                    (status IN ('refunded', 'settled')) = (closed_at IS NOT NULL AND closed_by IS NOT NULL AND closed_agency_id IS NOT NULL AND retained_cents IS NOT NULL AND refunded_cents IS NOT NULL)
                ),
                ADD CONSTRAINT deposits_closing_balance CHECK (retained_cents IS NULL OR refunded_cents IS NULL OR retained_cents + refunded_cents = amount_cents),
                ADD CONSTRAINT deposits_refund_retains_nothing CHECK (status <> 'refunded' OR retained_cents = 0),
                ADD CONSTRAINT deposits_closing_not_negative CHECK ((retained_cents IS NULL OR retained_cents >= 0) AND (refunded_cents IS NULL OR refunded_cents >= 0))
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};

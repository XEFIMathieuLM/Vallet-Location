<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_id')->unique()->constrained();
            $table->string('outcome');
            $table->integer('amount_cents')->nullable();
            $table->text('label')->nullable();
            $table->text('waiver_reason')->nullable();
            $table->foreignId('settled_by')->constrained('users');
            $table->timestamp('settled_at');
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE damage_settlements ADD CONSTRAINT damage_settlements_outcome_fields CHECK (
                (outcome = 'billed' AND amount_cents > 0 AND label IS NOT NULL AND label <> '' AND waiver_reason IS NULL)
                OR (outcome = 'waived' AND amount_cents IS NULL AND label IS NULL AND waiver_reason IS NOT NULL AND waiver_reason <> '')
            )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_settlements');
    }
};

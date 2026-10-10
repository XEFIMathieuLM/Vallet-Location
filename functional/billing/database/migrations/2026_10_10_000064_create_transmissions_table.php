<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transmissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('billable_period_id')->nullable()->unique()->constrained();
            $table->foreignId('damage_settlement_id')->nullable()->unique()->constrained();
            $table->foreignId('reservation_id')->constrained();
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->text('last_error')->nullable();
            $table->string('external_ref')->nullable();
            $table->foreignId('billing_export_id')->nullable()->constrained();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at']);
        });

        DB::statement('ALTER TABLE transmissions ADD CONSTRAINT transmissions_single_source CHECK (num_nonnulls(billable_period_id, damage_settlement_id) = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transmissions');
    }
};

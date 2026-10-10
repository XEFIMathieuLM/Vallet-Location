<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_account_id')->constrained();
            $table->foreignId('machine_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('comment', 500)->nullable();
            $table->integer('indicative_daily_price_cents')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reservation_id')->nullable()->unique()->constrained();
            $table->string('refusal_reason', 500)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->foreignId('decided_agency_id')->nullable()->constrained('agencies');
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('customer_notified_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'start_date']);
            $table->index(['customer_account_id', 'status']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE reservation_requests
                ADD CONSTRAINT reservation_requests_status CHECK (status IN ('pending', 'confirmed', 'refused', 'cancelled', 'expired')),
                ADD CONSTRAINT reservation_requests_dates CHECK (end_date >= start_date),
                ADD CONSTRAINT reservation_requests_price_positive CHECK (indicative_daily_price_cents IS NULL OR indicative_daily_price_cents > 0),
                ADD CONSTRAINT reservation_requests_confirmed_reservation CHECK ((status = 'confirmed') = (reservation_id IS NOT NULL)),
                ADD CONSTRAINT reservation_requests_refusal_reason CHECK ((status = 'refused') = (refusal_reason IS NOT NULL AND refusal_reason <> '')),
                ADD CONSTRAINT reservation_requests_decision_date CHECK ((status = 'pending') = (decided_at IS NULL)),
                ADD CONSTRAINT reservation_requests_no_overlapping_pending EXCLUDE USING gist (
                    customer_account_id WITH =,
                    machine_id WITH =,
                    daterange(start_date, end_date, '[]') WITH &&
                ) WHERE (status = 'pending')
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_requests');
    }
};

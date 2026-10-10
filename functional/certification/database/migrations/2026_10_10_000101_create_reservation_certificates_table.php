<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained('reservations');
            $table->string('status');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->string('last_failure_reason')->nullable();
            $table->timestamp('status_changed_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at']);
            $table->index(['status', 'status_changed_at']);
        });

        DB::statement("ALTER TABLE reservation_certificates ADD CONSTRAINT reservation_certificates_status CHECK (status IN ('awaiting_report', 'awaiting_email', 'pending', 'failed', 'sent', 'hand_delivered'))");
        DB::statement("ALTER TABLE reservation_certificates ADD CONSTRAINT reservation_certificates_delivered_at CHECK ((delivered_at IS NOT NULL) = (status IN ('sent', 'hand_delivered')))");
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_certificates');
    }
};

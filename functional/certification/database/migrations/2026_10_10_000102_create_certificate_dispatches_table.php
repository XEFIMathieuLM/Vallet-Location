<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_certificate_id')->constrained('reservation_certificates');
            $table->foreignId('vgp_report_id')->constrained('vgp_reports');
            $table->string('channel');
            $table->string('recipient_email')->nullable();
            $table->boolean('is_automatic');
            $table->foreignId('author_id')->nullable()->constrained('users');
            $table->string('outcome');
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamp('created_at')->nullable();
        });

        DB::statement("ALTER TABLE certificate_dispatches ADD CONSTRAINT certificate_dispatches_channel CHECK (channel IN ('email', 'hand'))");
        DB::statement("ALTER TABLE certificate_dispatches ADD CONSTRAINT certificate_dispatches_outcome CHECK (outcome IN ('sent', 'failed'))");
        DB::statement("ALTER TABLE certificate_dispatches ADD CONSTRAINT certificate_dispatches_email_recipient CHECK (channel <> 'email' OR recipient_email IS NOT NULL)");
        DB::statement("ALTER TABLE certificate_dispatches ADD CONSTRAINT certificate_dispatches_hand_is_manual_and_sent CHECK (channel <> 'hand' OR (is_automatic = false AND outcome = 'sent'))");
        DB::statement('ALTER TABLE certificate_dispatches ADD CONSTRAINT certificate_dispatches_manual_has_author CHECK (is_automatic OR author_id IS NOT NULL)');
        DB::statement("ALTER TABLE certificate_dispatches ADD CONSTRAINT certificate_dispatches_failure_reason CHECK ((failure_reason IS NOT NULL) = (outcome = 'failed'))");
        DB::statement("CREATE UNIQUE INDEX certificate_dispatches_single_automatic_success ON certificate_dispatches (reservation_certificate_id) WHERE is_automatic AND outcome = 'sent'");
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_dispatches');
    }
};

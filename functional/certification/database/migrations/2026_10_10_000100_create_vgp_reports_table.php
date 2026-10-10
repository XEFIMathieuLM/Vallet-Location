<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vgp_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('size_bytes');
            $table->date('verified_on');
            $table->date('due_on');
            $table->foreignId('deposited_by')->constrained('users');
            $table->timestamps();

            $table->index(['machine_id', 'id']);
        });

        DB::statement('ALTER TABLE vgp_reports ADD CONSTRAINT vgp_reports_due_after_verification CHECK (due_on > verified_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vgp_reports');
    }
};

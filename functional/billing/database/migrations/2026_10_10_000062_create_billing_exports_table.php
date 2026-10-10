<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedInteger('line_count');
            $table->string('file_path');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE billing_exports ADD CONSTRAINT billing_exports_not_empty CHECK (line_count >= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_exports');
    }
};

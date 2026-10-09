<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->foreignId('machine_category_id')->constrained();
            $table->foreignId('agency_id')->constrained();
            $table->string('status')->default('available')->index();
            $table->boolean('is_subject_to_vgp')->default(false);
            $table->date('vgp_due_date')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX machines_reference_normalized_unique ON machines (upper(btrim(reference)))');
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};

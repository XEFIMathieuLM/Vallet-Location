<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained();
            $table->string('label');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['reservation_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_views');
    }
};

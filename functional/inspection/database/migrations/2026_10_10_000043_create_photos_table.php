<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained();
            $table->foreignId('reservation_view_id')->constrained();
            $table->string('step');
            $table->foreignId('photo_session_id')->constrained();
            $table->timestamps();

            $table->index(['reservation_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};

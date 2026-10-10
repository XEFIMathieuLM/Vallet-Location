<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained();
            $table->string('step');
            $table->string('token_hash', 64)->unique();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_sessions');
    }
};

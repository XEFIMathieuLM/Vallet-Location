<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_category_id')->constrained();
            $table->string('label');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['machine_category_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_views');
    }
};

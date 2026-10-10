<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_billing_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained();
            $table->string('external_ref');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_billing_accounts');
    }
};

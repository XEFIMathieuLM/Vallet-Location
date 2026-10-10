<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 30);
            $table->string('declared_type');
            $table->string('password');
            $table->foreignId('customer_id')->nullable()->index()->constrained();
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE customer_accounts
                ADD CONSTRAINT customer_accounts_declared_type CHECK (declared_type IN ('individual', 'professional')),
                ADD CONSTRAINT customer_accounts_email_normalized CHECK (email = lower(trim(email)))
            SQL);

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customer_accounts');
    }
};

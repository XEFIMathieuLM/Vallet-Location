<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('type')->nullable();
        });

        DB::statement("ALTER TABLE customers ADD CONSTRAINT customers_type_check CHECK (type IN ('individual', 'professional'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE customers DROP CONSTRAINT customers_type_check');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

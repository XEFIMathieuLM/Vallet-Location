<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transmissions', function (Blueprint $table) {
            $table->unsignedBigInteger('reservation_id')->nullable()->change();
            $table->string('source_type')->nullable()->after('damage_settlement_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->unique(['source_type', 'source_id']);
        });

        DB::statement('ALTER TABLE transmissions DROP CONSTRAINT transmissions_single_source');
        DB::statement('ALTER TABLE transmissions ADD CONSTRAINT transmissions_single_source CHECK (num_nonnulls(billable_period_id, damage_settlement_id, source_id) = 1)');
        DB::statement('ALTER TABLE transmissions ADD CONSTRAINT transmissions_source_pair CHECK ((source_id IS NULL) = (source_type IS NULL))');
        DB::statement('ALTER TABLE transmissions ADD CONSTRAINT transmissions_rental_has_reservation CHECK (source_id IS NOT NULL OR reservation_id IS NOT NULL)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE transmissions DROP CONSTRAINT transmissions_rental_has_reservation');
        DB::statement('ALTER TABLE transmissions DROP CONSTRAINT transmissions_source_pair');
        DB::statement('ALTER TABLE transmissions DROP CONSTRAINT transmissions_single_source');
        DB::table('transmissions')->whereNotNull('source_id')->delete();

        Schema::table('transmissions', function (Blueprint $table) {
            $table->dropUnique(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
            $table->unsignedBigInteger('reservation_id')->nullable(false)->change();
        });

        DB::statement('ALTER TABLE transmissions ADD CONSTRAINT transmissions_single_source CHECK (num_nonnulls(billable_period_id, damage_settlement_id) = 1)');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('planned_end_date');
            $table->string('status')->default('confirmed')->index();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('conflict_reason')->nullable()->index();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE reservations ADD CONSTRAINT reservations_end_after_start CHECK (end_date >= start_date)');
        DB::statement(<<<'SQL'
            ALTER TABLE reservations ADD CONSTRAINT reservations_no_overlap
            EXCLUDE USING gist (machine_id WITH =, daterange(start_date, end_date, '[]') WITH &&)
            WHERE (status <> 'cancelled')
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

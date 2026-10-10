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

        Schema::create('billable_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained();
            $table->string('kind');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('days');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE billable_periods ADD CONSTRAINT billable_periods_end_after_start CHECK (end_date >= start_date)');
        DB::statement('ALTER TABLE billable_periods ADD CONSTRAINT billable_periods_days_match_dates CHECK (days = end_date - start_date + 1)');
        DB::statement(<<<'SQL'
            ALTER TABLE billable_periods ADD CONSTRAINT billable_periods_no_overlap
            EXCLUDE USING gist (reservation_id WITH =, daterange(start_date, end_date, '[]') WITH &&)
            SQL);
        DB::statement("CREATE UNIQUE INDEX billable_periods_one_final_per_reservation ON billable_periods (reservation_id) WHERE kind = 'final'");
    }

    public function down(): void
    {
        Schema::dropIfExists('billable_periods');
    }
};

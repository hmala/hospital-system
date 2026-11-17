<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('resident_station_reading_drains');

        Schema::create('resident_station_reading_drains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resident_station_reading_id');
            $table->unsignedInteger('drain_number')->default(1);
            $table->string('drain_label')->nullable();
            $table->decimal('amount', 8, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('resident_station_reading_id', 'rsr_drains_reading_fk')
                ->references('id')
                ->on('resident_station_readings')
                ->cascadeOnDelete();

            $table->index(['resident_station_reading_id', 'drain_number'], 'rsr_drains_reading_num_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resident_station_reading_drains');
    }
};

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
        Schema::create('resident_station_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_station_id')->constrained('resident_stations')->onDelete('cascade');
            $table->foreignId('resident_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->string('bp')->nullable();
            $table->string('temp')->nullable();
            $table->string('pr')->nullable();
            $table->string('rr')->nullable();
            $table->string('spo2')->nullable();
            $table->text('clinical_examination')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resident_station_readings');
    }
};

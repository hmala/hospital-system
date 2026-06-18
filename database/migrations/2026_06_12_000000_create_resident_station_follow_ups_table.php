<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('resident_station_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
            $table->foreignId('resident_station_id')->constrained('resident_stations')->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('follow_up_date');
            $table->enum('session', ['morning', 'evening']);
            $table->text('notes');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('resident_station_follow_ups');
    }
};

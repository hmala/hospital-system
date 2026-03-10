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
        Schema::create('emergency_emergency_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emergency_service_id')->constrained()->cascadeOnDelete();
            $table->unique(['emergency_id', 'emergency_service_id'], 'emergency_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_emergency_service');
    }
};

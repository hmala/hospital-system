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
        Schema::create('surgery_medical_device', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->onDelete('cascade');
            $table->foreignId('medical_device_id')->constrained('medical_devices')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgery_medical_device');
    }
};

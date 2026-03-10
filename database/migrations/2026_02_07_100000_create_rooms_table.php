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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->enum('room_type', ['regular', 'vip'])->default('regular');
            $table->string('floor')->nullable();
            $table->decimal('daily_fee', 12, 2)->default(0);
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
            $table->text('description')->nullable();
            $table->integer('beds_count')->default(1);
            $table->boolean('has_bathroom')->default(true);
            $table->boolean('has_tv')->default(false);
            $table->boolean('has_ac')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

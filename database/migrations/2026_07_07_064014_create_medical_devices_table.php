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
        Schema::create('medical_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('supplier')->nullable();
            $table->decimal('price', 15, 2)->default(0.00);
            $table->string('status')->default('active'); // active, inactive, maintenance
            $table->string('serial_number')->nullable()->unique();
            $table->date('last_maintenance_at')->nullable();
            $table->date('purchase_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_devices');
    }
};

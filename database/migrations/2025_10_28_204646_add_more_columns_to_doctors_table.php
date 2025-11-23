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
        Schema::table('doctors', function (Blueprint $table) {
            // $table->string('qualification')->nullable(); // موجود
            // $table->decimal('consultation_fee', 10, 2)->nullable(); // موجود
            // $table->integer('max_patients_per_day')->default(20); // موجود
            // $table->boolean('is_active')->default(true); // موجود
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['qualification', 'consultation_fee', 'max_patients_per_day', 'is_active']);
        });
    }
};

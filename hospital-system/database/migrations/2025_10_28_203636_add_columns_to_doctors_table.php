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
            // $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // موجود
            // $table->foreignId('department_id')->constrained('departments')->onDelete('cascade'); // موجود
            // $table->string('license_number')->unique(); // موجود
            // $table->string('specialization'); // موجود
            // $table->integer('experience_years')->nullable(); // موجود
            // $table->text('bio')->nullable(); // موجود
            // $table->boolean('is_available')->default(true); // مختلف، لكن ربما غير ضروري
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['user_id', 'department_id', 'license_number', 'specialization', 'experience_years', 'bio', 'is_available']);
        });
    }
};

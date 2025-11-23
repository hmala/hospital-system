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
        Schema::table('patients', function (Blueprint $table) {
            $table->string('mother_name')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('set null');
            $table->string('governorate')->nullable();
            $table->string('district')->nullable();
            $table->string('neighborhood')->nullable();
            $table->boolean('covered_by_insurance')->nullable();
            $table->string('insurance_booklet_number')->nullable();
            $table->enum('marital_status', ['أعزب', 'متزوج', 'مطلق', 'أرمل'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn(['mother_name', 'country_id', 'governorate', 'district', 'neighborhood', 'covered_by_insurance', 'insurance_booklet_number', 'marital_status']);
        });
    }
};

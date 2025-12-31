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
        Schema::table('surgeries', function (Blueprint $table) {
            $table->enum('surgery_category', ['elective', 'emergency', 'urgent', 'semi_urgent'])->nullable()->after('supplies');
            $table->enum('surgery_type_detail', ['diagnostic', 'therapeutic', 'preventive', 'cosmetic', 'reconstructive', 'palliative'])->nullable()->after('surgery_category');
            $table->enum('anesthesia_position', ['supine', 'prone', 'lateral', 'lithotomy', 'fowler', 'trendelenburg', 'sitting', 'other'])->nullable()->after('surgery_type_detail');
            $table->enum('asa_classification', ['asa1', 'asa2', 'asa3', 'asa4', 'asa5', 'asa6'])->nullable()->after('anesthesia_position');
            $table->enum('surgical_complexity', ['minor', 'intermediate', 'major', 'complex'])->nullable()->after('asa_classification');
            $table->text('surgical_notes')->nullable()->after('surgical_complexity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $table->dropColumn(['surgery_category', 'surgery_type_detail', 'anesthesia_position', 'asa_classification', 'surgical_complexity', 'surgical_notes']);
        });
    }
};

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
            $table->foreignId('anesthesiologist_id')->nullable()->constrained('doctors')->onDelete('set null')->after('anesthesia_type');
            $table->foreignId('anesthesiologist_2_id')->nullable()->constrained('doctors')->onDelete('set null')->after('anesthesiologist_id');
            $table->string('surgical_assistant_name')->nullable()->after('anesthesiologist_2_id');
            $table->time('start_time')->nullable()->after('surgical_assistant_name');
            $table->time('end_time')->nullable()->after('start_time');
            $table->string('referring_physician')->nullable()->after('end_time');
            $table->string('surgery_classification')->nullable()->after('referring_physician');
            $table->text('supplies')->nullable()->after('surgery_classification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $table->dropForeign(['anesthesiologist_id']);
            $table->dropForeign(['anesthesiologist_2_id']);
            $table->dropColumn(['anesthesiologist_id', 'anesthesiologist_2_id', 'surgical_assistant_name', 'start_time', 'end_time', 'referring_physician', 'surgery_classification', 'supplies']);
        });
    }
};

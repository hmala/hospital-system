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
        if (Schema::hasTable('emergencies')) {
            Schema::table('emergencies', function (Blueprint $table) {
                if (!Schema::hasColumn('emergencies', 'discharge_type')) {
                    $table->enum('discharge_type', ['recovered', 'against_medical_advice'])->nullable()->after('discharge_time');
                }
                if (!Schema::hasColumn('emergencies', 'discharge_notes')) {
                    $table->text('discharge_notes')->nullable()->after('discharge_type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            $table->dropColumn(['discharge_type', 'discharge_notes']);
        });
    }
};

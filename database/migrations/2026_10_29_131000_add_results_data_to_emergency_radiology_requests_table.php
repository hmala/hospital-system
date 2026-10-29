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
        Schema::table('emergency_radiology_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('emergency_radiology_requests', 'results_data')) {
                $table->longText('results_data')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_radiology_requests', function (Blueprint $table) {
            if (Schema::hasColumn('emergency_radiology_requests', 'results_data')) {
                $table->dropColumn('results_data');
            }
        });
    }
};


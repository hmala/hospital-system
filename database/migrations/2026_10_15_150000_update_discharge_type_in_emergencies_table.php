<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('emergencies') && Schema::hasColumn('emergencies', 'discharge_type')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE emergencies MODIFY COLUMN discharge_type VARCHAR(50) NULL");
            } else {
                Schema::table('emergencies', function (Blueprint $table) {
                    $table->string('discharge_type', 50)->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('emergencies') && Schema::hasColumn('emergencies', 'discharge_type')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE emergencies MODIFY COLUMN discharge_type ENUM('recovered', 'against_medical_advice') NULL");
            }
        }
    }
};

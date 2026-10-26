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
        Schema::table('emergency_emergency_service', function (Blueprint $table) {
            if (! Schema::hasColumn('emergency_emergency_service', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('emergency_service_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_emergency_service', function (Blueprint $table) {
            if (Schema::hasColumn('emergency_emergency_service', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};

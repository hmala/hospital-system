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
        Schema::table('resident_stations', function (Blueprint $table) {
            // إضافة حقل phase للتمييز بين مرحلة التحضير وما بعد العملية
            $table->enum('phase', ['pre_op', 'post_op'])->default('pre_op')->after('surgery_id');
            $table->index('phase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_stations', function (Blueprint $table) {
            $table->dropIndex(['phase']);
            $table->dropColumn('phase');
        });
    }
};

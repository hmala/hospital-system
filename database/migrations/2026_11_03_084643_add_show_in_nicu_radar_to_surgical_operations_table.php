<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('surgical_operations', function (Blueprint $table) {
            $table->boolean('show_in_nicu_radar')->default(false)->after('is_active');
        });

        // تفعيل العمليات القيصرية والولادية الافتراضية للظهور برادار الخدج
        DB::table('surgical_operations')
            ->where(function ($query) {
                $query->where('name', 'like', '%قيصر%')
                    ->orWhere('name', 'like', '%ولاد%');
            })
            ->update(['show_in_nicu_radar' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgical_operations', function (Blueprint $table) {
            $table->dropColumn('show_in_nicu_radar');
        });
    }
};

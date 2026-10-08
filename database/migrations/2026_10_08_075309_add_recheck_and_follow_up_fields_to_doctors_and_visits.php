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
        if (!Schema::hasColumn('doctors', 'recheck_validity_days')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->unsignedSmallInteger('recheck_validity_days')->default(7)->after('consultation_fee');
            });
        }

        if (!Schema::hasColumn('visits', 'follow_up_date')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->date('follow_up_date')->nullable()->after('treatment_plan');
                $table->string('follow_up_notes', 500)->nullable()->after('follow_up_date');
            });
        }

        if (!Schema::hasColumn('appointments', 'is_free_recheck')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->boolean('is_free_recheck')->default(false)->after('consultation_fee');
                $table->unsignedBigInteger('recheck_parent_visit_id')->nullable()->after('is_free_recheck');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('doctors', 'recheck_validity_days')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn('recheck_validity_days');
            });
        }

        if (Schema::hasColumn('visits', 'follow_up_date')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn(['follow_up_date', 'follow_up_notes']);
            });
        }

        if (Schema::hasColumn('appointments', 'is_free_recheck')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn(['is_free_recheck', 'recheck_parent_visit_id']);
            });
        }
    }
};

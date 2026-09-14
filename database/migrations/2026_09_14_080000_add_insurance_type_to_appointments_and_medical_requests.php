<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointments') && !Schema::hasColumn('appointments', 'insurance_type')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('insurance_type')->nullable()->after('payment_status');
            });
        }

        if (Schema::hasTable('medical_requests') && !Schema::hasColumn('medical_requests', 'insurance_type')) {
            Schema::table('medical_requests', function (Blueprint $table) {
                $table->string('insurance_type')->nullable()->after('payment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'insurance_type')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('insurance_type');
            });
        }

        if (Schema::hasTable('medical_requests') && Schema::hasColumn('medical_requests', 'insurance_type')) {
            Schema::table('medical_requests', function (Blueprint $table) {
                $table->dropColumn('insurance_type');
            });
        }
    }
};

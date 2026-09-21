<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('requests') && !Schema::hasColumn('requests', 'insurance_type')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->string('insurance_type')->nullable()->default('none')->after('payment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('requests') && Schema::hasColumn('requests', 'insurance_type')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->dropColumn('insurance_type');
            });
        }
    }
};

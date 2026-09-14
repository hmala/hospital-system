<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('surgeries') && !Schema::hasColumn('surgeries', 'insurance_type')) {
            Schema::table('surgeries', function (Blueprint $table) {
                $table->string('insurance_type')->nullable()->after('payment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('surgeries') && Schema::hasColumn('surgeries', 'insurance_type')) {
            Schema::table('surgeries', function (Blueprint $table) {
                $table->dropColumn('insurance_type');
            });
        }
    }
};

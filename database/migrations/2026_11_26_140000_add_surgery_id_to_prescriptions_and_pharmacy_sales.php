<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prescriptions') && ! Schema::hasColumn('prescriptions', 'surgery_id')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->foreignId('surgery_id')->nullable()->constrained('surgeries')->nullOnDelete();
            });
        }

        if (Schema::hasTable('pharmacy_sales') && ! Schema::hasColumn('pharmacy_sales', 'surgery_id')) {
            Schema::table('pharmacy_sales', function (Blueprint $table) {
                $table->foreignId('surgery_id')->nullable()->constrained('surgeries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('prescriptions') && Schema::hasColumn('prescriptions', 'surgery_id')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('surgery_id');
            });
        }

        if (Schema::hasTable('pharmacy_sales') && Schema::hasColumn('pharmacy_sales', 'surgery_id')) {
            Schema::table('pharmacy_sales', function (Blueprint $table) {
                $table->dropConstrainedForeignId('surgery_id');
            });
        }
    }
};


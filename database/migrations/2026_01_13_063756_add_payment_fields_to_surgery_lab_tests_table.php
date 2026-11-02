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
        Schema::table('surgery_lab_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_lab_tests', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('status');
            }
            if (! Schema::hasColumn('surgery_lab_tests', 'payment_id')) {
                $table->foreignId('payment_id')->nullable()->after('payment_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgery_lab_tests', function (Blueprint $table) {
            if (Schema::hasColumn('surgery_lab_tests', 'payment_id')) {
                $table->dropColumn('payment_id');
            }
            if (Schema::hasColumn('surgery_lab_tests', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};

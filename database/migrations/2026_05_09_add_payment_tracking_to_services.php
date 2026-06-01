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
        // إضافة payment_id لجدول الربط بين الطوارئ والخدمات
        Schema::table('emergency_emergency_service', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('emergency_service_id')->constrained()->nullOnDelete();
        });

        // إضافة payment_id لطلبات التحاليل
        Schema::table('emergency_lab_requests', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('emergency_id')->constrained()->nullOnDelete();
        });

        // إضافة payment_id لطلبات الأشعة
        Schema::table('emergency_radiology_requests', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('emergency_id')->constrained()->nullOnDelete();
        });

        // إضافة follow_up_payment_id لتتبع دفع رسوم المتابعة
        Schema::table('emergencies', function (Blueprint $table) {
            $table->foreignId('follow_up_payment_id')->nullable()->after('doctor_follow_up_fee')->constrained('payments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_emergency_service', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });

        Schema::table('emergency_lab_requests', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });

        Schema::table('emergency_radiology_requests', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });

        Schema::table('emergencies', function (Blueprint $table) {
            $table->dropForeign(['follow_up_payment_id']);
            $table->dropColumn('follow_up_payment_id');
        });
    }
};

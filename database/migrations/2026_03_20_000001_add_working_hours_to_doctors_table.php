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
        Schema::table('doctors', function (Blueprint $table) {
            // إضافة حقول وقت الدخول والخروج
            $table->time('start_time')->nullable()->after('consultation_fee')->comment('وقت الدخول');
            $table->time('end_time')->nullable()->after('start_time')->comment('وقت الخروج');
            
            // إضافة حقل أيام الأسبوع (JSON array)
            $table->json('working_days')->nullable()->after('end_time')->comment('أيام العمل في الأسبوع');
            
            // حذف الحقول غير المطلوبة
            $table->dropColumn(['qualification', 'license_number', 'experience_years', 'max_patients_per_day', 'bio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            // استرجاع الحقول المحذوفة
            $table->string('qualification')->nullable();
            $table->string('license_number')->unique()->nullable();
            $table->integer('experience_years')->default(0);
            $table->integer('max_patients_per_day')->default(20);
            $table->text('bio')->nullable();
            
            // حذف الحقول المضافة
            $table->dropColumn(['start_time', 'end_time', 'working_days']);
        });
    }
};

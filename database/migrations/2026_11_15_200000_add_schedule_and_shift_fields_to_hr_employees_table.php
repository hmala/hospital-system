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
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->string('schedule_type', 30)->default('fixed')->after('employment_type')->comment('نوع الجدولة: fixed دوام ثابت، rotating خفارات متناوبة');
            $table->foreignId('default_shift_id')->nullable()->after('schedule_type')->constrained('hr_shifts')->nullOnDelete()->comment('الشفت الدائم/الأساسي');
            $table->boolean('is_split_shift')->default(false)->after('default_shift_id')->comment('هل الدوام وجبتين (صباحي + مسائي)؟');
            $table->foreignId('second_shift_id')->nullable()->after('is_split_shift')->constrained('hr_shifts')->nullOnDelete()->comment('الشفت المسائي الثاني للدوام المزدوج');
            $table->time('custom_start_time')->nullable()->after('second_shift_id')->comment('وقت حضور مخصص إن وجد');
            $table->time('custom_end_time')->nullable()->after('custom_start_time')->comment('وقت انصراف مخصص إن وجد');
            $table->json('weekly_off_days')->nullable()->after('custom_end_time')->comment('أيام العطلة والاستراحة الأسبوعية');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropForeign(['default_shift_id']);
            $table->dropForeign(['second_shift_id']);
            $table->dropColumn([
                'schedule_type',
                'default_shift_id',
                'is_split_shift',
                'second_shift_id',
                'custom_start_time',
                'custom_end_time',
                'weekly_off_days',
            ]);
        });
    }
};

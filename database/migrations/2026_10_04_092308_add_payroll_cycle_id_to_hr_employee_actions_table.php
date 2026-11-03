<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employee_actions', function (Blueprint $table) {
            $table->foreignId('hr_payroll_cycle_id')->nullable()->after('status')->constrained('hr_payroll_cycles')->nullOnDelete()->comment('دورة الرواتب التي تم فيها ترحيل هذا الإجراء');
        });
    }

    public function down(): void
    {
        Schema::table('hr_employee_actions', function (Blueprint $table) {
            $table->dropForeign(['hr_payroll_cycle_id']);
            $table->dropColumn('hr_payroll_cycle_id');
        });
    }
};

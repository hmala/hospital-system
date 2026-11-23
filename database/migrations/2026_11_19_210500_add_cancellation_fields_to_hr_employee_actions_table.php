<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employee_actions', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->after('hr_payroll_cycle_id')->constrained('users')->nullOnDelete()->comment('المستخدم الذي قام بإلغاء الإجراء');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by')->comment('تاريخ وتوقيت الإلغاء');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at')->comment('سبب إلغاء الخصم أو المكافأة');
        });
    }

    public function down(): void
    {
        Schema::table('hr_employee_actions', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_by', 'cancelled_at', 'cancellation_reason']);
        });
    }
};

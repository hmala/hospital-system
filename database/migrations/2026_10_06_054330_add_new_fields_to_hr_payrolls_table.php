<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// الأعمدة موجودة بالفعل من الميغريشن الأصلي - هذا الملف لإكمال الـ approved_by فقط
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_payroll_cycles', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_payroll_cycles', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('hr_payroll_cycles', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_payroll_cycles', function (Blueprint $table) {
            if (Schema::hasColumn('hr_payroll_cycles', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approved_by', 'approved_at']);
            }
        });
    }
};

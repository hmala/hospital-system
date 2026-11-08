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
            if (! Schema::hasColumn('hr_payroll_cycles', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('hr_payroll_cycles', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
        });

        Schema::table('hr_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_payrolls', 'overtime_amount')) {
                $table->decimal('overtime_amount', 12, 2)->default(0)->after('allowances');
            }
            if (! Schema::hasColumn('hr_payrolls', 'absence_deduction')) {
                $table->decimal('absence_deduction', 12, 2)->default(0)->after('overtime_amount');
            }
            if (! Schema::hasColumn('hr_payrolls', 'loan_deduction')) {
                $table->decimal('loan_deduction', 12, 2)->default(0)->after('absence_deduction');
            }
            if (! Schema::hasColumn('hr_payrolls', 'social_security_amount')) {
                $table->decimal('social_security_amount', 12, 2)->default(0)->after('loan_deduction');
            }
            if (! Schema::hasColumn('hr_payrolls', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('social_security_amount');
            }
            if (! Schema::hasColumn('hr_payrolls', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'bank'])->default('cash')->after('net_salary');
            }
            if (! Schema::hasColumn('hr_payrolls', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('payment_method');
            }
            if (! Schema::hasColumn('hr_payrolls', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('is_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $cols = ['overtime_amount', 'absence_deduction', 'loan_deduction', 'social_security_amount', 'tax_amount', 'payment_method', 'is_paid', 'paid_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('hr_payrolls', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('hr_payroll_cycles', function (Blueprint $table) {
            if (Schema::hasColumn('hr_payroll_cycles', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approved_by', 'approved_at']);
            }
        });
    }
};

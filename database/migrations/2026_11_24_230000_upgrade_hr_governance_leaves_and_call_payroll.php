<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'manager_id')) {
                $table->foreignId('manager_id')
                    ->nullable()
                    ->after('hospital_id')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->comment('مدير القسم المسؤول');
            }
        });

        Schema::table('hr_employees', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_employees', 'salary_calculation_mode')) {
                $table->string('salary_calculation_mode', 30)
                    ->default('fixed_salary')
                    ->after('basic_salary')
                    ->comment('نمط الاحتساب: fixed_salary راتب ثابت أو per_call بالـ Call والشفتات');
            }
            if (! Schema::hasColumn('hr_employees', 'custom_call_rate')) {
                $table->decimal('custom_call_rate', 12, 2)
                    ->nullable()
                    ->after('salary_calculation_mode')
                    ->comment('سعر Call خاص بالموظف (يتجاوز السعر الافتراضي للشفت)');
            }
            if (! Schema::hasColumn('hr_employees', 'annual_leave_balance')) {
                $table->integer('annual_leave_balance')
                    ->default(21)
                    ->after('status')
                    ->comment('رصيد الإجازات السنوي الاسترشادي');
            }
        });

        if (! Schema::hasTable('hr_action_batches')) {
            Schema::create('hr_action_batches', function (Blueprint $table) {
                $table->id();
                $table->string('scope', 30)->comment('hospital, department, selected');
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('hr_action_setting_id')->nullable()->constrained('hr_action_settings')->nullOnDelete();
                $table->date('action_date');
                $table->text('reason');
                $table->unsignedInteger('target_count')->default(0);
                $table->string('status', 30)->default('active')->comment('active, partially_cancelled, cancelled');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancellable_until')->nullable();
                $table->foreignId('cancel_requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancel_requested_at')->nullable();
                $table->text('cancel_request_reason')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('hr_employee_actions', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_employee_actions', 'hr_action_batch_id')) {
                $table->foreignId('hr_action_batch_id')
                    ->nullable()
                    ->after('hr_action_setting_id')
                    ->constrained('hr_action_batches')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('hr_employee_actions', 'cancellable_until')) {
                $table->timestamp('cancellable_until')->nullable()->after('status');
            }
            if (! Schema::hasColumn('hr_employee_actions', 'cancel_requested_by')) {
                $table->foreignId('cancel_requested_by')->nullable()->after('cancellation_reason')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('hr_employee_actions', 'cancel_requested_at')) {
                $table->timestamp('cancel_requested_at')->nullable()->after('cancel_requested_by');
            }
            if (! Schema::hasColumn('hr_employee_actions', 'cancel_request_reason')) {
                $table->text('cancel_request_reason')->nullable()->after('cancel_requested_at');
            }
            if (! Schema::hasColumn('hr_employee_actions', 'cancel_approved_by')) {
                $table->foreignId('cancel_approved_by')->nullable()->after('cancel_request_reason')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('hr_employee_actions', 'cancel_approved_at')) {
                $table->timestamp('cancel_approved_at')->nullable()->after('cancel_approved_by');
            }
        });

        if (! Schema::hasTable('hr_leave_requests')) {
            Schema::create('hr_leave_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hr_employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('leave_type', 30)->default('annual')->comment('annual, sick, emergency, unpaid, other');
                $table->boolean('is_paid')->default(true);
                $table->date('start_date');
                $table->date('end_date');
                $table->unsignedInteger('days_count')->default(1);
                $table->text('reason');
                $table->string('status', 30)->default('pending_department')->comment('pending_department, pending_admin, approved, rejected, cancelled');
                $table->foreignId('dept_manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('dept_decision_at')->nullable();
                $table->text('dept_notes')->nullable();
                $table->foreignId('admin_approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('admin_decision_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->foreignId('hr_payroll_cycle_id')->nullable()->constrained('hr_payroll_cycles')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        Schema::table('hr_shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_shifts', 'duration_hours')) {
                $table->decimal('duration_hours', 5, 2)->default(8)->after('end_time');
            }
            if (! Schema::hasColumn('hr_shifts', 'call_rate')) {
                $table->decimal('call_rate', 12, 2)->default(0)->after('duration_hours');
            }
            if (! Schema::hasColumn('hr_shifts', 'shift_category')) {
                $table->string('shift_category', 50)->default('general')->after('call_rate');
            }
        });

        if (! Schema::hasTable('hr_roster_approvals')) {
            Schema::create('hr_roster_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('period_type', 20)->default('week')->comment('week or month');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status', 30)->default('draft')->comment('draft, submitted_by_dept, approved');
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('hr_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_schedules', 'is_leave')) {
                $table->boolean('is_leave')->default(false)->after('is_off_day');
            }
            if (! Schema::hasColumn('hr_schedules', 'hr_leave_request_id')) {
                $table->foreignId('hr_leave_request_id')->nullable()->after('is_leave')->constrained('hr_leave_requests')->nullOnDelete();
            }
            if (! Schema::hasColumn('hr_schedules', 'hr_roster_approval_id')) {
                $table->foreignId('hr_roster_approval_id')->nullable()->after('hr_leave_request_id')->constrained('hr_roster_approvals')->nullOnDelete();
            }
        });

        Schema::table('hr_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_payrolls', 'salary_calculation_mode')) {
                $table->string('salary_calculation_mode', 30)->default('fixed_salary');
            }
            if (! Schema::hasColumn('hr_payrolls', 'calls_count')) {
                $table->unsignedInteger('calls_count')->default(0);
            }
            if (! Schema::hasColumn('hr_payrolls', 'calls_amount')) {
                $table->decimal('calls_amount', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('hr_payrolls', 'absence_deduction')) {
                $table->decimal('absence_deduction', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('hr_payrolls', 'unpaid_leave_deduction')) {
                $table->decimal('unpaid_leave_deduction', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->dropColumn(['salary_calculation_mode', 'calls_count', 'calls_amount', 'unpaid_leave_deduction']);
        });

        Schema::table('hr_schedules', function (Blueprint $table) {
            $table->dropForeign(['hr_leave_request_id']);
            $table->dropForeign(['hr_roster_approval_id']);
            $table->dropColumn(['is_leave', 'hr_leave_request_id', 'hr_roster_approval_id']);
        });

        Schema::dropIfExists('hr_roster_approvals');

        Schema::table('hr_shifts', function (Blueprint $table) {
            $table->dropColumn(['duration_hours', 'call_rate', 'shift_category']);
        });

        Schema::dropIfExists('hr_leave_requests');

        Schema::table('hr_employee_actions', function (Blueprint $table) {
            $table->dropForeign(['hr_action_batch_id']);
            $table->dropForeign(['cancel_requested_by']);
            $table->dropForeign(['cancel_approved_by']);
            $table->dropColumn([
                'hr_action_batch_id',
                'cancellable_until',
                'cancel_requested_by',
                'cancel_requested_at',
                'cancel_request_reason',
                'cancel_approved_by',
                'cancel_approved_at',
            ]);
        });

        Schema::dropIfExists('hr_action_batches');

        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn(['salary_calculation_mode', 'custom_call_rate', 'annual_leave_balance']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn('manager_id');
        });
    }
};

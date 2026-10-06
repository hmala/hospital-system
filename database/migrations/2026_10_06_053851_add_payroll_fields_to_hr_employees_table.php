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
            // طريقة صرف الراتب
            $table->enum('payment_method', ['cash', 'bank'])->default('cash')->after('basic_salary')->comment('كاش أو بنك');
            $table->string('bank_name')->nullable()->after('payment_method');
            $table->string('bank_account_number')->nullable()->after('bank_name');

            // تسعيرة ساعة العمل الإضافي
            $table->decimal('overtime_hourly_rate', 10, 2)->default(0)->after('bank_account_number')->comment('سعر ساعة العمل الإضافي');

            // الضمان الاجتماعي والضريبة
            $table->boolean('subject_to_social_security')->default(false)->after('overtime_hourly_rate');
            $table->decimal('social_security_percentage', 5, 2)->default(5.00)->after('subject_to_social_security')->comment('نسبة الضمان الاجتماعي %');
            $table->boolean('subject_to_tax')->default(false)->after('social_security_percentage');
            $table->decimal('tax_percentage', 5, 2)->default(0)->after('subject_to_tax')->comment('نسبة الضريبة %');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method', 'bank_name', 'bank_account_number',
                'overtime_hourly_rate',
                'subject_to_social_security', 'social_security_percentage',
                'subject_to_tax', 'tax_percentage',
            ]);
        });
    }
};

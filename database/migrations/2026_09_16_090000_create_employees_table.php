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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 30)->unique()->comment('الرقم الوظيفي أو كود الباجة');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('ربط اختياري بحساب الدخول للنظام');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->comment('القسم التابع له الموظف');
            
            // البيانات الشخصية
            $table->string('full_name')->comment('الاسم الكامل للموظف');
            $table->string('national_id', 50)->nullable()->comment('رقم الهوية / البطاقة الموحدة');
            $table->enum('gender', ['male', 'female'])->default('male')->comment('الجنس');
            $table->date('date_of_birth')->nullable()->comment('تاريخ الميلاد');
            $table->string('phone', 30)->comment('رقم الهاتف الأساسي');
            $table->string('emergency_phone', 30)->nullable()->comment('هاتف الطوارئ / شخص قريب');
            $table->string('email')->nullable()->comment('البريد الإلكتروني');
            $table->text('address')->nullable()->comment('عنوان السكن');
            $table->string('blood_group', 10)->nullable()->comment('فصيلة الدم');

            // البيانات الوظيفية
            $table->enum('staff_type', [
                'medical',         // كادر طبي (أطباء، جراحين)
                'nursing',         // كادر تمريضي
                'technical',       // كادر فني (مختبر، أشعة، صيدلة)
                'administrative',  // كادر إداري ومالي
                'service'          // خدمات وصيانة وحراسة
            ])->default('administrative')->comment('نوع وتصنيف الكادر');
            
            $table->string('job_title')->comment('المسمى الوظيفي');
            $table->enum('employment_type', [
                'full_time',   // دوام كامل
                'part_time',   // دوام جزئي
                'contract',    // عقد محدد المدة
                'daily_shift'  // أجر يومي / خفارات
            ])->default('full_time')->comment('نوع التعيين / التعاقد');
            
            $table->date('hire_date')->comment('تاريخ المباشرة بالعمل');
            $table->date('contract_end_date')->nullable()->comment('تاريخ انتهاء العقد إن وُجد');
            $table->decimal('basic_salary', 12, 2)->default(0)->comment('الراتب الأساسي');
            $table->enum('status', [
                'active',      // على رأس العمل
                'on_leave',    // في إجازة
                'suspended',   // موقوف مؤقتاً
                'resigned',    // مستقيل
                'terminated'   // منهي خدماته
            ])->default('active')->comment('الحالة الوظيفية');

            // بيانات خاصة بالكادر الطبي والفني (تظهر مشروطة)
            $table->string('medical_license_number', 100)->nullable()->comment('رقم إجازة / ترخيص ممارسة المهنة');
            $table->date('license_expiry_date')->nullable()->comment('تاريخ انتهاء إجازة ممارسة المهنة');
            $table->string('syndicate_card_number', 100)->nullable()->comment('رقم هوية النقابة');
            $table->string('sub_specialty')->nullable()->comment('التخصص الدقيق');
            $table->string('qualification')->nullable()->comment('المؤهل العلمي / الشهادة');

            // مستندات وملاحظات
            $table->string('profile_photo')->nullable()->comment('صورة الموظف');
            $table->text('notes')->nullable()->comment('ملاحظات إضافية');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

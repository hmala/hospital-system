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
        // 1. جدول مستمسكات ووثائق الموظف
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('employee_code', 30)->index()->comment('الرمز الوظيفي للموظف');
            $table->string('document_type', 100)->comment('نوع المستمسك (بطاقة موحدة، سكن، عقد...)');
            $table->string('file_name')->comment('اسم الملف المورث للرمز الوظيفي والنوع');
            $table->string('file_path')->comment('مسار تخزين الملف في السيرفر');
            $table->string('file_extension', 10)->nullable()->comment('امتداد الملف pdf, jpg...');
            $table->unsignedBigInteger('file_size')->nullable()->comment('حجم الملف بالبايت');
            $table->text('notes')->nullable()->comment('ملاحظات على المستمسك');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. جدول خيارات القوائم المنسدلة في الموارد البشرية (أنواع التعاقد، أنواع المستمسكات...)
        Schema::create('hr_lookup_options', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index()->comment('employment_type, document_type...');
            $table->string('name')->comment('اسم الخيار المعروض بالعربية');
            $table->string('code', 50)->nullable()->comment('كود تعريفي');
            $table->boolean('is_active')->default(true)->comment('حالة التفعيل');
            $table->integer('sort_order')->default(0)->comment('ترتيب الظهور');
            $table->timestamps();
        });

        // 3. جدول تحديد الحقول الإجبارية والاختيارية لنموذج الموظف
        Schema::create('hr_field_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('field_key', 50)->unique()->comment('اسم الحقل البرمجي phone, national_id...');
            $table->string('field_name_ar')->comment('الاسم بالعربية');
            $table->string('group_name', 50)->default('personal')->comment('المجموعة: personal, job, medical');
            $table->boolean('is_required')->default(false)->comment('هل الحقل إجباري؟');
            $table->boolean('is_locked')->default(false)->comment('حقول أساسية مقفلة لا يمكن إلغاء إجباريتها كـ full_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_field_requirements');
        Schema::dropIfExists('hr_lookup_options');
        Schema::dropIfExists('employee_documents');
    }
};

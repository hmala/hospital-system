<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('health_insurance_categories')) {
            Schema::create('health_insurance_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique(); // A, B, C, D, E, F, G, H, I
                $table->string('name'); // الفئة A، الفئة B...
                $table->text('description')->nullable(); // توصيف الفئة والمشمولين بها
                $table->boolean('requires_thermal_stamp')->default(false); // شرط ختم حراري
                
                // نسب الاستقطاع (الأهلي)
                $table->decimal('consultation_copay', 5, 2)->default(10.00); // الاستشارية
                $table->decimal('surgery_copay', 5, 2)->default(25.00);      // العمليات الجراحية
                $table->decimal('lab_copay', 5, 2)->default(25.00);          // المختبر والتحاليل
                $table->decimal('radiology_copay', 5, 2)->default(25.00);    // الأشعة والسونار والرنين
                $table->decimal('support_services_copay', 5, 2)->default(25.00); // الخدمات الساندة (علاج طبيعي، توحد)
                $table->decimal('medication_copay', 5, 2)->default(25.00);   // الأدوية
                $table->decimal('emergency_copay', 5, 2)->default(0.00);     // الطوارئ (0%)
                $table->decimal('dental_copay', 5, 2)->default(25.00);       // الأسنان
                
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('patients') && !Schema::hasColumn('patients', 'health_insurance_category_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->unsignedBigInteger('health_insurance_category_id')->nullable()->after('insurance_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'health_insurance_category_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('health_insurance_category_id');
            });
        }

        Schema::dropIfExists('health_insurance_categories');
    }
};

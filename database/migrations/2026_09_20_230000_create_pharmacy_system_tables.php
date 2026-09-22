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
        // 1. جدول الأدوية والمستلزمات الطبية
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('national_code')->nullable()->index()->comment('الرمز الوطني الرسمي بهيئة الضمان e.g. 01-C00-038');
            $table->string('name')->index()->comment('الاسم التجاري للدواء أو المستلزم');
            $table->string('generic_name')->nullable()->index()->comment('الاسم العلمي والمادة الفعالة');
            $table->string('dosage_form')->nullable()->comment('الشكل الصيدلاني: حبوب، شراب، أمبول، فيال، مرهم، مستلزم');
            $table->string('strength')->nullable()->comment('العيار / التركيز e.g. 500mg');
            $table->string('barcode')->nullable()->index()->comment('باركود العلبة الرئيسي');
            $table->string('sub_barcode')->nullable()->index()->comment('باركود الشريط / الوحدة الصغرى');

            // نظام الوحدات الديناميكي
            $table->string('main_unit')->default('علبة')->comment('الوحدة الكبرى');
            $table->string('sub_unit')->default('شريط')->comment('الوحدة الصغرى');
            $table->unsignedInteger('sub_units_count')->default(1)->comment('معامل التحويل: كم وحدة صغرى في الوحدة الكبرى');

            // التسعير الديناميكي
            $table->decimal('cost_price', 12, 2)->default(0.00)->comment('سعر الشراء والتكلفة');
            $table->decimal('sale_price', 12, 2)->default(0.00)->comment('سعر بيع العلبة نقداً للجمهور');
            $table->decimal('sub_unit_sale_price', 12, 2)->default(0.00)->comment('سعر بيع الوحدة الصغرى/الشريط نقداً');
            $table->decimal('hi_price', 12, 2)->nullable()->comment('سعر بيع العلبة المعتمد للضمان الصحي');
            $table->decimal('moi_price', 12, 2)->nullable()->comment('سعر بيع العلبة لوزارة الداخلية');
            $table->boolean('is_insurance_covered')->default(true)->comment('هل مشمول بالتغطية التأمينية؟');

            // مؤشرات التنبيه والرقابة
            $table->unsignedInteger('min_stock_alert')->default(5)->comment('حد التنبيه لنفاد الرصيد بالعلب');
            $table->string('storage_temperature')->nullable()->comment('شروط التخزين والحفظ');
            $table->boolean('requires_prescription')->default(false)->comment('هل يشترط وصفة طبية؟');
            $table->boolean('is_controlled')->default(false)->comment('أدوية المؤثرات العقلية والرقابية');
            $table->boolean('is_active')->default(true)->comment('حالة تفعيل الصنف');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // 2. جدول البدائل الدوائية العلمية
        Schema::create('medicine_alternatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->foreignId('alternative_medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->string('notes')->nullable()->comment('ملاحظات التكافؤ الحيوي أو الفروقات');
            $table->timestamps();

            $table->unique(['medicine_id', 'alternative_medicine_id'], 'med_alt_unique');
        });

        // 3. جدول الخدمات الصيدلانية غير المخزنية
        Schema::create('pharmacy_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم الخدمة: ضرب إبرة، قياس سكر، قياس ضغط، غيار');
            $table->decimal('price', 12, 2)->default(0.00)->comment('سعر الخدمة نقداً');
            $table->decimal('hi_price', 12, 2)->nullable()->comment('سعر الخدمة للضمان الصحي');
            $table->decimal('moi_price', 12, 2)->nullable()->comment('سعر الخدمة لوزارة الداخلية');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. جدول الشحنات والصلاحيات بنظام FEFO
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->string('batch_number')->index()->comment('رقم الوجبة / التشغيلة Lot');
            $table->date('expiry_date')->index()->comment('تاريخ انتهاء الصلاحية');
            $table->unsignedInteger('initial_quantity')->default(0)->comment('الكمية المستلمة بالعلب');
            $table->unsignedInteger('current_quantity')->default(0)->comment('العلب الكاملة المتبقية');
            $table->unsignedInteger('current_sub_units')->default(0)->comment('الأشرطة المتبقية من العلب المفتوحة');
            $table->decimal('purchase_price', 12, 2)->default(0.00)->comment('سعر شراء العلبة في هذه الشحنة');
            $table->string('supplier_name')->nullable()->comment('اسم المورد أو شركة التوزيع');
            $table->date('received_at')->nullable()->comment('تاريخ الاستلام والتوريد');
            $table->string('status', 30)->default('active')->comment('active, expired, depleted, quarantined');
            $table->timestamps();

            $table->index(['medicine_id', 'expiry_date'], 'med_batch_fefo_idx');
        });

        // 5. جدول فواتير الصرف والمبيعات
        Schema::create('pharmacy_sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique()->comment('رقم الفاتورة المميز e.g. PH-20260920-0001');
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->string('patient_name')->nullable()->comment('اسم المريض للمبيعات المباشرة');
            $table->string('patient_phone')->nullable();
            $table->string('sale_type', 30)->default('direct_otc')->comment('direct_otc, prescription, emergency, inpatient');

            // المحاسبة والضمان الصحي
            $table->decimal('total_amount', 12, 2)->default(0.00)->comment('المبلغ الإجمالي للفاتورة');
            $table->decimal('patient_share', 12, 2)->default(0.00)->comment('حصة المريض المسددة');
            $table->decimal('insurance_share', 12, 2)->default(0.00)->comment('حصة جهة التأمين / الضمان');
            $table->string('insurance_type', 50)->default('none')->comment('none, health_insurance, interior_ministry');
            $table->foreignId('health_insurance_category_id')->nullable()->constrained('health_insurance_categories')->nullOnDelete();
            $table->string('insurance_card_no')->nullable();
            $table->decimal('copay_percentage', 5, 2)->default(0.00)->comment('نسبة التحمل المطبقة');
            $table->string('claim_status', 30)->default('none')->comment('none, pending, approved, rejected');

            // حالات الصرف ومسار الدفع
            $table->string('payment_status', 30)->default('paid')->comment('paid, pending_cashier, refunded');
            $table->string('dispensing_status', 30)->default('dispensed')->comment('dispensed, pending, partially_dispensed, cancelled');
            $table->string('payment_route', 30)->default('pharmacy_cashier')->comment('pharmacy_cashier, central_cashier');
            $table->boolean('is_held')->default(false)->comment('هل الفاتورة معلقة مؤقتاً؟');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            // المستخدمين والتوثيق
            $table->foreignId('user_id')->constrained('users')->comment('من أنشأ الفاتورة');
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete()->comment('الصيدلي الذي قام بالصرف');
            $table->timestamp('dispensed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // 6. جدول بنود الفاتورة
        Schema::create('pharmacy_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('pharmacy_sales')->onDelete('cascade');
            $table->string('item_type', 20)->default('medicine')->comment('medicine, service');
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('pharmacy_services')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('medicine_batches')->nullOnDelete();
            $table->string('unit_type', 20)->default('main_unit')->comment('main_unit (علبة), sub_unit (شريط)');
            $table->decimal('quantity', 8, 2)->default(1.00);
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->string('dosage_instructions')->nullable()->comment('تعليمات الجرعة والاستخدام');
            $table->string('duration')->nullable()->comment('مدة العلاج');
            $table->string('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_sale_items');
        Schema::dropIfExists('pharmacy_sales');
        Schema::dropIfExists('medicine_batches');
        Schema::dropIfExists('pharmacy_services');
        Schema::dropIfExists('medicine_alternatives');
        Schema::dropIfExists('medicines');
    }
};

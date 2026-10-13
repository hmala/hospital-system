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
        // 1. جدول خدمات وباقات الخدج
        if (!Schema::hasTable('nicu_services')) {
            Schema::create('nicu_services', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('name')->comment('اسم الخدمة');
                $table->string('code')->unique()->comment('رمز الخدمة');
                $table->decimal('daily_fee', 12, 2)->default(0)->comment('الأجرة اليومية بالدينار العراقي');
                $table->text('description')->nullable()->comment('وصف الخدمة وما تشمله');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. تحديث جدول الحاضنات
        if (Schema::hasTable('incubators')) {
            Schema::table('incubators', function (Blueprint $table) {
                if (!Schema::hasColumn('incubators', 'camera_stream_url')) {
                    $table->string('camera_stream_url')->nullable()->after('notes')->comment('رابط كاميرا الحاضنة المباشر');
                }
                // تعديل status و incubator_type إلى string(50) لدعم حالات جديدة مثل sterilizing و capsule
                $table->string('status', 50)->default('available')->change();
                $table->string('incubator_type', 50)->default('normal')->change();
            });
        }

        // 3. تحديث جدول حجوزات الحاضنات
        if (Schema::hasTable('incubator_reservations')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                $table->string('status', 50)->default('pending')->change();

                if (!Schema::hasColumn('incubator_reservations', 'nicu_service_id')) {
                    $table->foreignId('nicu_service_id')->nullable()->after('incubator_id')
                          ->constrained('nicu_services')->nullOnDelete();
                }
                if (!Schema::hasColumn('incubator_reservations', 'current_weight')) {
                    $table->integer('current_weight')->nullable()->after('birth_weight')->comment('الوزن الحالي بالجرام');
                }
                if (!Schema::hasColumn('incubator_reservations', 'twin_group_id')) {
                    $table->string('twin_group_id')->nullable()->after('current_weight')->comment('معرف مجموعة التوأم');
                }
                if (!Schema::hasColumn('incubator_reservations', 'admission_source')) {
                    $table->string('admission_source', 50)->default('internal')->after('admission_time')->comment('مصدر الدخول: internal, external, c_section');
                }
                if (!Schema::hasColumn('incubator_reservations', 'discharge_type')) {
                    $table->string('discharge_type', 50)->nullable()->after('discharge_time')->comment('نوع الخروج: improved, step_down, transferred_out, dama, expired');
                }
                if (!Schema::hasColumn('incubator_reservations', 'camera_token')) {
                    $table->string('camera_token', 64)->nullable()->after('discharge_notes');
                }
                if (!Schema::hasColumn('incubator_reservations', 'camera_active')) {
                    $table->boolean('camera_active')->default(true)->after('camera_token');
                }
            });
        }

        // 4. جدول سجل تتبع الخدمات اليومية للطفل
        if (!Schema::hasTable('nicu_service_logs')) {
            Schema::create('nicu_service_logs', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('reservation_id')->constrained('incubator_reservations')->cascadeOnDelete();
                $table->foreignId('nicu_service_id')->constrained('nicu_services')->cascadeOnDelete();
                $table->decimal('daily_fee', 12, 2)->default(0)->comment('الأجرة المطبقة لليوم');
                $table->dateTime('start_time')->comment('وقت بدء الخدمة');
                $table->dateTime('end_time')->nullable()->comment('وقت انتهاء الخدمة (عند التبديل أو الخروج)');
                $table->integer('days_count')->default(1)->comment('عدد الأيام المحتسبة لهذه الخدمة');
                $table->decimal('total_cost', 12, 2)->default(0)->comment('إجمالي تكلفة هذه الخدمة');
                $table->text('reason_for_switch')->nullable()->comment('سبب التحويل السريري');
                $table->foreignId('switched_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['reservation_id', 'start_time']);
            });
        }

        // 5. جدول العلامات الحيوية والوزن والرضاعة
        if (!Schema::hasTable('nicu_vitals')) {
            Schema::create('nicu_vitals', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('reservation_id')->constrained('incubator_reservations')->cascadeOnDelete();
                $table->decimal('temperature', 4, 1)->nullable()->comment('حرارة الطفل س');
                $table->decimal('incubator_temperature', 4, 1)->nullable()->comment('حرارة الحاضنة س');
                $table->integer('heart_rate')->nullable()->comment('نبض القلب bpm');
                $table->integer('respiratory_rate')->nullable()->comment('معدل التنفس cpm');
                $table->integer('spo2')->nullable()->comment('تشبع الأكسجين %');
                $table->string('blood_pressure', 30)->nullable()->comment('ضغط الدم');
                $table->integer('weight')->nullable()->comment('الوزن بالجرام');
                $table->string('feeding_type', 100)->nullable()->comment('حليب أم، صناعي، TPN');
                $table->decimal('feeding_amount_ml', 6, 2)->nullable()->comment('كمية الرضعة بالمل');
                $table->text('feeding_notes')->nullable();
                $table->foreignId('nurse_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('recorded_at')->comment('وقت تسجيل القياس');
                $table->timestamps();

                $table->index(['reservation_id', 'recorded_at']);
            });
        }

        // 6. جدول استشارات الأطباء التخصصية (داخلية / خارجية)
        if (!Schema::hasTable('nicu_consultations')) {
            Schema::create('nicu_consultations', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('reservation_id')->constrained('incubator_reservations')->cascadeOnDelete();
                $table->enum('consultation_type', ['internal', 'external'])->default('internal')->comment('داخلي أو خارجي');
                $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete()->comment('الطبيب الداخلي');
                $table->string('external_doctor_name')->nullable()->comment('اسم الطبيب الزائر الخارجي');
                $table->string('specialty')->comment('التخصص الدقيق');
                $table->decimal('fee', 12, 2)->default(0)->comment('أجرة الاستشارة');
                $table->text('reason')->comment('سبب طلب الاستشارة');
                $table->text('findings')->nullable()->comment('فحص الطبيب المستشار');
                $table->text('recommendations')->nullable()->comment('التوصيات وخطة العلاج');
                $table->enum('status', ['requested', 'completed', 'cancelled'])->default('requested');
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 7. جدول طلبات الفحوصات والأدوية السريرية للخدج
        if (!Schema::hasTable('nicu_medical_orders')) {
            Schema::create('nicu_medical_orders', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('reservation_id')->constrained('incubator_reservations')->cascadeOnDelete();
                $table->enum('order_type', ['lab', 'radiology', 'medication'])->comment('نوع الطلب: مختبر، أشعة، علاج');
                $table->string('item_name')->comment('اسم الفحص أو الأشعة أو الدواء');
                $table->decimal('price', 12, 2)->default(0)->comment('السعر المضاف للفاتورة');
                $table->string('priority', 20)->default('urgent')->comment('الأولوية: عادي، عاجل');
                $table->text('instructions')->nullable()->comment('تعليمات الاستخدام أو متطلبات الفحص');
                $table->text('result')->nullable()->comment('النتيجة المخبرية أو تقرير الأشعة');
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
                $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('ordered_at');
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();

                $table->index(['reservation_id', 'order_type']);
                $table->index(['status', 'order_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nicu_medical_orders');
        Schema::dropIfExists('nicu_consultations');
        Schema::dropIfExists('nicu_vitals');
        Schema::dropIfExists('nicu_service_logs');

        if (Schema::hasTable('incubator_reservations')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                $table->dropForeign(['nicu_service_id']);
                $table->dropColumn([
                    'nicu_service_id',
                    'current_weight',
                    'twin_group_id',
                    'admission_source',
                    'discharge_type',
                    'camera_token',
                    'camera_active',
                ]);
            });
        }

        if (Schema::hasTable('incubators')) {
            Schema::table('incubators', function (Blueprint $table) {
                $table->dropColumn('camera_stream_url');
            });
        }

        Schema::dropIfExists('nicu_services');
    }
};

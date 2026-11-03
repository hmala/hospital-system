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
        // إسقاط الجداول بالترتيب العكسي إن وجدت لتفادي أخطاء المفاتيح الأجنبية
        Schema::dropIfExists('eye_surgeries');
        Schema::dropIfExists('eye_investigations');
        Schema::dropIfExists('eye_examinations');
        Schema::dropIfExists('eye_store_movements');
        Schema::dropIfExists('eye_store_items');
        Schema::dropIfExists('eye_invoice_items');
        Schema::dropIfExists('eye_invoices');
        Schema::dropIfExists('eye_appointments');

        // 1. استعلامات واستقبال وطابور العيون
        Schema::create('eye_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number', 50)->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('visit_type', 50)->default('consultation'); // consultation, optometry, investigation, procedure, follow_up
            $table->integer('queue_number')->default(1);
            $table->string('status', 30)->default('waiting'); // waiting, in_clinic, in_investigation, completed, cancelled
            $table->text('chief_complaint')->nullable();
            $table->string('insurance_type', 50)->default('cash'); // cash, health_insurance, moi
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // 2. كاشير وفواتير مركز العيون
        Schema::create('eye_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('eye_appointment_id')->nullable()->constrained('eye_appointments')->nullOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('patient_share', 12, 2)->default(0);
            $table->decimal('insurance_share', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('payment_method', 50)->default('cash'); // cash, card, insurance
            $table->string('insurance_type', 50)->default('cash');
            $table->string('status', 30)->default('paid'); // paid, partially_paid, refunded, pending
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('reconciled_with_hospital')->default(false); // الترحيل للخزينة الرئيسية
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'reconciled_with_hospital']);
        });

        Schema::create('eye_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eye_invoice_id')->constrained('eye_invoices')->cascadeOnDelete();
            $table->string('service_type', 50)->default('consultation'); // consultation, optometry, investigation, procedure, lens, consumable
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();
        });

        // 3. مخزن مستلزمات وعدسات العيون
        Schema::create('eye_store_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('name');
            $table->string('category', 50)->default('general_consumable'); // iol_lens, retinal_injection, viscoelastic, surgical_blade, suture, general_consumable
            $table->decimal('diopter', 4, 2)->nullable(); // للعدسات IOL: +10.00 إلى +30.00
            $table->string('model_number', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->string('unit', 50)->default('قطعة');
            $table->integer('current_stock')->default(0);
            $table->integer('min_stock_alert')->default(5);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('eye_store_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eye_store_item_id')->constrained('eye_store_items')->cascadeOnDelete();
            $table->string('movement_type', 50); // direct_purchase, hospital_transfer_in, surgery_dispense, adjustment, return
            $table->integer('quantity'); // موجب للإضافة، سالب للخصم
            $table->integer('balance_after')->default(0);
            $table->foreignId('stock_transfer_request_id')->nullable()->constrained('stock_transfer_requests')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->unsignedBigInteger('surgery_id')->nullable();
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['movement_type', 'created_at']);
        });

        // 4. استمارة فحص العيون السريرية التخصصية
        Schema::create('eye_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eye_appointment_id')->nullable()->constrained('eye_appointments')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();

            // حدة الإبصار (Visual Acuity)
            $table->string('va_od_unaided', 30)->nullable();
            $table->string('va_os_unaided', 30)->nullable();
            $table->string('va_od_corrected', 30)->nullable();
            $table->string('va_os_corrected', 30)->nullable();
            $table->string('va_od_pinhole', 30)->nullable();
            $table->string('va_os_pinhole', 30)->nullable();

            // قياس الانكسار والنظارة (Refraction)
            $table->decimal('ref_od_sphere', 4, 2)->nullable();
            $table->decimal('ref_od_cylinder', 4, 2)->nullable();
            $table->integer('ref_od_axis')->nullable();
            $table->decimal('ref_od_add', 4, 2)->nullable();

            $table->decimal('ref_os_sphere', 4, 2)->nullable();
            $table->decimal('ref_os_cylinder', 4, 2)->nullable();
            $table->integer('ref_os_axis')->nullable();
            $table->decimal('ref_os_add', 4, 2)->nullable();
            $table->decimal('pupillary_distance', 4, 1)->nullable(); // المسافة بين الحدقتين PD

            // ضغط العين (Intraocular Pressure - IOP)
            $table->decimal('iop_od', 4, 1)->nullable();
            $table->decimal('iop_os', 4, 1)->nullable();
            $table->string('iop_method', 50)->default('goldmann'); // goldmann, air_puff, tonopen, icare
            $table->time('iop_time')->nullable();

            // فحص المصباح الشقي (Slit Lamp)
            $table->boolean('is_od_wnl')->default(false); // Within Normal Limits
            $table->boolean('is_os_wnl')->default(false);
            $table->text('lids_adnexa_od')->nullable();
            $table->text('lids_adnexa_os')->nullable();
            $table->text('conjunctiva_sclera_od')->nullable();
            $table->text('conjunctiva_sclera_os')->nullable();
            $table->text('cornea_od')->nullable();
            $table->text('cornea_os')->nullable();
            $table->text('anterior_chamber_od')->nullable();
            $table->text('anterior_chamber_os')->nullable();
            $table->text('iris_pupil_od')->nullable();
            $table->text('iris_pupil_os')->nullable();
            $table->text('lens_od')->nullable();
            $table->text('lens_os')->nullable();

            // فحص قاع العين والشبكية (Fundus & Posterior Segment)
            $table->text('vitreous_od')->nullable();
            $table->text('vitreous_os')->nullable();
            $table->string('cup_to_disc_ratio_od', 20)->nullable();
            $table->string('cup_to_disc_ratio_os', 20)->nullable();
            $table->text('macula_od')->nullable();
            $table->text('macula_os')->nullable();
            $table->text('retina_periphery_od')->nullable();
            $table->text('retina_periphery_os')->nullable();

            // التشخيص والقرار العلاجي
            $table->text('diagnosis')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->text('management_plan')->nullable();
            $table->boolean('has_glasses_prescription')->default(false);
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });

        // 5. فحوصات الأجهزة المتخصصة (OCT, Visual Field, Biometry, Pentacam)
        Schema::create('eye_investigations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('eye_appointment_id')->nullable()->constrained('eye_appointments')->nullOnDelete();
            $table->string('investigation_type', 50); // oct_macula, oct_optic_disc, visual_field, pentacam_topography, biometry_iol, fundus_photography, b_scan
            $table->string('eye_target', 10)->default('OU'); // OD, OS, OU
            $table->string('status', 30)->default('requested'); // requested, in_progress, completed, cancelled
            $table->text('findings')->nullable();
            $table->text('conclusion')->nullable();
            $table->json('measurement_data')->nullable(); // قياسات رقمية منظمة
            $table->string('attachment_path')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();

            $table->index(['investigation_type', 'status']);
        });

        // 6. عمليات وإجراءات وحقن العيون
        Schema::create('eye_surgeries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('eye_appointment_id')->nullable()->constrained('eye_appointments')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('procedure_name', 150); // Phaco + IOL, Intravitreal Injection, Trabeculectomy, LASIK
            $table->string('target_eye', 10)->default('OD'); // OD, OS, OU
            $table->string('anesthesia_type', 50)->default('topical'); // topical, local_peribulbar, general
            $table->foreignId('iol_item_id')->nullable()->constrained('eye_store_items')->nullOnDelete();
            $table->decimal('iol_power', 4, 2)->nullable();
            $table->string('iol_serial_number', 100)->nullable();
            $table->string('injection_drug', 100)->nullable(); // Eylea, Lucentis, Avastin
            $table->string('injection_dose', 50)->nullable();
            $table->text('operative_notes')->nullable();
            $table->text('complications')->nullable();
            $table->text('postop_plan')->nullable();
            $table->string('status', 30)->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->date('surgery_date');
            $table->timestamps();

            $table->index(['status', 'surgery_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eye_surgeries');
        Schema::dropIfExists('eye_investigations');
        Schema::dropIfExists('eye_examinations');
        Schema::dropIfExists('eye_store_movements');
        Schema::dropIfExists('eye_store_items');
        Schema::dropIfExists('eye_invoice_items');
        Schema::dropIfExists('eye_invoices');
        Schema::dropIfExists('eye_appointments');
    }
};

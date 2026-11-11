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
        // 1. جدول خدمات وباقات وتسعيرات العناية المركزة للكبار (Dynamic CCU Tariffs & Services)
        if (! Schema::hasTable('ccu_services')) {
            Schema::create('ccu_services', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('official_claim_name')->nullable();
                $table->string('service_type')->default('package'); // package (باقة يومية شاملة), addon (خدمة مخصصة منفصلة)
                $table->decimal('daily_fee', 12, 2)->default(0); // التسعيرة اليومية للكاش (Cash Daily Rate)
                $table->decimal('hi_price', 12, 2)->default(0); // التسعيرة اليومية الرسمية للضمان الصحي (HIA Rate)
                $table->boolean('is_hi_active')->default(true);
                $table->boolean('include_bed_fee')->default(true); // true = شامل السرير، false = تسعيرة السرير تضاف منفصلة
                $table->boolean('is_bundled_lab')->default(false); // هل التحاليل مشمولة باليومية (تظهر صفر للمريض)
                $table->boolean('is_bundled_radiology')->default(false); // هل الأشعة مشمولة باليومية
                $table->boolean('is_bundled_medications')->default(false);
                $table->decimal('hotel_fee', 12, 2)->default(0);
                $table->decimal('nursing_fee', 12, 2)->default(0);
                $table->decimal('device_fee', 12, 2)->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. جدول شرائح نسب التغطية الداينمك حسب مدة الرقود (Dynamic Stay Brackets)
        if (! Schema::hasTable('ccu_stay_brackets')) {
            Schema::create('ccu_stay_brackets', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('policy_category')->default('ALL'); // ALL أو E أو H ...
                $table->integer('min_day');
                $table->integer('max_day')->nullable();
                $table->decimal('hia_percent', 5, 2); // نسبة تحمل الضمان الصحي (%)
                $table->decimal('patient_percent', 5, 2); // نسبة تحمل المريض (%)
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. جدول الإعدادات العامة الداينميكية للعناية المركزة (Global Dynamic Settings)
        if (! Schema::hasTable('ccu_settings')) {
            Schema::create('ccu_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('label');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 4. توسيع جدول إضبارة العناية المركزة لدعم التسعير والباقات والضمان
        Schema::table('icu_admissions', function (Blueprint $table) {
            if (Schema::hasColumn('icu_admissions', 'primary_diagnosis')) {
                $table->string('primary_diagnosis')->nullable()->change();
            }
            if (! Schema::hasColumn('icu_admissions', 'ccu_service_id')) {
                $table->foreignId('ccu_service_id')->nullable()->after('doctor_id')->constrained('ccu_services')->nullOnDelete();
            }
            if (! Schema::hasColumn('icu_admissions', 'insurance_type')) {
                $table->string('insurance_type')->default('none')->after('is_insured'); // none, hi, moi
            }
            if (! Schema::hasColumn('icu_admissions', 'policy_category')) {
                $table->string('policy_category')->default('E')->after('insurance_type');
            }
            if (! Schema::hasColumn('icu_admissions', 'pricing_mode')) {
                $table->string('pricing_mode')->default('package')->after('policy_category'); // package, bed_plus_services
            }
            if (! Schema::hasColumn('icu_admissions', 'patient_share_amount')) {
                $table->decimal('patient_share_amount', 12, 2)->default(0)->after('pricing_mode');
            }
            if (! Schema::hasColumn('icu_admissions', 'insurance_share_amount')) {
                $table->decimal('insurance_share_amount', 12, 2)->default(0)->after('patient_share_amount');
            }
            if (! Schema::hasColumn('icu_admissions', 'bundled_reference_amount')) {
                $table->decimal('bundled_reference_amount', 12, 2)->default(0)->after('insurance_share_amount');
            }
            if (! Schema::hasColumn('icu_admissions', 'hia_collected_amount')) {
                $table->decimal('hia_collected_amount', 12, 2)->default(0)->after('bundled_reference_amount');
            }
            if (! Schema::hasColumn('icu_admissions', 'coverage_summary')) {
                $table->json('coverage_summary')->nullable()->after('hia_collected_amount');
            }
        });

        // 5. جدول سجل باقات وخدمات العناية أثناء الرقود (Switch & Addon Service Logs)
        if (! Schema::hasTable('ccu_service_logs')) {
            Schema::create('ccu_service_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
                $table->foreignId('ccu_service_id')->nullable()->constrained('ccu_services')->nullOnDelete();
                $table->string('log_category')->default('stay_package'); // stay_package (الإقامة الأساسية), addon_service (خدمة مخصصة منفصلة)
                $table->string('service_name_snapshot')->nullable();
                $table->decimal('daily_fee', 12, 2)->default(0);
                $table->decimal('bed_daily_fee', 12, 2)->default(0);
                $table->dateTime('start_time');
                $table->dateTime('end_time')->nullable();
                $table->integer('days_count')->default(1);
                $table->decimal('total_cost', 12, 2)->default(0);
                $table->decimal('patient_share', 12, 2)->default(0);
                $table->decimal('insurance_share', 12, 2)->default(0);
                $table->decimal('coverage_percentage', 5, 2)->default(0);
                $table->string('reason_for_switch')->nullable();
                $table->foreignId('switched_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 6. توسيع جدول طلبات المختبر والأشعة في العناية لدعم السعر الأصلي والتبديل الفوري بضغطة زر
        Schema::table('icu_medical_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('icu_medical_orders', 'original_price')) {
                $table->decimal('original_price', 12, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('icu_medical_orders', 'billing_mode')) {
                $table->string('billing_mode')->default('auto')->after('original_price'); // auto, bundled, extra
            }
        });

        // 7. توسيع جدول استشارات العناية لدعم السعر الأصلي والتبديل الفوري
        if (Schema::hasTable('ccu_consultations')) {
            Schema::table('ccu_consultations', function (Blueprint $table) {
                if (! Schema::hasColumn('ccu_consultations', 'original_fee')) {
                    $table->decimal('original_fee', 12, 2)->nullable()->after('fee');
                }
                if (! Schema::hasColumn('ccu_consultations', 'billing_mode')) {
                    $table->string('billing_mode')->default('auto')->after('original_fee'); // auto, bundled, extra
                }
            });
        }

        // 8. جدول مدفوعات وسندات قبض واسترجاع العناية المركزة (CCU Payments)
        if (! Schema::hasTable('ccu_payments')) {
            Schema::create('ccu_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->string('receipt_number')->unique();
                $table->string('payment_type')->default('interim'); // deposit, interim, final_settlement, refund
                $table->decimal('amount', 12, 2);
                $table->string('payment_method')->default('cash'); // cash, card
                $table->string('financial_status')->default('paid'); // paid, cancelled
                $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ccu_payments');

        if (Schema::hasTable('ccu_consultations')) {
            Schema::table('ccu_consultations', function (Blueprint $table) {
                $cols = array_filter(['original_fee', 'billing_mode'], fn ($c) => Schema::hasColumn('ccu_consultations', $c));
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::table('icu_medical_orders', function (Blueprint $table) {
            $cols = array_filter(['original_price', 'billing_mode'], fn ($c) => Schema::hasColumn('icu_medical_orders', $c));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::dropIfExists('ccu_service_logs');

        Schema::table('icu_admissions', function (Blueprint $table) {
            if (Schema::hasColumn('icu_admissions', 'ccu_service_id')) {
                $table->dropConstrainedForeignId('ccu_service_id');
            }
            $cols = array_filter([
                'insurance_type',
                'policy_category',
                'pricing_mode',
                'patient_share_amount',
                'insurance_share_amount',
                'bundled_reference_amount',
                'hia_collected_amount',
                'coverage_summary',
            ], fn ($c) => Schema::hasColumn('icu_admissions', $c));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::dropIfExists('ccu_settings');
        Schema::dropIfExists('ccu_stay_brackets');
        Schema::dropIfExists('ccu_services');
    }
};

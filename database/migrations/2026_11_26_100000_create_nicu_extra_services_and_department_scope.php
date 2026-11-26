<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hospital_extra_services') && ! Schema::hasColumn('hospital_extra_services', 'department')) {
            Schema::table('hospital_extra_services', function (Blueprint $table) {
                $table->string('department', 30)->default('general')->after('category')->index();
            });
        }

        if (Schema::hasTable('hospital_extra_services')) {
            $nicuDefaultServices = [
                // سريرية وإجراءات للخدج
                ['name' => 'علاج ضوئي إضافي (Phototherapy)', 'category' => 'clinical', 'department' => 'nicu', 'default_price' => 25000.00],
                ['name' => 'تخطيط قلب للطفل (ECG)', 'category' => 'clinical', 'department' => 'nicu', 'default_price' => 15000.00],
                ['name' => 'فحص سمع للوليد (Hearing Screening)', 'category' => 'clinical', 'department' => 'nicu', 'default_price' => 20000.00],
                ['name' => 'فحص شبكية العين للخدج (ROP)', 'category' => 'clinical', 'department' => 'nicu', 'default_price' => 35000.00],
                ['name' => 'سحب غازات الدم (ABG)', 'category' => 'clinical', 'department' => 'nicu', 'default_price' => 15000.00],
                ['name' => 'تبديل كانيولا / قسطرة وريدية إضافية', 'category' => 'clinical', 'department' => 'nicu', 'default_price' => 10000.00],

                // عناية ومستلزمات للطفل
                ['name' => 'مستلزمات عناية ونظافة للطفل', 'category' => 'care', 'department' => 'nicu', 'default_price' => 15000.00],
                ['name' => 'حليب خاص / تغذية إضافية للخدج', 'category' => 'care', 'department' => 'nicu', 'default_price' => 20000.00],
                ['name' => 'مستلزمات أوكسجين وكمام CPAP', 'category' => 'care', 'department' => 'nicu', 'default_price' => 25000.00],
                ['name' => 'أنبوب تغذية / قسطرة سرة إضافية', 'category' => 'care', 'department' => 'nicu', 'default_price' => 15000.00],

                // إدارية ووثائق
                ['name' => 'أجور فتح طبلة خدج', 'category' => 'administrative', 'department' => 'nicu', 'default_price' => 10000.00],
                ['name' => 'بيان ولادة للخديج', 'category' => 'administrative', 'department' => 'nicu', 'default_price' => 15000.00],
                ['name' => 'تقرير طبي مفصل للطفل', 'category' => 'administrative', 'department' => 'nicu', 'default_price' => 15000.00],
                ['name' => 'خدمات إدارية للخدج', 'category' => 'administrative', 'department' => 'nicu', 'default_price' => 10000.00],

                // فندقية ومرافقين
                ['name' => 'مبيت أم / مرافق طفل', 'category' => 'accommodation', 'department' => 'nicu', 'default_price' => 25000.00],
                ['name' => 'خدمات فندقية ومرافقين (خدج)', 'category' => 'accommodation', 'department' => 'nicu', 'default_price' => 20000.00],

                // غرامات ومخالفات
                ['name' => 'غرامة تدخين (قسم الخدج)', 'category' => 'fine', 'department' => 'nicu', 'default_price' => 25000.00],
                ['name' => 'غرامة مخالفة تعليمات الزيارة أو إدخال طعام', 'category' => 'fine', 'department' => 'nicu', 'default_price' => 25000.00],
            ];

            foreach ($nicuDefaultServices as $srv) {
                DB::table('hospital_extra_services')->updateOrInsert(
                    ['name' => $srv['name'], 'department' => $srv['department']],
                    [
                        'category' => $srv['category'],
                        'default_price' => $srv['default_price'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        if (! Schema::hasTable('nicu_extra_services')) {
            Schema::create('nicu_extra_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('incubator_reservation_id')->constrained('incubator_reservations')->cascadeOnDelete();
                $table->foreignId('service_id')->nullable()->constrained('hospital_extra_services')->nullOnDelete();
                $table->string('name');
                $table->string('category', 40)->default('general');
                $table->decimal('price', 12, 2)->default(0.00);
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->string('notes', 500)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nicu_extra_services');

        if (Schema::hasTable('hospital_extra_services') && Schema::hasColumn('hospital_extra_services', 'department')) {
            DB::table('hospital_extra_services')->where('department', 'nicu')->delete();
            Schema::table('hospital_extra_services', function (Blueprint $table) {
                $table->dropIndex(['department']);
                $table->dropColumn('department');
            });
        }
    }
};

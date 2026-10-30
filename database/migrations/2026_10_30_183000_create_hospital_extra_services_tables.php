<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. جدول الكتالوج المرجعي للخدمات الإضافية والفندقية
        if (! Schema::hasTable('hospital_extra_services')) {
            Schema::create('hospital_extra_services', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('category')->default('general'); // accommodation, medical, administrative, decorating, fine
                $table->decimal('default_price', 12, 2)->default(0.00);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // بذور الخدمات الأولية الظاهرة في صور النظام
            $defaultServices = [
                ['name' => 'تخطيط قلب', 'category' => 'medical', 'default_price' => 15000.00],
                ['name' => 'خدمة مرافق مريض مبيت', 'category' => 'accommodation', 'default_price' => 25000.00],
                ['name' => 'رقود اضافي', 'category' => 'accommodation', 'default_price' => 50000.00],
                ['name' => 'رقود مؤقت 50', 'category' => 'accommodation', 'default_price' => 50000.00],
                ['name' => 'خدمات فندقه 30', 'category' => 'accommodation', 'default_price' => 30000.00],
                ['name' => 'خدمات فندقه نواظير', 'category' => 'accommodation', 'default_price' => 25000.00],
                ['name' => 'اجور فتح طبلة', 'category' => 'administrative', 'default_price' => 10000.00],
                ['name' => 'بيان ولادة 2', 'category' => 'administrative', 'default_price' => 15000.00],
                ['name' => 'خدمات ادارية', 'category' => 'administrative', 'default_price' => 10000.00],
                ['name' => 'مواد مصروفة', 'category' => 'administrative', 'default_price' => 0.00],
                ['name' => 'اجور تزيين غرفة', 'category' => 'decorating', 'default_price' => 50000.00],
                ['name' => 'Decorating50', 'category' => 'decorating', 'default_price' => 50000.00],
                ['name' => 'Decorating100', 'category' => 'decorating', 'default_price' => 100000.00],
                ['name' => 'طبيب اطفال', 'category' => 'medical', 'default_price' => 25000.00],
                ['name' => 'Patient escort', 'category' => 'accommodation', 'default_price' => 25000.00],
                ['name' => 'غرامة تدخين', 'category' => 'fine', 'default_price' => 25000.00],
                ['name' => 'غرامة ادخال اكل الى الغرفة', 'category' => 'fine', 'default_price' => 25000.00],
            ];

            foreach ($defaultServices as $srv) {
                DB::table('hospital_extra_services')->insert(array_merge($srv, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 2. جدول الخدمات الإضافية الملحقة بكل عملية جراحية
        if (! Schema::hasTable('surgery_extra_services')) {
            Schema::create('surgery_extra_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
                $table->foreignId('service_id')->nullable()->constrained('hospital_extra_services')->nullOnDelete();
                $table->string('name');
                $table->decimal('price', 12, 2)->default(0.00);
                $table->integer('quantity')->default(1);
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->string('notes', 500)->nullable();
                $table->string('payment_status', 30)->default('pending'); // pending, paid, cancelled
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgery_extra_services');
        Schema::dropIfExists('hospital_extra_services');
    }
};

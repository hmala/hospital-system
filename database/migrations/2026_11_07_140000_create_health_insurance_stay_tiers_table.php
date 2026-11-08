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
        Schema::create('health_insurance_stay_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('day_from');
            $table->unsignedInteger('day_to');
            $table->decimal('copay_percentage', 5, 2)->default(0.00); // 0% to 100%
            $table->decimal('standard_room_daily_price', 12, 2)->default(200000.00);
            $table->string('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // إدخال الشرائح الخمس الافتراضية المعتمدة
        $tiers = [
            [
                'name' => 'الليلة الأولى',
                'day_from' => 1,
                'day_to' => 1,
                'copay_percentage' => 0.00,
                'standard_room_daily_price' => 200000.00,
                'notes' => 'مشمول بالكامل ضمن باقة وتكلفة العملية الجراحية (مجاني)',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'الليلة الثانية',
                'day_from' => 2,
                'day_to' => 2,
                'copay_percentage' => 10.00,
                'standard_room_daily_price' => 200000.00,
                'notes' => 'تحمل تشجيعي لأول ليلة إضافية (10% = 20,000 د.ع)',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'إقامة اعتيادية (أيام 3 إلى 8)',
                'day_from' => 3,
                'day_to' => 8,
                'copay_percentage' => 25.00,
                'standard_room_daily_price' => 200000.00,
                'notes' => 'إقامة ومتابعة طبية اعتيادية (25% = 50,000 د.ع / يوم)',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'إقامة ممتدة (أيام 9 إلى 13)',
                'day_from' => 9,
                'day_to' => 13,
                'copay_percentage' => 50.00,
                'standard_room_daily_price' => 200000.00,
                'notes' => 'حالات علاجية تستلزم بقاءً إضافياً (50% = 100,000 د.ع / يوم)',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'إقامة طويلة (يوم 14 فما بعد)',
                'day_from' => 14,
                'day_to' => 999,
                'copay_percentage' => 75.00,
                'standard_room_daily_price' => 200000.00,
                'notes' => 'إقامة علاجية طويلة وتأهيل مستمر (75% = 150,000 د.ع / يوم)',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('health_insurance_stay_tiers')->insert($tiers);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_insurance_stay_tiers');
    }
};

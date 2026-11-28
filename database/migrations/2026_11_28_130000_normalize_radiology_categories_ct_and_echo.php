<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. توحيد تصنيف وتفعيل كافة فحوصات المفراس (CT)
        DB::table('radiology_types')
            ->where(function ($q) {
                $q->where('name', 'like', '%مفراس%')
                    ->orWhere('name', 'like', '%CT%')
                    ->orWhere('description', 'like', '%CT%')
                    ->orWhere('subcategory', 'like', '%مفراس%')
                    ->orWhere('main_category', 'like', '%مفراس%')
                    ->orWhere('main_category', 'like', '%مقطعية%')
                    ->orWhere('code', 'like', 'CT-%')
                    ->orWhere('code', 'like', 'RAD-2026-00%');
            })
            ->update([
                'main_category' => 'مفراس',
                'subcategory' => 'مفراس',
                'is_active' => true,
                'updated_at' => now(),
            ]);

        // 2. توحيد تصنيف وتفعيل كافة فحوصات الإيكو (Echocardiography)
        DB::table('radiology_types')
            ->where(function ($q) {
                $q->where('name', 'like', '%إيكو%')
                    ->orWhere('name', 'like', '%ايكو%')
                    ->orWhere('name', 'like', '%Echo%')
                    ->orWhere('description', 'like', '%Echo%')
                    ->orWhere('subcategory', 'like', '%إيكو%')
                    ->orWhere('subcategory', 'like', '%ايكو%')
                    ->orWhere('main_category', 'like', '%إيكو%')
                    ->orWhere('main_category', 'like', '%ايكو%')
                    ->orWhere('main_category', 'like', '%تخطيط القلب%')
                    ->orWhere('code', 'like', 'ECHO-%')
                    ->orWhere('code', 'RAD-2026-058')
                    ->orWhere('code', 'RAD-2026-059');
            })
            ->update([
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'is_active' => true,
                'updated_at' => now(),
            ]);

        // 3. توحيد تصنيف وتفعيل كافة فحوصات الرنين المغناطيسي (MRI)
        DB::table('radiology_types')
            ->where(function ($q) {
                $q->where('name', 'like', '%رنين%')
                    ->orWhere('name', 'like', '%MRI%')
                    ->orWhere('description', 'like', '%MRI%')
                    ->orWhere('subcategory', 'like', '%رنين%')
                    ->orWhere('main_category', 'like', '%رنين%')
                    ->orWhere('code', 'like', 'MRI-%');
            })
            ->update([
                'main_category' => 'الرنين',
                'subcategory' => 'الرنين',
                'is_active' => true,
                'updated_at' => now(),
            ]);

        // 4. توحيد تصنيف وتفعيل كافة فحوصات السونار (Ultrasound) باستثناء الإيكو
        DB::table('radiology_types')
            ->where(function ($q) {
                $q->where('name', 'like', '%سونار%')
                    ->orWhere('name', 'like', '%دوبلر%')
                    ->orWhere('description', 'like', '%Ultrasound%')
                    ->orWhere('subcategory', 'like', '%سونار%')
                    ->orWhere('main_category', 'like', '%سونار%')
                    ->orWhere('code', 'like', 'US-%');
            })
            ->where('name', 'not like', '%إيكو%')
            ->where('name', 'not like', '%ايكو%')
            ->update([
                'main_category' => 'سونار',
                'subcategory' => 'سونار',
                'is_active' => true,
                'updated_at' => now(),
            ]);

        // 5. توحيد تصنيف وتفعيل كافة فحوصات الأشعة العادية (X-Ray) باستثناء المفراس
        DB::table('radiology_types')
            ->where(function ($q) {
                $q->where('name', 'like', '%أشعة%')
                    ->orWhere('name', 'like', '%اشعة%')
                    ->orWhere('subcategory', 'like', '%أشعة%')
                    ->orWhere('subcategory', 'like', '%اشعة%')
                    ->orWhere('main_category', 'like', '%أشعة%')
                    ->orWhere('main_category', 'like', '%اشعة%')
                    ->orWhere('code', 'like', 'XR-%');
            })
            ->where('name', 'not like', '%مقطعية%')
            ->where('name', 'not like', '%مفراس%')
            ->update([
                'main_category' => 'أشعة',
                'subcategory' => 'أشعة',
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasInsuranceCols = Schema::hasColumn('radiology_types', 'hi_price');

        $echoServices = [
            [
                'code' => 'ECHO-001',
                'name' => 'فحص إيكو القلب اعتيادي (Echocardiography Routine)',
                'description' => 'Echocardiogram Routine',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 60000,
                'estimated_duration' => 20,
            ],
            [
                'code' => 'ECHO-002',
                'name' => 'فحص إيكو القلب استدعاء طارئ (Echocardiography Emergency On-Call)',
                'description' => 'Echocardiogram Emergency On-Call',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 60000,
                'estimated_duration' => 20,
            ],
            [
                'code' => 'ECHO-003',
                'name' => 'فحص إيكو دوبلر ملون للقلب والأوعية (Color Doppler Echo)',
                'description' => 'Color Doppler Echocardiography',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 75000,
                'estimated_duration' => 25,
            ],
            [
                'code' => 'ECHO-004',
                'name' => 'إيكو القلب عبر المريء (TEE - Transesophageal Echocardiography)',
                'description' => 'Transesophageal Echocardiogram',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 120000,
                'estimated_duration' => 40,
            ],
            [
                'code' => 'ECHO-005',
                'name' => 'إيكو القلب للأطفال والتشوهات الولادية (Pediatric Echocardiography)',
                'description' => 'Pediatric Echocardiogram',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 75000,
                'estimated_duration' => 25,
            ],
            [
                'code' => 'ECHO-006',
                'name' => 'إيكو القلب مع الجهد الدوائي (Stress Echocardiography)',
                'description' => 'Stress Echocardiogram',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 100000,
                'estimated_duration' => 35,
            ],
            [
                'code' => 'RAD-2026-058',
                'name' => 'فحص إيكو القلب اعتيادي (Echocardiography)',
                'description' => 'Echocardiogram Routine',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 60000,
                'estimated_duration' => 20,
            ],
            [
                'code' => 'RAD-2026-059',
                'name' => 'فحص إيكو القلب استدعاء طارئ (Echocardiography On-Call)',
                'description' => 'Echocardiogram Emergency On-Call',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 60000,
                'estimated_duration' => 20,
            ],
            [
                'code' => 'ECHO-ROUTINE',
                'name' => 'فحص إيكو القلب اعتيادي (ECHO-ROUTINE)',
                'description' => 'Echocardiogram Routine',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 60000,
                'estimated_duration' => 20,
            ],
            [
                'code' => 'ECHO-ONCALL',
                'name' => 'فحص إيكو القلب استدعاء طارئ (ECHO-ONCALL)',
                'description' => 'Echocardiogram Emergency On-Call',
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
                'base_price' => 60000,
                'estimated_duration' => 20,
            ],
        ];

        foreach ($echoServices as $service) {
            $data = [
                'main_category' => $service['main_category'],
                'subcategory' => $service['subcategory'],
                'name' => $service['name'],
                'description' => $service['description'],
                'base_price' => $service['base_price'],
                'estimated_duration' => $service['estimated_duration'],
                'requires_contrast' => false,
                'requires_preparation' => false,
                'is_active' => true,
                'updated_at' => now(),
            ];

            if ($hasInsuranceCols) {
                $data['moi_price'] = $service['base_price'];
                $data['is_moi_active'] = true;
                $data['hi_price'] = $service['base_price'];
                $data['is_hi_active'] = true;
            }

            DB::table('radiology_types')->updateOrInsert(
                ['code' => $service['code']],
                $data
            );
        }

        // تحديث أي فحوصات إيكو أخرى موجودة مسبقاً وتفعيلها وتصنيفها
        DB::table('radiology_types')
            ->where(function ($q) {
                $q->where('name', 'like', '%إيكو%')
                    ->orWhere('name', 'like', '%ايكو%')
                    ->orWhere('name', 'like', '%Echo%')
                    ->orWhere('subcategory', 'like', '%إيكو%')
                    ->orWhere('subcategory', 'like', '%ايكو%')
                    ->orWhere('main_category', 'like', '%إيكو%')
                    ->orWhere('main_category', 'like', '%ايكو%')
                    ->orWhere('main_category', 'like', '%تخطيط القلب%');
            })
            ->update([
                'main_category' => 'إيكو',
                'subcategory' => 'إيكو',
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

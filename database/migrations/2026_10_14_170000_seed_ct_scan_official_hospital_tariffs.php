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
        $services = [
            // منطقة البطن والحوض (Abdomen & Pelvis)
            [
                'name' => 'مفراس البطن والحوض مع صبغة وريدية وفموية (CT Abdomen & Pelvis oral + I.V)',
                'code' => 'CT-ABD-PEL-ORAL-IV',
                'description' => 'CT abdomen and Pelvis with oral and I.V contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس البطن والحوض مع صبغة وريدية (CT Abdomen & Pelvis I.V)',
                'code' => 'CT-ABD-PEL-IV',
                'description' => 'CT abdomen and Pelvis with I.V contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس البطن والحوض بدون تباين (CT Abdomen & Pelvis native)',
                'code' => 'CT-ABD-PEL-PLAIN',
                'description' => 'CT Abdomen and Pelvis without contrast',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس البطن مع صبغة متحركة متعدد الأطوار (CT Dynamic Phase Liver)',
                'code' => 'CT-ABD-DYNAMIC',
                'description' => 'CT Phase Liver / Abdomen Dynamic contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الحوض بدون صبغة (CT Pelvis native)',
                'code' => 'CT-PELVIS-PLAIN',
                'description' => 'CT Pelvis without contrast',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الحوض مع الصبغة (CT Pelvis with contrast)',
                'code' => 'CT-PELVIS-CONTRAST',
                'description' => 'CT Pelvis with contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس البنكرياس متعدد الأطوار مع الصبغة (CT Pancreas protocol)',
                'code' => 'CT-PANCREAS-PHASE',
                'description' => 'CT Pancreas protocol with contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الجهاز البولي الملون (CT Urography with contrast)',
                'code' => 'CT-UROGRAPHY',
                'description' => 'CT Urography with contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],

            // منطقة الرأس والدماغ والجيوب (Brain, Head, Sinuses, Ears)
            [
                'name' => 'مفراس الدماغ بدون صبغة (Brain CT native)',
                'code' => 'CT-BRAIN-PLAIN',
                'description' => 'Brain CT without contrast',
                'base_price' => 100000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الدماغ مع الصبغة (Brain CT with contrast)',
                'code' => 'CT-BRAIN-CONTRAST',
                'description' => 'Brain CT with contrast',
                'base_price' => 125000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الجيوب الأنفية بدون صبغة (CT Paranasal Sinuses)',
                'code' => 'CT-PNS-PLAIN',
                'description' => 'CT Paranasal Sinuses (PNS) without contrast',
                'base_price' => 50000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الجيوب الأنفية مع الصبغة (CT PNS with contrast)',
                'code' => 'CT-PNS-CONTRAST',
                'description' => 'CT Paranasal Sinuses with contrast',
                'base_price' => 75000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الوجه والفكين ثلاثي الأبعاد (CT Face/Orbits/Sinus 3D)',
                'code' => 'CT-FACE-3D',
                'description' => 'CT Face/Orbits/Sinus 3D reconstruction',
                'base_price' => 100000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الأذن الوسطى والداخلية (CT Temporal Bones IAC)',
                'code' => 'CT-TEMPORAL-BONES',
                'description' => 'CT Temporal Bones (IAC) Middle & Inner ear',
                'base_price' => 100000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الأذن الوسطى بدون صبغة',
                'code' => 'CT-EAR-PLAIN',
                'description' => 'CT Middle Ear without contrast',
                'base_price' => 50000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الأذن الوسطى مع الصبغة',
                'code' => 'CT-EAR-CONTRAST',
                'description' => 'CT Middle Ear with contrast',
                'base_price' => 75000.00,
                'requires_contrast' => true,
            ],

            // منطقة الرقبة والصدر (Neck & Chest)
            [
                'name' => 'مفراس الرقبة بدون صبغة (CT Neck plain)',
                'code' => 'CT-NECK-PLAIN',
                'description' => 'CT Neck without contrast',
                'base_price' => 100000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الرقبة مع الصبغة (CT Neck with contrast)',
                'code' => 'CT-NECK-CONTRAST',
                'description' => 'CT Neck with contrast',
                'base_price' => 150000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الصدر بدون صبغة (Chest CT plain)',
                'code' => 'CT-CHEST-PLAIN',
                'description' => 'Chest CT without contrast',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الصدر مع الصبغة (Chest CT with contrast)',
                'code' => 'CT-CHEST-CONTRAST',
                'description' => 'Chest CT with contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الرقبة والصدر بدون صبغة (CT Neck & Chest plain)',
                'code' => 'CT-NECK-CHEST-PLAIN',
                'description' => 'CT Neck and chest without contrast',
                'base_price' => 175000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الرقبة والصدر مع الصبغة (CT Neck & Chest contrast)',
                'code' => 'CT-NECK-CHEST-CONTRAST',
                'description' => 'CT Neck and chest contrast',
                'base_price' => 250000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الرقبة والصدر والبطن بدون تباين',
                'code' => 'CT-NECK-CHEST-ABD-PLAIN',
                'description' => 'CT Neck, chest and abdomen without contrast',
                'base_price' => 250000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الرقبة والصدر والبطن والحوض (مسح أورام شامل) مع الصبغة',
                'code' => 'CT-PAN-SCAN-ONCOLOGY',
                'description' => 'CTA Neck/Chest/Abdomen/Pelvis Oncology staging',
                'base_price' => 300000.00,
                'requires_contrast' => true,
            ],

            // تلوين الشرايين والأوعية بالمفراس (CT Angiography - CTA)
            [
                'name' => 'مفراس شرايين الدماغ الملون (Brain CTA)',
                'code' => 'CT-ANGIO-BRAIN',
                'description' => 'Brain CTA (Cerebral Angiography)',
                'base_price' => 300000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الشريان السباتي والرقبة الملون (Carotid CTA)',
                'code' => 'CT-ANGIO-CAROTID',
                'description' => 'Carotid CTA',
                'base_price' => 300000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الشريان الأبهر الصدري والبطني (Aorta CTA)',
                'code' => 'CT-ANGIO-AORTA',
                'description' => 'Aorta CTA (Thoracic and Abdominal)',
                'base_price' => 300000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الشريان الرئوي (Pulmonary Embolism CTA)',
                'code' => 'CT-ANGIO-PULMONARY',
                'description' => 'Pulmonary Embolism CTA',
                'base_price' => 300000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس شرايين الطرف السفلي (Lower Extremity CTA runoff)',
                'code' => 'CT-ANGIO-LOWER-EXT',
                'description' => 'Lower Extremity (CTA runoff)',
                'base_price' => 300000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس شرايين الطرف العلوي (Upper Extremity CTA runoff)',
                'code' => 'CT-ANGIO-UPPER-EXT',
                'description' => 'Upper Extremity (CTA runoff)',
                'base_price' => 300000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'تلوين الشرايين بالمفراس (CT Angiography عام)',
                'code' => 'CT-ANGIO-GENERAL',
                'description' => 'CT Angiography General',
                'base_price' => 150000.00,
                'requires_contrast' => true,
            ],

            // مفراس القلب والشرايين التاجية (Coronary CT & Calcium Score)
            [
                'name' => 'مفراس شرايين القلب التاجية (Coronary CTA)',
                'code' => 'CT-CORONARY-ANGIO',
                'description' => 'Coronary CT Angiography',
                'base_price' => 300000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس حساب نسبة الكالسيوم لشرايين القلب (Calcium Scoring CT)',
                'code' => 'CT-CALCIUM-SCORING',
                'description' => 'Calcium Scoring CT',
                'base_price' => 50000.00,
                'requires_contrast' => false,
            ],

            // العمود الفقري (Spine)
            [
                'name' => 'مفراس الفقرات العنقية بدون صبغة (Cervical Spine CT)',
                'code' => 'CT-SPINE-CERVICAL',
                'description' => 'Cervical Spine CT without contrast',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الفقرات الصدرية بدون صبغة (Thoracic Spine CT)',
                'code' => 'CT-SPINE-THORACIC',
                'description' => 'Thoracic Spine CT without contrast',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الفقرات القطنية والعجزية (Lumbar Spine CT L.S.S)',
                'code' => 'CT-SPINE-LUMBAR',
                'description' => 'Lumbar Spine CT (L.S.S)',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],

            // العظام والمفاصل والأطراف (Bones & Extremities)
            [
                'name' => 'مفراس مفصل الكتف بدون صبغة (Shoulder CT plain)',
                'code' => 'CT-SHOULDER-PLAIN',
                'description' => 'Shoulder CT without contrast',
                'base_price' => 100000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس مفصل الكتف مع الصبغة (Shoulder CT contrast)',
                'code' => 'CT-SHOULDER-CONTRAST',
                'description' => 'Shoulder CT with contrast',
                'base_price' => 150000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس مفصل المرفق بدون صبغة (Elbow CT plain)',
                'code' => 'CT-ELBOW-PLAIN',
                'description' => 'Elbow CT without contrast',
                'base_price' => 100000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس مفصل المرفق مع الصبغة (Elbow CT contrast)',
                'code' => 'CT-ELBOW-CONTRAST',
                'description' => 'Elbow CT with contrast',
                'base_price' => 150000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس اليد والرسغ (Hand & Wrist CT)',
                'code' => 'CT-HAND-WRIST',
                'description' => 'Hand and Wrist CT',
                'base_price' => 100000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس مفصل الورك (Hip Joint CT)',
                'code' => 'CT-HIP-JOINT',
                'description' => 'Hip Joint CT',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس عظم الفخذ بدون صبغة (Thigh CT native)',
                'code' => 'CT-THIGH-PLAIN',
                'description' => 'Thigh CT (native)',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس عظم الفخذ مع الصبغة (Thigh CT contrast)',
                'code' => 'CT-THIGH-CONTRAST',
                'description' => 'Thigh CT with contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس مفصل الركبة بدون صبغة (Knee CT plain)',
                'code' => 'CT-KNEE-PLAIN',
                'description' => 'Knee CT without contrast',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس مفصل الركبة مع الصبغة (Knee CT contrast)',
                'code' => 'CT-KNEE-CONTRAST',
                'description' => 'Knee CT with contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس الساق بدون صبغة (Leg CT native)',
                'code' => 'CT-LEG-PLAIN',
                'description' => 'Leg CT (native)',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الساق مع الصبغة (Leg CT with contrast)',
                'code' => 'CT-LEG-CONTRAST',
                'description' => 'Leg CT with contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مفراس مفصل الكاحل والقدم بدون صبغة (Ankle & Foot CT plain)',
                'code' => 'CT-ANKLE-FOOT-PLAIN',
                'description' => 'Ankle and Foot CT without contrast',
                'base_price' => 125000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'مفراس الكاحل والقدم مع الصبغة (Ankle & Foot CT contrast)',
                'code' => 'CT-ANKLE-FOOT-CONTRAST',
                'description' => 'Ankle and Foot CT with contrast',
                'base_price' => 175000.00,
                'requires_contrast' => true,
            ],

            // خدمات إضافية وفحوصات خاصة (Special & Add-on Services)
            [
                'name' => 'جهاز التصوير المقطعي بالإصدار البوزيتروني (PET-CT Scan)',
                'code' => 'CT-PET-SCAN',
                'description' => 'PET-CT Scan (Positron Emission Tomography)',
                'base_price' => 300000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'صبغة وريدية إضافية للمفراس (I.V Contrast Media for CT)',
                'code' => 'CT-ADDON-IV-CONTRAST',
                'description' => 'I.V Contrast Media for CT',
                'base_price' => 50000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'مادة ملونة للشرب (Oral Contrast Media for CT)',
                'code' => 'CT-ADDON-ORAL-CONTRAST',
                'description' => 'Oral Contrast Media for CT',
                'base_price' => 25000.00,
                'requires_contrast' => true,
            ],
            [
                'name' => 'تنويم الطفل بإشراف طبيب الأطفال لإجراء المفراس',
                'code' => 'CT-ADDON-PEDIATRIC-SEDATION',
                'description' => 'Pediatric Sedation for CT scan',
                'base_price' => 50000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'طباعة فلم مفراس إضافي',
                'code' => 'CT-ADDON-FILM-PRINT',
                'description' => 'Additional CT Film Print',
                'base_price' => 10000.00,
                'requires_contrast' => false,
            ],
            [
                'name' => 'تقرير المفراس والرنين الاستشاري',
                'code' => 'CT-ADDON-REPORT',
                'description' => 'Radiology Consultant Report',
                'base_price' => 25000.00,
                'requires_contrast' => false,
            ],
        ];

        foreach ($services as $srv) {
            DB::table('radiology_types')->updateOrInsert(
                ['code' => $srv['code']],
                array_merge($srv, [
                    'main_category' => 'مفراس',
                    'subcategory' => 'مفراس',
                    'is_active' => true,
                    // خدمات خاصة خارج الضمان تماماً
                    'hi_price' => 0.00,
                    'is_hi_active' => false,
                    'moi_price' => 0.00,
                    'is_moi_active' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // تحديث تصنيف أي فحوصات مفراس قديمة لتصبح تابعة لقسم المفراس
        DB::table('radiology_types')
            ->where(function ($q) {
                $q->where('name', 'like', '%مفراس%')
                  ->orWhere('description', 'like', '%CT%')
                  ->orWhere('subcategory', 'like', '%مفراس%');
            })
            ->update([
                'main_category' => 'مفراس',
                'subcategory' => 'مفراس',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('radiology_types')
            ->where('code', 'like', 'CT-%')
            ->delete();
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HrLookupOption;
use App\Models\HrFieldRequirement;

class HrSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. خيارات أنواع التعيين والتعاقد
        $employmentTypes = [
            ['name' => 'دوام كامل (ملاك دائم)', 'code' => 'full_time', 'sort_order' => 1],
            ['name' => 'عقد مستشفى محدد المدة', 'code' => 'contract', 'sort_order' => 2],
            ['name' => 'عقد وزاري / حكومي', 'code' => 'gov_contract', 'sort_order' => 3],
            ['name' => 'دوام جزئي', 'code' => 'part_time', 'sort_order' => 4],
            ['name' => 'أجر يومي / خفارات بالساعة', 'code' => 'daily_shift', 'sort_order' => 5],
        ];
        foreach ($employmentTypes as $type) {
            HrLookupOption::updateOrCreate(
                ['category' => 'employment_type', 'name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true, 'sort_order' => $type['sort_order']]
            );
        }

        // 2. خيارات نوع الملاك / تصنيف الكادر (Staff Types)
        $staffTypes = [
            ['name' => '🩺 كادر طبي (أطباء وجراحين)', 'code' => 'medical', 'sort_order' => 1],
            ['name' => '💉 كادر تمريضي', 'code' => 'nursing', 'sort_order' => 2],
            ['name' => '🔬 كادر فني (مختبر / أشعة / صيدلة)', 'code' => 'technical', 'sort_order' => 3],
            ['name' => '💼 كادر إداري ومالي', 'code' => 'administrative', 'sort_order' => 4],
            ['name' => '🛠️ كادر خدمات وصيانة', 'code' => 'service', 'sort_order' => 5],
        ];
        foreach ($staffTypes as $st) {
            HrLookupOption::updateOrCreate(
                ['category' => 'staff_type', 'name' => $st['name']],
                ['code' => $st['code'], 'is_active' => true, 'sort_order' => $st['sort_order']]
            );
        }

        // 3. أنواع المستمسكات والوثائق الرسمية
        $docTypes = [
            ['name' => 'البطاقة الوطنية الموحدة', 'code' => 'national_id', 'sort_order' => 1],
            ['name' => 'بطاقة السكن', 'code' => 'residence_card', 'sort_order' => 2],
            ['name' => 'عقد العمل', 'code' => 'employment_contract', 'sort_order' => 3],
            ['name' => 'ترخيص مزاولة المهنة (وزارة الصحة)', 'code' => 'medical_license', 'sort_order' => 4],
            ['name' => 'هوية النقابة الطبية / التمريضية', 'code' => 'syndicate_card', 'sort_order' => 5],
            ['name' => 'وثيقة / شهادة التخرج', 'code' => 'graduation_certificate', 'sort_order' => 6],
            ['name' => 'أمر إداري بالمباشرة', 'code' => 'administrative_order', 'sort_order' => 7],
            ['name' => 'شهادة الجنسية / هوية الأحوال', 'code' => 'citizenship_cert', 'sort_order' => 8],
            ['name' => 'السيرة الذاتية (CV)', 'code' => 'cv', 'sort_order' => 9],
            ['name' => 'أخرى', 'code' => 'other', 'sort_order' => 10],
        ];
        foreach ($docTypes as $doc) {
            HrLookupOption::updateOrCreate(
                ['category' => 'document_type', 'name' => $doc['name']],
                ['code' => $doc['code'], 'is_active' => true, 'sort_order' => $doc['sort_order']]
            );
        }

        // 4. خيارات المؤهل العلمي والشهادات
        $qualifications = [
            ['name' => 'بورد طبي (عربي / عراقي / أجنبي)', 'code' => 'board', 'sort_order' => 1],
            ['name' => 'دكتوراه / PhD / زمالة', 'code' => 'phd', 'sort_order' => 2],
            ['name' => 'ماجستير / MSc', 'code' => 'master', 'sort_order' => 3],
            ['name' => 'دبلوم عالي', 'code' => 'higher_diploma', 'sort_order' => 4],
            ['name' => 'بكالوريوس طب وجراحة عامة', 'code' => 'mbchb', 'sort_order' => 5],
            ['name' => 'بكالوريوس تخصصي (تمريض، صيدلة، تحليلات، أشعة)', 'code' => 'bachelor', 'sort_order' => 6],
            ['name' => 'دبلوم معهد طبي / فني', 'code' => 'diploma', 'sort_order' => 7],
            ['name' => 'إعدادية فما دون', 'code' => 'secondary', 'sort_order' => 8],
        ];
        foreach ($qualifications as $q) {
            HrLookupOption::updateOrCreate(
                ['category' => 'qualification', 'name' => $q['name']],
                ['code' => $q['code'], 'is_active' => true, 'sort_order' => $q['sort_order']]
            );
        }

        // 5. فصائل الدم
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        foreach ($bloodGroups as $idx => $bg) {
            HrLookupOption::updateOrCreate(
                ['category' => 'blood_group', 'name' => $bg],
                ['code' => $bg, 'is_active' => true, 'sort_order' => $idx + 1]
            );
        }

        // 6. الحالات الوظيفية
        $statuses = [
            ['name' => 'على رأس العمل', 'code' => 'active', 'sort_order' => 1],
            ['name' => 'في إجازة', 'code' => 'on_leave', 'sort_order' => 2],
            ['name' => 'موقوف مؤقتاً', 'code' => 'suspended', 'sort_order' => 3],
            ['name' => 'مستقيل', 'code' => 'resigned', 'sort_order' => 4],
            ['name' => 'منهي خدماته', 'code' => 'terminated', 'sort_order' => 5],
        ];
        foreach ($statuses as $st) {
            HrLookupOption::updateOrCreate(
                ['category' => 'status', 'name' => $st['name']],
                ['code' => $st['code'], 'is_active' => true, 'sort_order' => $st['sort_order']]
            );
        }

        // 7. تعريف وتحديث جميع حقول النظام مع أنواعها وفئاتها وقفلها
        $fields = [
            // أ. البيانات الشخصية
            ['field_key' => 'full_name', 'field_name_ar' => 'الاسم الكامل', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'personal', 'is_required' => true, 'is_locked' => true, 'sort_order' => 1],
            ['field_key' => 'national_id', 'field_name_ar' => 'رقم البطاقة الموحدة / الهوية', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'personal', 'is_required' => false, 'is_locked' => false, 'sort_order' => 2],
            ['field_key' => 'gender', 'field_name_ar' => 'الجنس', 'field_type' => 'select', 'lookup_category' => 'gender', 'group_name' => 'personal', 'is_required' => true, 'is_locked' => false, 'sort_order' => 3],
            ['field_key' => 'date_of_birth', 'field_name_ar' => 'تاريخ الميلاد', 'field_type' => 'date', 'lookup_category' => null, 'group_name' => 'personal', 'is_required' => false, 'is_locked' => false, 'sort_order' => 4],
            ['field_key' => 'blood_group', 'field_name_ar' => 'فصيلة الدم', 'field_type' => 'select', 'lookup_category' => 'blood_group', 'group_name' => 'personal', 'is_required' => false, 'is_locked' => false, 'sort_order' => 5],
            ['field_key' => 'phone', 'field_name_ar' => 'رقم الهاتف الأساسي', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'personal', 'is_required' => true, 'is_locked' => false, 'sort_order' => 6],
            ['field_key' => 'emergency_phone', 'field_name_ar' => 'هاتف الطوارئ (شخص قريب)', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'personal', 'is_required' => false, 'is_locked' => false, 'sort_order' => 7],
            ['field_key' => 'email', 'field_name_ar' => 'البريد الإلكتروني', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'personal', 'is_required' => false, 'is_locked' => false, 'sort_order' => 8],
            ['field_key' => 'address', 'field_name_ar' => 'عنوان السكن الكامل', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'personal', 'is_required' => false, 'is_locked' => false, 'sort_order' => 9],
            ['field_key' => 'profile_photo', 'field_name_ar' => 'الصورة الشخصية', 'field_type' => 'file', 'lookup_category' => null, 'group_name' => 'personal', 'is_required' => false, 'is_locked' => false, 'sort_order' => 10],

            // ب. البيانات الوظيفية
            ['field_key' => 'employee_code', 'field_name_ar' => 'الرقم الوظيفي (كود الباجة)', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'job', 'is_required' => true, 'is_locked' => true, 'sort_order' => 11],
            ['field_key' => 'staff_type', 'field_name_ar' => 'نوع الملاك / تصنيف الكادر', 'field_type' => 'select', 'lookup_category' => 'staff_type', 'group_name' => 'job', 'is_required' => true, 'is_locked' => true, 'sort_order' => 12],
            ['field_key' => 'job_title', 'field_name_ar' => 'المسمى الوظيفي', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'job', 'is_required' => true, 'is_locked' => false, 'sort_order' => 13],
            ['field_key' => 'department_id', 'field_name_ar' => 'القسم التابع له', 'field_type' => 'select', 'lookup_category' => 'department', 'group_name' => 'job', 'is_required' => true, 'is_locked' => false, 'sort_order' => 14],
            ['field_key' => 'employment_type', 'field_name_ar' => 'نوع التعيين والتعاقد', 'field_type' => 'select', 'lookup_category' => 'employment_type', 'group_name' => 'job', 'is_required' => true, 'is_locked' => false, 'sort_order' => 15],
            ['field_key' => 'hire_date', 'field_name_ar' => 'تاريخ المباشرة بالعمل', 'field_type' => 'date', 'lookup_category' => null, 'group_name' => 'job', 'is_required' => true, 'is_locked' => false, 'sort_order' => 16],
            ['field_key' => 'contract_end_date', 'field_name_ar' => 'تاريخ انتهاء العقد', 'field_type' => 'date', 'lookup_category' => null, 'group_name' => 'job', 'is_required' => false, 'is_locked' => false, 'sort_order' => 17],
            ['field_key' => 'basic_salary', 'field_name_ar' => 'الراتب الأساسي', 'field_type' => 'number', 'lookup_category' => null, 'group_name' => 'job', 'is_required' => false, 'is_locked' => false, 'sort_order' => 18],
            ['field_key' => 'status', 'field_name_ar' => 'الحالة الوظيفية', 'field_type' => 'select', 'lookup_category' => 'status', 'group_name' => 'job', 'is_required' => true, 'is_locked' => false, 'sort_order' => 19],

            // ج. التراخيص والبيانات الطبية
            ['field_key' => 'medical_license_number', 'field_name_ar' => 'رقم إجازة / ترخيص ممارسة المهنة', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'medical', 'is_required' => false, 'is_locked' => false, 'sort_order' => 20],
            ['field_key' => 'license_expiry_date', 'field_name_ar' => 'تاريخ انتهاء ترخيص الممارسة', 'field_type' => 'date', 'lookup_category' => null, 'group_name' => 'medical', 'is_required' => false, 'is_locked' => false, 'sort_order' => 21],
            ['field_key' => 'syndicate_card_number', 'field_name_ar' => 'رقم هوية النقابة', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'medical', 'is_required' => false, 'is_locked' => false, 'sort_order' => 22],
            ['field_key' => 'qualification', 'field_name_ar' => 'المؤهل العلمي / الشهادة', 'field_type' => 'select', 'lookup_category' => 'qualification', 'group_name' => 'medical', 'is_required' => false, 'is_locked' => false, 'sort_order' => 23],
            ['field_key' => 'sub_specialty', 'field_name_ar' => 'التخصص الدقيق', 'field_type' => 'text', 'lookup_category' => null, 'group_name' => 'medical', 'is_required' => false, 'is_locked' => false, 'sort_order' => 24],

            // د. المستمسكات
            ['field_key' => 'documents', 'field_name_ar' => 'إرفاق المستمسكات الرسمية', 'field_type' => 'file', 'lookup_category' => 'document_type', 'group_name' => 'documents', 'is_required' => false, 'is_locked' => false, 'sort_order' => 25],
        ];

        foreach ($fields as $field) {
            HrFieldRequirement::updateOrCreate(
                ['field_key' => $field['field_key']],
                [
                    'field_name_ar' => $field['field_name_ar'],
                    'field_type' => $field['field_type'],
                    'lookup_category' => $field['lookup_category'],
                    'group_name' => $field['group_name'],
                    'is_required' => $field['is_required'],
                    'is_locked' => $field['is_locked'],
                    'sort_order' => $field['sort_order'],
                ]
            );
        }
    }
}

<?php

use App\Models\Accounting\Account;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. تحديث الصناديق النقدية التحليلية بالكاشير تحت 13411 (نقدية لدى صندوق المركز)
        $parentCash = Account::where('code', '13411')->first();
        if ($parentCash) {
            $parentCash->update(['is_postable' => false]);
        }

        Account::updateOrCreate(
            ['code' => '134111'],
            [
                'name' => 'صندوق المقبوضات النقدية العامة',
                'parent_id' => $parentCash?->id,
                'level' => 6,
                'account_type' => 'asset',
                'nature' => 'debit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        Account::updateOrCreate(
            ['code' => '134112'],
            [
                'name' => 'صندوق مقبوضات الضمان الصحي (استقطاعات ونسب تحمل)',
                'parent_id' => $parentCash?->id,
                'level' => 6,
                'account_type' => 'asset',
                'nature' => 'debit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        Account::updateOrCreate(
            ['code' => '134113'],
            [
                'name' => 'صندوق مقبوضات قوى الأمن الداخلي (استقطاعات ونسب تحمل)',
                'parent_id' => $parentCash?->id,
                'level' => 6,
                'account_type' => 'asset',
                'nature' => 'debit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 2. تحديث وتفريع ذمم ومطالبات الجهات الضامنة الحكومية تحت 13211 (مدينون قطاع عام)
        $parentPublicDebtors = Account::where('code', '13211')->first();
        if ($parentPublicDebtors) {
            $parentPublicDebtors->update(['is_postable' => false]);
        }

        Account::updateOrCreate(
            ['code' => '132111'],
            [
                'name' => 'ذمم ومطالبات هيأة الضمان الصحي الوطنية',
                'parent_id' => $parentPublicDebtors?->id,
                'level' => 6,
                'account_type' => 'asset',
                'nature' => 'debit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        Account::updateOrCreate(
            ['code' => '132112'],
            [
                'name' => 'ذمم ومطالبات وزارة الداخلية (قوى الأمن الداخلي)',
                'parent_id' => $parentPublicDebtors?->id,
                'level' => 6,
                'account_type' => 'asset',
                'nature' => 'debit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 3. إنشاء حساب المقبوضات الوسيط من المرضى 2326111 تحت 232611 (تأمينات مستلمة لصالح الوحدة)
        $parentInsuranceHeld = Account::where('code', '232611')->first();
        if ($parentInsuranceHeld) {
            $parentInsuranceHeld->update(['is_postable' => false]);
        }

        Account::updateOrCreate(
            ['code' => '2326111'],
            [
                'name' => 'حسابات مقبوضة من المرضى (وسيط أمانات ومقبوضات الخدمات والعمليات)',
                'parent_id' => $parentInsuranceHeld?->id,
                'level' => 7,
                'account_type' => 'liability',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 4. تفريع إيرادات الخدمات الطبية والعلاجية تحت 43121
        $parentRevenue = Account::where('code', '43121')->first();
        if ($parentRevenue) {
            $parentRevenue->update([
                'name' => 'إيرادات الخدمات الطبية والعلاجية (حسب النشاط)',
                'is_postable' => false,
            ]);
        }

        // 4.1 إيرادات صالات العمليات الجراحية 431211
        $surgRev = Account::updateOrCreate(
            ['code' => '431211'],
            [
                'name' => 'إيرادات صالات العمليات الجراحية',
                'parent_id' => $parentRevenue?->id,
                'level' => 6,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => false,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312111'],
            [
                'name' => 'إيراد أجور صالات العمليات الجراحية',
                'parent_id' => $surgRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312112'],
            [
                'name' => 'إيراد استخدام الأجهزة التخصصية (ناظور، PCA، تاب بلوك)',
                'parent_id' => $surgRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 4.2 إيرادات الفحوصات والتحاليل المختبرية 431212
        $labRev = Account::updateOrCreate(
            ['code' => '431212'],
            [
                'name' => 'إيرادات الفحوصات والتحاليل المختبرية',
                'parent_id' => $parentRevenue?->id,
                'level' => 6,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => false,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312121'],
            [
                'name' => 'تحاليل كيمياء الدم والمناعة والهرمونات',
                'parent_id' => $labRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312122'],
            [
                'name' => 'تحاليل أمراض الدم ومصرف الدم',
                'parent_id' => $labRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312123'],
            [
                'name' => 'الفحوصات الجرثومية والزرع البكتيري',
                'parent_id' => $labRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312124'],
            [
                'name' => 'فحوصات الأنسجة والخزعات النسيجية',
                'parent_id' => $labRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 4.3 إيرادات الأشعة والتصوير الطبي 431213
        $radRev = Account::updateOrCreate(
            ['code' => '431213'],
            [
                'name' => 'إيرادات الأشعة والتصوير الطبي',
                'parent_id' => $parentRevenue?->id,
                'level' => 6,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => false,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312131'],
            [
                'name' => 'إيرادات الأشعة السينية العادية (X-Ray)',
                'parent_id' => $radRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312132'],
            [
                'name' => 'إيرادات السونار والموجات فوق الصوتية (Ultrasound)',
                'parent_id' => $radRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312133'],
            [
                'name' => 'إيرادات الرنين المغناطيسي (MRI)',
                'parent_id' => $radRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312134'],
            [
                'name' => 'إيرادات المفراس الحلزوني (CT Scan)',
                'parent_id' => $radRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312135'],
            [
                'name' => 'إيرادات إيكو وتخطيط القلب (Echo & ECG)',
                'parent_id' => $radRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 4.4 إيرادات الرقود والإقامة الفندقية 431214
        $inpatientRev = Account::updateOrCreate(
            ['code' => '431214'],
            [
                'name' => 'إيرادات الرقود والإقامة الفندقية',
                'parent_id' => $parentRevenue?->id,
                'level' => 6,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => false,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312141'],
            [
                'name' => 'إيرادات إقامة الأجنحة الخاصة',
                'parent_id' => $inpatientRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312142'],
            [
                'name' => 'إيرادات إقامة الغرف المشتركة',
                'parent_id' => $inpatientRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312143'],
            [
                'name' => 'إيرادات إقامة العناية المركزة (ICU / CCU)',
                'parent_id' => $inpatientRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 4.5 إيرادات قسم الخدج وحاضنات الأطفال 431215
        $nicuRev = Account::updateOrCreate(
            ['code' => '431215'],
            [
                'name' => 'إيرادات قسم الخدج وحاضنات الأطفال (NICU)',
                'parent_id' => $parentRevenue?->id,
                'level' => 6,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => false,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312151'],
            [
                'name' => 'إيرادات إقامة حاضنات الخدج العادية',
                'parent_id' => $nicuRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
        Account::updateOrCreate(
            ['code' => '4312152'],
            [
                'name' => 'إيرادات العناية المركزة للخدج والتنفس الاصطناعي (Ventilator/CPAP)',
                'parent_id' => $nicuRev->id,
                'level' => 7,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 4.6 إيرادات الطوارئ والحوادث 431216
        Account::updateOrCreate(
            ['code' => '431216'],
            [
                'name' => 'إيرادات الطوارئ والحوادث والإسعاف الفوري',
                'parent_id' => $parentRevenue?->id,
                'level' => 6,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 4.7 إيرادات العيادات الاستشارية والكشفيات 431217
        Account::updateOrCreate(
            ['code' => '431217'],
            [
                'name' => 'إيرادات العيادات الاستشارية الخارجية والكشفيات',
                'parent_id' => $parentRevenue?->id,
                'level' => 6,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 4.8 إيرادات العلاج الطبيعي والتأهيل الطبي 431218
        Account::updateOrCreate(
            ['code' => '431218'],
            [
                'name' => 'إيرادات العلاج الطبيعي والتأهيل الطبي',
                'parent_id' => $parentRevenue?->id,
                'level' => 6,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        // 5. تفعيل حساب مصروفات المواد الطبية 3263 ليكون قابلاً للترحيل المباشر
        $medicalSuppliesExp = Account::where('code', '3263')->first();
        if ($medicalSuppliesExp) {
            $medicalSuppliesExp->update([
                'name' => 'مصروفات المواد والمستلزمات الطبية المستهلكة',
                'is_postable' => true,
                'is_active' => true,
            ]);
        } else {
            Account::create([
                'code' => '3263',
                'name' => 'مصروفات المواد والمستلزمات الطبية المستهلكة',
                'level' => 4,
                'account_type' => 'expense',
                'nature' => 'debit',
                'is_postable' => true,
                'is_active' => true,
            ]);
        }

        // 6. حساب المخزون العام 131 والموردين 23214 في حال عدم وجودهما
        Account::firstOrCreate(
            ['code' => '131'],
            [
                'name' => 'مخزون الأدوية والمستلزمات الطبية',
                'level' => 3,
                'account_type' => 'asset',
                'nature' => 'debit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['code' => '23214'],
            [
                'name' => 'دائنون قطاع خاص (الموردون والمجهزون)',
                'level' => 5,
                'account_type' => 'liability',
                'nature' => 'credit',
                'is_postable' => true,
                'is_active' => true,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Account::whereIn('code', [
            '134112', '134113', '132111', '132112', '2326111',
            '431211', '4312111', '4312112',
            '431212', '4312121', '4312122', '4312123', '4312124',
            '431213', '4312131', '4312132', '4312133', '4312134', '4312135',
            '431214', '4312141', '4312142', '4312143',
            '431215', '4312151', '4312152',
            '431216', '431217', '431218',
        ])->update(['is_active' => false]);
    }
};

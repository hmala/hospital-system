<?php

use App\Models\Accounting\Account;
use App\Models\Accounting\CostCenter;
use App\Models\Department;
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
        // 1. إضافة حقول الربط المالي لجدول departments (بشكل آمن وغير كاسر)
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'cost_center_id')) {
                $table->unsignedBigInteger('cost_center_id')->nullable()->after('manager_id');
            }
            if (! Schema::hasColumn('departments', 'revenue_account_id')) {
                $table->unsignedBigInteger('revenue_account_id')->nullable()->after('cost_center_id');
            }
        });

        // 2. إضافة حقل مركز الكلفة لجدول locations (لربط المخازن الفرعية بمراكز الكلفة)
        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'cost_center_id')) {
                $table->unsignedBigInteger('cost_center_id')->nullable()->after('custodian_id');
            }
        });

        // 3. جلب معرفات مراكز الكلفة والحسابات المالية للربط التلقائي
        $consultationRev = Account::where('code', '431217')->first();
        $labRev = Account::where('code', '431212')->first();
        $nicuRev = Account::firstOrCreate(
            ['code' => '431215'],
            [
                'name' => 'إيرادات قسم الخدج وحاضنات الأطفال (NICU)',
                'level' => 6,
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => false,
                'is_active' => true,
            ]
        );
        $emergencyRev = Account::where('code', '431216')->first();

        // 4. ربط الأقسام القائمة بمراكز الكلفة وحسابات الإيراد
        // عيادة الباطنية
        $ccInternal = CostCenter::where('code', '151')->first();
        Department::where('name', 'like', '%باطنية%')->update([
            'cost_center_id' => $ccInternal?->id,
            'revenue_account_id' => $consultationRev?->id,
        ]);

        // عيادة الجراحة
        $ccSurgery = CostCenter::where('code', '152')->first();
        Department::where('name', 'like', '%جراحة%')->update([
            'cost_center_id' => $ccSurgery?->id,
            'revenue_account_id' => $consultationRev?->id,
        ]);

        // عيادة الأطفال
        $ccPediatrics = CostCenter::where('code', '153')->first();
        Department::where('name', 'like', '%أطفال%')->update([
            'cost_center_id' => $ccPediatrics?->id,
            'revenue_account_id' => $consultationRev?->id,
        ]);

        // عيادة النساء
        $ccObstetrics = CostCenter::where('code', '154')->first();
        Department::where('name', 'like', '%نساء%')->update([
            'cost_center_id' => $ccObstetrics?->id,
            'revenue_account_id' => $consultationRev?->id,
        ]);

        // المختبر
        $ccLab = CostCenter::where('code', '21')->first();
        Department::where('name', 'like', '%مختبر%')->update([
            'cost_center_id' => $ccLab?->id,
            'revenue_account_id' => $labRev?->id,
        ]);

        // الطوارئ
        $ccEmergency = CostCenter::where('code', '14')->first();
        Department::where('name', 'like', '%طوارئ%')->orWhere('name', 'like', '%طوارىء%')->update([
            'cost_center_id' => $ccEmergency?->id,
            'revenue_account_id' => $emergencyRev?->id,
        ]);

        // 5. إدراج الأقسام الطبية الرئيسية غير المدرجة سابقاً رسمياً في جدول departments
        $ccNicu = CostCenter::firstOrCreate(
            ['code' => '13'],
            [
                'name' => 'قسم الخدج والحاضنات (NICU)',
                'is_active' => true,
            ]
        );
        $hospId = \App\Models\Hospital::first()?->id ?? DB::table('departments')->value('hospital_id');
        if (! $hospId) {
            $hosp = \App\Models\Hospital::create([
                'name' => 'مستشفى الفيحاء الأهلي',
                'owner_name' => 'الإدارة العامة',
                'phone' => '07700000000',
                'address' => 'البصرة - الفيحاء',
                'license_number' => 'FA-001',
            ]);
            $hospId = $hosp->id;
        }

        Department::firstOrCreate(
            ['name' => 'قسم الخدج وحاضنات الأطفال (NICU)'],
            [
                'hospital_id' => $hospId,
                'type' => 'pediatrics',
                'room_number' => 'NICU',
                'consultation_fee' => 0.00,
                'is_active' => true,
                'cost_center_id' => $ccNicu?->id,
                'revenue_account_id' => $nicuRev?->id,
            ]
        );

        // قسم الأشعة والتصوير الطبي
        $ccRad = CostCenter::firstOrCreate(['code' => '22'], ['name' => 'قسم الأشعة والسونار والرنين والمفراس', 'is_active' => true]);
        $radRev = Account::where('code', '4312131')->first() ?? Account::where('code', '431213')->first();
        Department::firstOrCreate(
            ['name' => 'قسم الأشعة والتصوير الطبي (Radiology)'],
            [
                'hospital_id' => $hospId,
                'type' => 'other',
                'room_number' => 'قسم الأشعة',
                'consultation_fee' => 0.00,
                'is_active' => true,
                'cost_center_id' => $ccRad?->id,
                'revenue_account_id' => $radRev?->id,
            ]
        );

        // قسم صالات العمليات الجراحية
        $ccOR = CostCenter::firstOrCreate(['code' => '11'], ['name' => 'صالات العمليات الجراحية', 'is_active' => true]);
        $orRev = Account::where('code', '4312111')->first();
        Department::firstOrCreate(
            ['name' => 'قسم صالات العمليات الجراحية (OR)'],
            [
                'hospital_id' => $hospId,
                'type' => 'surgery',
                'room_number' => 'صالات العمليات',
                'consultation_fee' => 0.00,
                'is_active' => true,
                'cost_center_id' => $ccOR?->id,
                'revenue_account_id' => $orRev?->id,
            ]
        );

        // قسم الرقود والإقامة الفندقية
        $ccInpatient = CostCenter::firstOrCreate(['code' => '12'], ['name' => 'أجنحة وردهات الرقود العام والخاص', 'is_active' => true]);
        $inpatientRev = Account::where('code', '4312141')->first();
        Department::firstOrCreate(
            ['name' => 'قسم الرقود والإقامة الفندقية'],
            [
                'hospital_id' => $hospId,
                'type' => 'other',
                'room_number' => 'أجنحة وردهات الرقود',
                'consultation_fee' => 0.00,
                'is_active' => true,
                'cost_center_id' => $ccInpatient?->id,
                'revenue_account_id' => $inpatientRev?->id,
            ]
        );

        // 6. ربط المخازن الفرعية locations بمراكز الكلفة التابعة لها
        $locMappings = [
            'صالة العمليات 1' => CostCenter::where('code', '111')->first()?->id,
            'ردهة الطابق الخامس' => CostCenter::where('code', '123')->first()?->id,
            'ردهةالطابق الرابع' => CostCenter::where('code', '122')->first()?->id,
            'ردهة الطابق الثاني' => CostCenter::where('code', '121')->first()?->id,
            'الاستشارية' => CostCenter::where('code', '15')->first()?->id,
            'المختبر' => CostCenter::where('code', '21')->first()?->id,
            'الطواريء' => CostCenter::where('code', '14')->first()?->id,
            'الخدج' => CostCenter::where('code', '13')->first()?->id,
            'صالة الولادة' => CostCenter::where('code', '112')->first()?->id,
            'النواظير طابق 6' => CostCenter::where('code', '113')->first()?->id,
        ];

        foreach ($locMappings as $locName => $ccId) {
            if ($ccId) {
                DB::table('locations')->where('name', $locName)->update(['cost_center_id' => $ccId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'revenue_account_id')) {
                $table->dropColumn('revenue_account_id');
            }
            if (Schema::hasColumn('departments', 'cost_center_id')) {
                $table->dropColumn('cost_center_id');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'cost_center_id')) {
                $table->dropColumn('cost_center_id');
            }
        });
    }
};

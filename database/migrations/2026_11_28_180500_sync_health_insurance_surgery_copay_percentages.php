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
        // مزامنة العمليات غير المسددة لمرضى الضمان الصحي الوطني وتصحيح نسبة الاستقطاع وفق فئة المريض الرسمية
        $surgeries = DB::table('surgeries')
            ->join('patients', 'surgeries.patient_id', '=', 'patients.id')
            ->join('health_insurance_categories', 'patients.health_insurance_category_id', '=', 'health_insurance_categories.id')
            ->where(function ($query) {
                $query->where('surgeries.insurance_type', 'hi')
                    ->orWhere('patients.insurance_type', 'hi');
            })
            ->where('surgeries.payment_status', '!=', 'paid')
            ->select('surgeries.id', 'health_insurance_categories.surgery_copay')
            ->get();

        foreach ($surgeries as $surgery) {
            DB::table('surgeries')
                ->where('id', $surgery->id)
                ->update(['copay_percentage' => $surgery->surgery_copay]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا حاجة لعمل تراجع لأنها مزامنة تصحيحية لبيانات العمليات غير المسددة
    }
};

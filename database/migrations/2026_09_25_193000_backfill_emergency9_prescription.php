<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Emergency;
use App\Models\EmergencyTreatment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Medicine;

return new class extends Migration
{
    /**
     * تعبئة الوصفات الإلكترونية للطوارئ التي طُلبت قبل تفعيل الآلية الجديدة.
     */
    public function up(): void
    {
        // البحث عن كل علاجات طوارئ بحالة "completed" أو "pending" لا تملك وصفة مرتبطة بعد
        $emergencyIds = EmergencyTreatment::whereIn('treatment_type', ['medication', 'injection', 'drip'])
            ->pluck('emergency_id')
            ->unique();

        foreach ($emergencyIds as $emergencyId) {
            $emergency = Emergency::find($emergencyId);
            if (!$emergency) continue;

            // تجاهل إذا كانت هناك وصفة pending موجودة مسبقاً
            if (Prescription::where('emergency_id', $emergencyId)->where('status', 'pending')->exists()) {
                continue;
            }

            $treatments = EmergencyTreatment::where('emergency_id', $emergencyId)
                ->whereIn('treatment_type', ['medication', 'injection', 'drip'])
                ->get();

            if ($treatments->isEmpty()) continue;

            $prescription = Prescription::create([
                'emergency_id' => $emergencyId,
                'patient_id'   => $emergency->patient_id,
                'doctor_id'    => $emergency->doctor_id,
                'diagnosis'    => $emergency->diagnosis ?? '🚨 علاج طوارئ (STAT)',
                'notes'        => '🚨 طلب أدوية ومحاليل طوارئ (STAT) - تعبئة تلقائية',
                'status'       => 'pending',
            ]);

            foreach ($treatments as $t) {
                $medId = null;
                if (!empty($t->description)) {
                    $med = Medicine::where('name', $t->description)
                        ->orWhere('generic_name', $t->description)
                        ->first();
                    $medId = $med?->id;
                }

                PrescriptionItem::create([
                    'prescription_id'  => $prescription->id,
                    'medicine_id'      => $medId,
                    'medicine_name'    => $t->description,
                    'quantity'         => 1,
                    'unit_type'        => 'main_unit',
                    'dosage_frequency' => $t->notes ?? '',
                    'duration_days'    => null,
                    'instructions'     => $t->treatment_type === 'injection' ? 'حقن' :
                                        ($t->treatment_type === 'drip' ? 'محلول وريدي STAT' : null),
                    'status'           => 'pending',
                ]);
            }
        }
    }

    public function down(): void
    {
        // حذف الوصفات التي أُنشئت بالتعبئة التلقائية
        Prescription::where('notes', 'like', '%تعبئة تلقائية%')
            ->where('emergency_id', '!=', null)
            ->each(function ($p) {
                $p->items()->delete();
                $p->delete();
            });
    }
};

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
        // تصحيح حصص أطباء الطوارئ السابقة لتصبح 100% للطبيب و 0 للمستشفى
        $revenues = DB::table('consultation_revenues')
            ->where('payment_type', 'emergency_consultation')
            ->get();

        foreach ($revenues as $rev) {
            $total = (float) $rev->total_amount;
            DB::table('consultation_revenues')
                ->where('id', $rev->id)
                ->update([
                    'doctor_share' => $total,
                    'hospital_share' => 0.00,
                    'doctor_percentage' => 100.00,
                ]);

            if ($rev->doctor_id && $rev->payment_id) {
                DB::table('doctor_dues')
                    ->where('doctor_id', $rev->doctor_id)
                    ->where('payment_id', $rev->payment_id)
                    ->update([
                        'amount' => $total,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا حاجة لعكس التعديل المالي
    }
};

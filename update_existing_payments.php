<?php

/**
 * Script لتحديث البيانات الموجودة وربط الخدمات المدفوعة بـ payments
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Emergency;
use App\Models\Payment;

echo "بدء تحديث البيانات الموجودة...\n\n";

DB::beginTransaction();

try {
    // الحصول على جميع حالات الطوارئ التي لديها payment مدفوع
    $emergencies = Emergency::whereHas('payment', function($q) {
        $q->whereNotNull('paid_at');
    })->with(['payment'])->get();

    echo "تم العثور على " . $emergencies->count() . " حالة طوارئ بها payments مدفوعة\n\n";

    foreach ($emergencies as $emergency) {
        // الحصول على أول payment مدفوع لهذه الحالة
        $firstPaidPayment = Payment::where('emergency_id', $emergency->id)
            ->where('payment_type', 'emergency')
            ->whereNotNull('paid_at')
            ->orderBy('paid_at')
            ->first();

        if (!$firstPaidPayment) {
            continue;
        }

        echo "معالجة Emergency #{$emergency->id} - Payment #{$firstPaidPayment->id}\n";

        // تحديث الخدمات التي لا تحتوي على payment_id
        $updatedServices = DB::table('emergency_emergency_service')
            ->where('emergency_id', $emergency->id)
            ->whereNull('payment_id')
            ->update(['payment_id' => $firstPaidPayment->id]);

        if ($updatedServices > 0) {
            echo "  - تم تحديث {$updatedServices} خدمة\n";
        }

        // تحديث طلبات التحاليل
        $updatedLabs = DB::table('emergency_lab_requests')
            ->where('emergency_id', $emergency->id)
            ->whereNull('payment_id')
            ->where('created_at', '<=', $firstPaidPayment->paid_at)
            ->update(['payment_id' => $firstPaidPayment->id]);

        if ($updatedLabs > 0) {
            echo "  - تم تحديث {$updatedLabs} طلب تحليل\n";
        }

        // تحديث طلبات الأشعة
        $updatedRadiology = DB::table('emergency_radiology_requests')
            ->where('emergency_id', $emergency->id)
            ->whereNull('payment_id')
            ->where('created_at', '<=', $firstPaidPayment->paid_at)
            ->update(['payment_id' => $firstPaidPayment->id]);

        if ($updatedRadiology > 0) {
            echo "  - تم تحديث {$updatedRadiology} طلب أشعة\n";
        }

        // تحديث رسوم المتابعة إذا كانت موجودة
        if ($emergency->doctor_follow_up_fee > 0 && !$emergency->follow_up_payment_id) {
            $emergency->update(['follow_up_payment_id' => $firstPaidPayment->id]);
            echo "  - تم ربط رسوم المتابعة بالـ payment\n";
        }

        echo "\n";
    }

    DB::commit();
    echo "\n✅ تم تحديث البيانات بنجاح!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ حدث خطأ: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Request;
use App\Models\LabResult;

return new class extends Migration
{
    /**
     * نقل نتائج التحاليل من جدول الطلبات إلى جدول نتائج المختبر
     */
    public function up(): void
    {
        // الحصول على جميع طلبات المختبر المكتملة
        $labRequests = Request::where('type', 'lab')
            ->where('status', 'completed')
            ->whereNotNull('result')
            ->get();

        foreach ($labRequests as $request) {
            try {
                // استخراج التفاصيل من حقل النتيجة
                $result = json_decode($request->result, true) ?? [];
                
                // إنشاء سجل جديد في جدول نتائج المختبر
                LabResult::create([
                    'visit_id' => $request->visit_id,
                    'request_id' => $request->id,
                    'test_name' => $request->description ?? 'تحليل مختبر',
                    'value' => $result['value'] ?? null,
                    'unit' => $result['unit'] ?? null,
                    'status' => $result['status'] ?? 'normal',
                    'reference_range' => $result['reference_range'] ?? null,
                    'notes' => $result['notes'] ?? null
                ]);
            } catch (\Exception $e) {
                // تسجيل أي أخطاء تحدث أثناء النقل
                \Log::error("خطأ في نقل نتيجة المختبر للطلب رقم {$request->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * التراجع عن عملية النقل
     */
    public function down(): void
    {
        // حذف جميع النتائج التي تم نقلها
        LabResult::whereIn('request_id', function($query) {
            $query->select('id')
                ->from('requests')
                ->where('type', 'lab');
        })->delete();
    }
};
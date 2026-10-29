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
        // 1. توحيد اسم الفحص الأساسي رقم 43 إلى الاسم الطبي الرسمي المعتمد CBC & Blood Film
        DB::table('lab_tests')
            ->where('id', 43)
            ->update([
                'name' => 'CBC & Blood Film',
                'description' => 'CBC 3 Diff. & Blood Film',
            ]);

        // تحويل أي فحص آخر يحمل الاسم القديم
        DB::table('lab_tests')
            ->where('name', 'Complete blood count & Differential')
            ->update([
                'name' => 'CBC & Blood Film',
            ]);

        // 2. دمج أي طلبات طوارئ استخدمت الفحص المكرر 1440 وإعادتها للفحص الأساسي 43
        if (DB::table('lab_tests')->where('id', 1440)->exists()) {
            DB::table('emergency_lab_request_tests')
                ->where('lab_test_id', 1440)
                ->update(['lab_test_id' => 43]);

            // تعطيل السجل المكرر 1440 لمنع ازدواجية الفحوصات في القوائم
            DB::table('lab_tests')
                ->where('id', 1440)
                ->update(['is_active' => 0]);
        }

        // 3. تحديث المرفقات والنتائج المسجلة بالاسم القديم في تفاصيل طلبات الطوارئ الحالية
        $emergencyRequests = DB::table('emergency_lab_requests')->whereNotNull('details')->get();
        foreach ($emergencyRequests as $req) {
            $details = json_decode($req->details, true);
            if (! is_array($details)) {
                continue;
            }

            $updated = false;

            // تحديث المرفقات
            if (isset($details['test_attachments']['Complete blood count & Differential'])) {
                $details['test_attachments']['CBC & Blood Film'] = $details['test_attachments']['Complete blood count & Differential'];
                unset($details['test_attachments']['Complete blood count & Differential']);
                $updated = true;
            }

            // تحديث النتائج المخزنة
            if (isset($details['test_results']['Complete blood count & Differential'])) {
                $details['test_results']['CBC & Blood Film'] = $details['test_results']['Complete blood count & Differential'];
                $details['test_results']['CBC & Blood Film']['test_name'] = 'CBC & Blood Film';
                unset($details['test_results']['Complete blood count & Differential']);
                $updated = true;
            }

            if ($updated) {
                DB::table('emergency_lab_requests')
                    ->where('id', $req->id)
                    ->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('lab_tests')
            ->where('id', 43)
            ->update([
                'name' => 'Complete blood count & Differential',
                'description' => 'CBC 3 Diff.',
            ]);

        if (DB::table('lab_tests')->where('id', 1440)->exists()) {
            DB::table('lab_tests')
                ->where('id', 1440)
                ->update(['is_active' => 1]);
        }
    }
};

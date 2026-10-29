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
        // 1. توحيد الفحصين 43 و 44 في lab_tests إلى الاسم المعتمد CBC (5-Part Diff)
        DB::table('lab_tests')
            ->whereIn('id', [43, 44])
            ->update([
                'name' => 'CBC (5-Part Diff)',
                'is_active' => true,
            ]);

        // 2. تعطيل السجلات المكررة والقديمة مثل 1440
        DB::table('lab_tests')
            ->where('id', 1440)
            ->update([
                'name' => 'CBC (5-Part Diff)',
                'is_active' => false,
            ]);

        // التأكد من أن فحص فيلم الدم المجهري 1243 مفعل ومستقل تماماً
        DB::table('lab_tests')
            ->where('id', 1243)
            ->update([
                'name' => 'Blood Film',
                'is_active' => true,
            ]);

        // 3. تحديث مصفوفات الفحوصات في جدول requests
        $requests = DB::table('requests')
            ->where('type', 'lab')
            ->where(function ($q) {
                $q->where('details', 'like', '%CBC & Blood Film%')
                    ->orWhere('details', 'like', '%Complete blood count & Differential%');
            })
            ->get(['id', 'details', 'description']);

        foreach ($requests as $req) {
            $details = json_decode($req->details, true);
            if (! is_array($details)) {
                continue;
            }

            $changed = false;
            if (! empty($details['tests']) && is_array($details['tests'])) {
                foreach ($details['tests'] as $idx => $tName) {
                    if (in_array(trim($tName), ['CBC & Blood Film', 'Complete blood count & Differential', 'Complete Blood Count (CBC)'], true)) {
                        $details['tests'][$idx] = 'CBC (5-Part Diff)';
                        $changed = true;
                    }
                }
                $details['tests'] = array_values(array_unique($details['tests']));
            }

            // تحديث test_attachments
            if (! empty($details['test_attachments']) && is_array($details['test_attachments'])) {
                foreach (['CBC & Blood Film', 'Complete blood count & Differential'] as $legacyKey) {
                    if (isset($details['test_attachments'][$legacyKey])) {
                        $details['test_attachments']['CBC (5-Part Diff)'] = $details['test_attachments'][$legacyKey];
                        unset($details['test_attachments'][$legacyKey]);
                        $changed = true;
                    }
                }
            }

            // تحديث test_results
            if (! empty($details['test_results']) && is_array($details['test_results'])) {
                foreach (['CBC & Blood Film', 'Complete blood count & Differential'] as $legacyKey) {
                    if (isset($details['test_results'][$legacyKey])) {
                        $details['test_results']['CBC (5-Part Diff)'] = $details['test_results'][$legacyKey];
                        $details['test_results']['CBC (5-Part Diff)']['test_name'] = 'CBC (5-Part Diff)';
                        unset($details['test_results'][$legacyKey]);
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                $desc = $req->description;
                if ($desc === 'CBC & Blood Film' || $desc === 'Complete blood count & Differential') {
                    $desc = 'CBC (5-Part Diff)';
                }
                DB::table('requests')->where('id', $req->id)->update([
                    'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                    'description' => $desc,
                ]);
            }
        }

        // 4. تحديث جدول emergency_lab_requests
        $emReqs = DB::table('emergency_lab_requests')
            ->where(function ($q) {
                $q->where('details', 'like', '%CBC & Blood Film%')
                    ->orWhere('details', 'like', '%Complete blood count & Differential%');
            })
            ->get(['id', 'details']);

        foreach ($emReqs as $em) {
            $details = json_decode($em->details, true);
            if (! is_array($details)) {
                continue;
            }

            $changed = false;
            if (! empty($details['test_attachments']) && is_array($details['test_attachments'])) {
                foreach (['CBC & Blood Film', 'Complete blood count & Differential'] as $legacyKey) {
                    if (isset($details['test_attachments'][$legacyKey])) {
                        $details['test_attachments']['CBC (5-Part Diff)'] = $details['test_attachments'][$legacyKey];
                        unset($details['test_attachments'][$legacyKey]);
                        $changed = true;
                    }
                }
            }

            if (! empty($details['test_results']) && is_array($details['test_results'])) {
                foreach (['CBC & Blood Film', 'Complete blood count & Differential'] as $legacyKey) {
                    if (isset($details['test_results'][$legacyKey])) {
                        $details['test_results']['CBC (5-Part Diff)'] = $details['test_results'][$legacyKey];
                        $details['test_results']['CBC (5-Part Diff)']['test_name'] = 'CBC (5-Part Diff)';
                        unset($details['test_results'][$legacyKey]);
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                DB::table('emergency_lab_requests')->where('id', $em->id)->update([
                    'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
